<?php
namespace Akti\Controllers;

use Akti\Models\Commission;
use Akti\Models\Product;
use Akti\Models\Category;
use Akti\Services\CommissionEngine;
use Akti\Services\CommissionService;
use Akti\Utils\Input;

/**
 * CommissionController — Controller do Módulo de Comissões
 *
 * Responsabilidades:
 *   - Delegar execução ao CommissionService
 *   - Retornar respostas JSON padronizadas para ações AJAX
 *   - Renderizar views para ações de navegação
 *
 * @package Akti\Controllers
 */
class CommissionController extends BaseController
{
    private CommissionService $service;
    private Commission $model;

    public function __construct(\PDO $db, Commission $model, CommissionEngine $engine, CommissionService $service)
    {
        $this->db = $db;
        $this->model = $model;
        $this->service = $service;
    }

    // ═══════════════════════════════════════════════════
    // DASHBOARD (Visão Geral)
    // ═══════════════════════════════════════════════════

    public function index()
    {
        $month = Input::get('month', 'int', (int) date('m'));
        $year  = Input::get('year', 'int', (int) date('Y'));

        $summary = $this->service->getDashboardSummary($month, $year);
        $config  = $this->service->getConfig();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/index.php';
        require 'app/views/layout/footer.php';
    }

    // ═══════════════════════════════════════════════════
    // FORMAS DE COMISSÃO (Cadastros)
    // ═══════════════════════════════════════════════════

    public function formas()
    {
        $formas = $this->service->getAllFormas();
        $aux = $this->service->getAuxData();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/formas.php';
        require 'app/views/layout/footer.php';
    }

    public function storeForma()
    {
        header('Content-Type: application/json');

        $data = [
            'nome'         => Input::post('nome'),
            'descricao'    => Input::post('descricao'),
            'tipo_calculo' => Input::post('tipo_calculo', 'enum', 'percentual', ['percentual', 'valor_fixo', 'faixa']),
            'base_calculo' => Input::post('base_calculo', 'enum', 'valor_venda', ['valor_venda', 'margem_lucro', 'valor_produto']),
            'valor'        => Input::post('valor', 'float', 0),
            'ativo'        => Input::post('ativo', 'int', 1),
        ];

        // Faixas para tipo "faixa"
        if ($data['tipo_calculo'] === 'faixa') {
            $data['faixas'] = $this->parseFaixasFromPost();
        }

        if (empty($data['nome'])) {
            $this->json(['success' => false, 'message' => 'Nome é obrigatório.']);
        }

        $result = $this->service->createForma($data);
        $this->json($result);
    }

