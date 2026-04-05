<?php

namespace Akti\Controllers;

use Akti\Models\Quote;
use Akti\Utils\Input;

class QuoteController
{
    private \PDO $db;
    private Quote $model;

    public function __construct(\PDO $db, Quote $model)
    {
        $this->db = $db;
        $this->model = $model;
    }

    public function index()
    {
        $page = Input::get('p', 'int', 1);
        $filters = [
            'search'      => Input::get('search', 'string', ''),
            'status'      => Input::get('status', 'string', ''),
            'customer_id' => Input::get('customer_id', 'int', 0) ?: null,
        ];
        $filters = array_filter($filters);

        $result = $this->model->readPaginated($page, 15, $filters);
        $quotes = $result['data'];
        $pagination = $result;
        $summary = $this->model->getSummary();

        require 'app/views/layout/header.php';
        require 'app/views/quotes/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $customerStmt = $this->db->prepare("SELECT id, name FROM customers WHERE deleted_at IS NULL ORDER BY name");
        $customerStmt->execute();
        $customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);
        $quote = null;

        require 'app/views/layout/header.php';
        require 'app/views/quotes/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $data = [
            'tenant_id'      => $_SESSION['tenant']['id'] ?? 0,
            'customer_id'    => Input::post('customer_id', 'int', 0),
            'user_id'        => $_SESSION['user_id'] ?? null,
            'code'           => Input::post('code', 'string', ''),
            'valid_until'    => Input::post('valid_until', 'string', '') ?: null,
            'notes'          => Input::post('notes', 'string', ''),
            'internal_notes' => Input::post('internal_notes', 'string', ''),
        ];

        $id = $this->model->create($data);
        $_SESSION['flash_success'] = 'Orçamento criado com sucesso.';
        header('Location: ?page=quotes&action=edit&id=' . $id);
    }

    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $quote = $this->model->readOne($id);
        if (!$quote) {
            $_SESSION['flash_error'] = 'Orçamento não encontrado.';
            header('Location: ?page=quotes');
            return;
        }
        $items = $this->model->getItems($id);
        $versions = $this->model->getVersions($id);

        $customerStmt = $this->db->prepare("SELECT id, name FROM customers WHERE deleted_at IS NULL ORDER BY name");
        $customerStmt->execute();
        $customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);

        require 'app/views/layout/header.php';
        require 'app/views/quotes/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'customer_id'    => Input::post('customer_id', 'int', 0),
            'code'           => Input::post('code', 'string', ''),
            'status'         => Input::post('status', 'string', 'draft'),
            'valid_until'    => Input::post('valid_until', 'string', '') ?: null,
            'subtotal'       => Input::post('subtotal', 'float', 0),
            'discount'       => Input::post('discount', 'float', 0),
            'total'          => Input::post('total', 'float', 0),
            'notes'          => Input::post('notes', 'string', ''),
            'internal_notes' => Input::post('internal_notes', 'string', ''),
        ];

        $this->model->update($id, $data);
        $_SESSION['flash_success'] = 'Orçamento atualizado.';
        header('Location: ?page=quotes&action=edit&id=' . $id);
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->delete($id);
        $_SESSION['flash_success'] = 'Orçamento removido.';
        header('Location: ?page=quotes');
    }

    public function approve()
    {
        $token = Input::get('token', 'string', '');
        $quote = $this->model->readByToken($token);

        if (!$quote) {
            http_response_code(404);
            echo 'Orçamento não encontrado.';
            return;
        }

        if ($quote['status'] !== 'sent') {
            echo 'Este orçamento não pode mais ser aprovado.';
            return;
        }

        $this->model->approve($quote['id']);
        echo 'Orçamento aprovado com sucesso! Entraremos em contato em breve.';
    }

    public function convertToOrder()
    {
        $id = Input::get('id', 'int', 0);
        $quote = $this->model->readOne($id);

        if (!$quote || !in_array($quote['status'], ['approved', 'draft', 'sent'])) {
            $_SESSION['flash_error'] = 'Orçamento não pode ser convertido.';
            header('Location: ?page=quotes');
            return;
        }

        $items = $this->model->getItems($id);

        $this->db->beginTransaction();
        try {
            $orderStmt = $this->db->prepare(
                "INSERT INTO orders (tenant_id, customer_id, user_id, total, status, notes)
                 VALUES (:tenant_id, :customer_id, :user_id, :total, 'pending', :notes)"
            );
            $orderStmt->execute([
                ':tenant_id'   => $quote['tenant_id'],
                ':customer_id' => $quote['customer_id'],
                ':user_id'     => $_SESSION['user_id'] ?? null,
                ':total'       => $quote['total'],
                ':notes'       => 'Convertido do orçamento #' . $id,
            ]);
            $orderId = (int) $this->db->lastInsertId();

            foreach ($items as $item) {
                $itemStmt = $this->db->prepare(
                    "INSERT INTO order_items (tenant_id, order_id, product_id, description, quantity, unit_price, total)
                     VALUES (:tenant_id, :order_id, :product_id, :description, :quantity, :unit_price, :total)"
                );
                $itemStmt->execute([
                    ':tenant_id'  => $quote['tenant_id'],
                    ':order_id'   => $orderId,
                    ':product_id' => $item['product_id'],
                    ':description' => $item['description'],
                    ':quantity'   => $item['quantity'],
                    ':unit_price' => $item['unit_price'],
                    ':total'      => $item['total'],
                ]);
            }

            $this->model->convertToOrder($id, $orderId);
            $this->db->commit();

            $_SESSION['flash_success'] = 'Orçamento convertido em pedido #' . $orderId;
            header('Location: ?page=orders&action=edit&id=' . $orderId);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $_SESSION['flash_error'] = 'Erro ao converter orçamento.';
            header('Location: ?page=quotes&action=edit&id=' . $id);
        }
    }

    public function addItem()
    {
        $data = [
            'tenant_id'  => $_SESSION['tenant']['id'] ?? 0,
            'quote_id'   => Input::post('quote_id', 'int', 0),
            'product_id' => Input::post('product_id', 'int', 0) ?: null,
            'description' => Input::post('description', 'string', ''),
            'quantity'   => Input::post('quantity', 'float', 1),
            'unit_price' => Input::post('unit_price', 'float', 0),
            'discount'   => Input::post('discount', 'float', 0),
        ];

        $this->model->addItem($data);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function removeItem()
    {
        $itemId = Input::get('item_id', 'int', 0);
        $this->model->removeItem($itemId);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
