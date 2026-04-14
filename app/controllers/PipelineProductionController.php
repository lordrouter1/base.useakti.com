<?php

namespace Akti\Controllers;

use Akti\Models\Pipeline;
use Akti\Models\User;
use Akti\Models\Logger;
use Akti\Models\OrderItemLog;
use Akti\Services\PipelineDetailService;
use Akti\Utils\Input;

/**
 * PipelineProductionController — Painel de produção e setores.
 *
 * Extraído de PipelineController para separação de responsabilidades.
 *
 * @package Akti\Controllers
 */
class PipelineProductionController extends BaseController
{
    private \PDO $db;
    private Pipeline $pipelineModel;
    private PipelineDetailService $detailService;

    public function __construct(\PDO $db, Pipeline $pipelineModel, PipelineDetailService $detailService)
    {
        $this->db = $db;
        $this->pipelineModel = $pipelineModel;
        $this->detailService = $detailService;
    }

    public function moveSector()
    {
        header('Content-Type: application/json');

        $orderId = Input::post('order_id', 'int') ?: Input::get('order_id', 'int');
        $orderItemId = Input::post('order_item_id', 'int') ?: Input::get('order_item_id', 'int');
        $sectorId = Input::post('sector_id', 'int') ?: Input::get('sector_id', 'int');
        $action = Input::post('move_action') ?: Input::get('move_action', 'string', 'advance');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$orderId || !$orderItemId || !$sectorId) {
            $this->json(['success' => false, 'message' => 'Parâmetros inválidos']);
        }

        $userModel = new User($this->db);
        $allowedSectors = $userModel->getAllowedSectorIds($userId);
        if (!empty($allowedSectors) && !in_array((int)$sectorId, $allowedSectors)) {
            $this->json(['success' => false, 'message' => 'Sem permissão para este setor']);
        }

        $result = false;
        if ($action === 'advance') {
            $result = $this->pipelineModel->advanceItemSector($orderId, $orderItemId, $sectorId, $userId);
        } elseif ($action === 'revert') {
            $result = $this->pipelineModel->revertItemSector($orderId, $orderItemId, $sectorId, $userId);
        }

        if ($result) {
            $logger = new Logger($this->db);
            $logger->log('PRODUCTION_SECTOR_MOVE', "Order #$orderId item #$orderItemId sector #$sectorId action:$action");
        }

        $this->json(['success' => $result]);
    }

    public function productionBoard()
    {
        $userModel = new User($this->db);
        $userAllowedSectorIds = $userModel->getAllowedSectorIds($_SESSION['user_id'] ?? 0);

        $data = $this->detailService->loadProductionBoardData($userAllowedSectorIds);
        extract($data);

        require 'app/views/layout/header.php';
        require 'app/views/pipeline/production_board.php';
        require 'app/views/layout/footer.php';
    }

    public function getItemLogs()
    {
        header('Content-Type: application/json');
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();

        $orderItemId = Input::get('order_item_id', 'int');
        if (!$orderItemId) {
            $this->json(['success' => false, 'message' => 'Item não informado']);
        }

        $logs = $logModel->getLogsByItem($orderItemId);
        $this->json(['success' => true, 'logs' => $logs]);
    }

    public function addItemLog()
    {
        header('Content-Type: application/json');
        $logModel = new OrderItemLog($this->db);
        $logModel->createTableIfNotExists();

        $orderId = Input::post('order_id', 'int');
        $orderItemId = Input::post('order_item_id', 'int');
        $allItems = Input::post('all_items');
        $orderItemIds = Input::postArray('order_item_ids');
        $message = Input::post('message');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$orderId) {
            $this->json(['success' => false, 'message' => 'Parâmetros inválidos']);
        }

        if ($allItems && !empty($orderItemIds)) {
            // Registrar para todos os itens
        } elseif ($orderItemId) {
            $orderItemIds = [$orderItemId];
        } else {
            $this->json(['success' => false, 'message' => 'Selecione um produto']);
        }

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $firstItemId = $orderItemIds[0] ?? 0;
            $uploadResult = $logModel->handleFileUpload($_FILES['file'], $orderId, $firstItemId);
            if (isset($uploadResult['error'])) {
                $this->json(['success' => false, 'message' => $uploadResult['error']]);
            }
            $filePath = $uploadResult['file_path'];
            $fileName = $uploadResult['file_name'];
            $fileType = $uploadResult['file_type'];
        }

        if (empty($message) && empty($filePath)) {
            $this->json(['success' => false, 'message' => 'Informe uma mensagem ou envie um arquivo.']);
        }

        $logIds = [];
        foreach ($orderItemIds as $iid) {
            $logId = $logModel->addLog($orderId, $iid, $userId, $message ?: null, $filePath, $fileName, $fileType);
            $logIds[] = $logId;
        }

        $logger = new Logger($this->db);
        $itemCount = count($logIds);
        $logger->log('ITEM_LOG_ADDED', "Log added to order #$orderId for $itemCount item(s)");

        $this->json(['success' => true, 'log_ids' => $logIds]);
    }

    public function deleteItemLog()
    {
        header('Content-Type: application/json');
        $logModel = new OrderItemLog($this->db);

        $logId = Input::post('log_id', 'int');
        $userId = $_SESSION['user_id'] ?? null;

        if (!$logId) {
            $this->json(['success' => false, 'message' => 'ID do log não informado']);
        }

        $result = $logModel->deleteLog($logId, $userId);
        $this->json(['success' => $result]);
    }
}