    public function updateForma()
    {
        header('Content-Type: application/json');

        $id = Input::post('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }

        $data = [
            'nome'         => Input::post('nome'),
            'descricao'    => Input::post('descricao'),
            'tipo_calculo' => Input::post('tipo_calculo', 'enum', 'percentual', ['percentual', 'valor_fixo', 'faixa']),
            'base_calculo' => Input::post('base_calculo', 'enum', 'valor_venda', ['valor_venda', 'margem_lucro', 'valor_produto']),
            'valor'        => Input::post('valor', 'float', 0),
            'ativo'        => Input::post('ativo', 'int', 1),
        ];

        if ($data['tipo_calculo'] === 'faixa') {
            $data['faixas'] = $this->parseFaixasFromPost();
        }

        $result = $this->service->updateForma($id, $data);
        $this->json($result);
    }

    public function deleteForma()
    {
        header('Content-Type: application/json');
        $id = Input::get('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }
        $result = $this->service->deleteForma($id);
        $this->json($result);
    }

    public function getFaixas()
    {
        header('Content-Type: application/json');
        $id = Input::get('id', 'int');
        $this->json(['success' => true, 'data' => $this->service->getFaixas($id)]);
    }

    // ═══════════════════════════════════════════════════
    // REGRAS POR GRUPO
    // ═══════════════════════════════════════════════════

    public function grupos()
    {
        $grupoFormas = $this->service->getGrupoFormas();
        $aux = $this->service->getAuxData();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/grupos.php';
        require 'app/views/layout/footer.php';
    }

    public function linkGrupo()
    {
        header('Content-Type: application/json');
        $groupId = Input::post('group_id', 'int');
        $formaId = Input::post('forma_comissao_id', 'int');

        if (!$groupId || !$formaId) {
            $this->json(['success' => false, 'message' => 'Dados incompletos.']);
        }

        $result = $this->service->linkGrupoForma($groupId, $formaId);
        $this->json($result);
    }

    public function unlinkGrupo()
    {
        header('Content-Type: application/json');
        $id = Input::get('id', 'int') ?: Input::post('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }
        $result = $this->service->unlinkGrupoForma($id);
        $this->json($result);
    }

    // ═══════════════════════════════════════════════════
    // REGRAS POR USUÁRIO
    // ═══════════════════════════════════════════════════

    public function usuarios()
    {
        $usuarioFormas = $this->service->getUsuarioFormas();
        $usuariosComRegras = $this->service->getUsuariosComRegras();
        $aux = $this->service->getAuxData();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/usuarios.php';
        require 'app/views/layout/footer.php';
    }

    public function linkUsuario()
    {
        header('Content-Type: application/json');
        $userId = Input::post('user_id', 'int');
        $formaId = Input::post('forma_comissao_id', 'int');

        if (!$userId || !$formaId) {
            $this->json(['success' => false, 'message' => 'Dados incompletos.']);
        }

        $result = $this->service->linkUsuarioForma($userId, $formaId);
        $this->json($result);
    }

    public function unlinkUsuario()
    {
        header('Content-Type: application/json');
        $id = Input::get('id', 'int') ?: Input::post('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }
        $result = $this->service->unlinkUsuarioForma($id);
        $this->json($result);
    }

    // ═══════════════════════════════════════════════════
    // REGRAS POR PRODUTO / CATEGORIA
    // ═══════════════════════════════════════════════════

    public function produtos()
    {
        $regras = $this->service->getComissaoProdutos();

        $productModel = new Product($this->db);
        $products = $productModel->readAll();

        $categoryModel = new Category($this->db);
        $categories = $categoryModel->readAll();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/produtos.php';
        require 'app/views/layout/footer.php';
    }

    public function saveProdutoRegra()
    {
        header('Content-Type: application/json');

        $data = [
            'id'           => Input::post('id', 'int', 0),
            'product_id'   => Input::post('product_id', 'int', 0),
            'category_id'  => Input::post('category_id', 'int', 0),
            'tipo_calculo' => Input::post('tipo_calculo', 'enum', 'percentual', ['percentual', 'valor_fixo']),
            'valor'        => Input::post('valor', 'float', 0),
            'ativo'        => Input::post('ativo', 'int', 1),
        ];

        if (!$data['product_id'] && !$data['category_id']) {
            $this->json(['success' => false, 'message' => 'Selecione um produto ou categoria.']);
        }

        $result = $this->service->saveComissaoProduto($data);
        $this->json($result);
    }

    public function deleteProdutoRegra()
    {
        header('Content-Type: application/json');
        $id = Input::get('id', 'int') ?: Input::post('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }
        $result = $this->service->deleteComissaoProduto($id);
        $this->json($result);
    }

    // ═══════════════════════════════════════════════════
    // SIMULADOR
    // ═══════════════════════════════════════════════════

    public function simulador()
    {
        $aux = $this->service->getAuxData();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/simulador.php';
        require 'app/views/layout/footer.php';
    }

    public function simularCalculo()
    {
        header('Content-Type: application/json');

        $context = [
            'user_id'      => Input::post('user_id', 'int'),
            'order_id'     => Input::post('order_id', 'int', 0),
            'valor_venda'  => Input::post('valor_venda', 'float', 0),
            'margem_lucro' => Input::post('margem_lucro', 'float', 0),
            'product_id'   => Input::post('product_id', 'int', 0),
            'category_id'  => Input::post('category_id', 'int', 0),
        ];

        if (!$context['user_id']) {
            $this->json(['success' => false, 'message' => 'Selecione um usuário.']);
        }
        if ($context['valor_venda'] <= 0) {
            $this->json(['success' => false, 'message' => 'Informe o valor da venda.']);
        }

        $resultado = $this->service->simular($context);
        $this->json(['success' => true, 'data' => $resultado]);
    }

    // ═══════════════════════════════════════════════════
    // CÁLCULO REAL (registrar comissão)
    // ═══════════════════════════════════════════════════

    public function calcular()
    {
        header('Content-Type: application/json');

        $orderId = Input::post('order_id', 'int');
        $userId  = Input::post('user_id', 'int');
        $obs     = Input::post('observacao');

        if (!$orderId || !$userId) {
            $this->json(['success' => false, 'message' => 'Dados incompletos.']);
        }

        $result = $this->service->calcularComissao($orderId, $userId, $obs);
        $this->json($result);
    }

    // ═══════════════════════════════════════════════════
    // HISTÓRICO / RELATÓRIOS
    // ═══════════════════════════════════════════════════

    public function historico()
    {
        $aux = $this->service->getAuxData();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/historico.php';
        require 'app/views/layout/footer.php';
    }

    public function getHistoricoPaginated()
    {
        header('Content-Type: application/json');

        $filters = [];
        if (Input::hasGet('user_id'))   $filters['user_id']   = Input::get('user_id', 'int');
        if (Input::hasGet('status'))    $filters['status']     = Input::get('status');
        if (Input::hasGet('month'))     $filters['month']      = Input::get('month', 'int');
        if (Input::hasGet('year'))      $filters['year']       = Input::get('year', 'int');
        if (Input::hasGet('date_from')) $filters['date_from']  = Input::get('date_from', 'date');
        if (Input::hasGet('date_to'))   $filters['date_to']    = Input::get('date_to', 'date');
        if (Input::hasGet('search'))    $filters['search']     = Input::get('search');

        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 25);

        $result = $this->service->getComissoesRegistradas($filters, $page, $perPage);
        $this->json(['success' => true] + $result);
    }

    // ═══════════════════════════════════════════════════
    // AÇÕES DE STATUS (aprovar, pagar, cancelar)
    // ═══════════════════════════════════════════════════

    public function aprovar()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }
        $result = $this->service->aprovarComissao($id, (int) $_SESSION['user_id']);
        $this->json($result);
    }

    public function pagar()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }
        $result = $this->service->pagarComissao($id);
        $this->json($result);
    }

    public function cancelar()
    {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID inválido.']);
        }
        $result = $this->service->cancelarComissao($id);
        $this->json($result);
    }

    public function aprovarLote()
    {
        header('Content-Type: application/json');
        $ids = Input::post('ids', 'intArray', []);
        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'Nenhum item selecionado.']);
        }
        $result = $this->service->aprovarEmLote($ids, (int) $_SESSION['user_id']);
        $this->json($result);
    }

    public function pagarLote()
    {
        header('Content-Type: application/json');
        $ids = Input::post('ids', 'intArray', []);
        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'Nenhum item selecionado.']);
        }
        $result = $this->service->pagarEmLote($ids);
        $this->json($result);
    }

    // ═══════════════════════════════════════════════════
    // CONFIGURAÇÕES
    // ═══════════════════════════════════════════════════

    public function configuracoes()
    {
        $config = $this->service->getConfig();

        require 'app/views/layout/header.php';
        require 'app/views/commissions/configuracoes.php';
        require 'app/views/layout/footer.php';
    }

    public function saveConfig()
    {
        header('Content-Type: application/json');

        $configs = [
            'comissao_padrao_percentual'    => Input::post('comissao_padrao_percentual', 'float', 5),
            'base_calculo_padrao'           => Input::post('base_calculo_padrao', 'enum', 'valor_venda', ['valor_venda', 'margem_lucro', 'valor_produto']),
            'aprovacao_automatica'          => Input::post('aprovacao_automatica', 'int', 0),
            'permite_comissao_cancelado'    => Input::post('permite_comissao_cancelado', 'int', 0),
            'pipeline_stage_comissao'       => Input::post('pipeline_stage_comissao'),
            'criterio_liberacao_comissao'   => Input::post('criterio_liberacao_comissao', 'enum', 'pagamento_total', ['sem_confirmacao', 'primeira_parcela', 'pagamento_total']),
        ];

        $result = $this->service->saveConfig($configs);
        $this->json($result);
    }

    // ═══════════════════════════════════════════════════
    // APROVAÇÃO / PAGAMENTO POR VENDEDOR (Modal)
    // ═══════════════════════════════════════════════════

    /**
     * Retorna lista de vendedores com comissões pendentes (JSON).
     */
    public function getVendedoresPendentes()
    {
        header('Content-Type: application/json');
        $data = $this->service->getVendedoresComPendentes();
        $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * Retorna comissões pendentes de um vendedor (JSON).
     */
    public function getComissoesVendedor()
    {
        header('Content-Type: application/json');
        $userId = Input::get('user_id', 'int');
        $statusFilter = Input::get('status_filter'); // 'aprovacao' | 'pagamento' | null

        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Vendedor não informado.']);
        }

        $data = $this->service->getComissoesPorVendedor($userId, $statusFilter);
        $this->json(['success' => true, 'data' => $data]);
    }

    // ═══════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════

    private function parseFaixasFromPost(): array
    {
        $faixas = [];
        $mins = Input::postArray('faixa_min');
        $maxs = Input::postArray('faixa_max');
        $pcts = Input::postArray('faixa_pct');

        if (is_array($mins)) {
            for ($i = 0; $i < count($mins); $i++) {
                $faixas[] = [
                    'faixa_min'  => (float) ($mins[$i] ?? 0),
                    'faixa_max'  => isset($maxs[$i]) && $maxs[$i] !== '' ? (float) $maxs[$i] : null,
                    'percentual' => (float) ($pcts[$i] ?? 0),
                ];
            }
        }

        return $faixas;
    }
}
