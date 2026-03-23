<?php
namespace Akti\Services;

use Akti\Models\Commission;
use Akti\Models\Order;
use Akti\Models\User;
use Akti\Models\UserGroup;
use PDO;

/**
 * CommissionService — Camada de Serviço para Comissões
 *
 * Orquestra operações entre Engine, Model e regras de negócio.
 * Os Controllers delegam a execução para este Service.
 *
 * Dependency Injection:
 *   - CommissionEngine é injetado no construtor
 *   - Commission model é injetado no construtor
 *
 * @package Akti\Services
 */
class CommissionService
{
    private CommissionEngine $engine;
    private Commission $model;
    private PDO $db;

    public function __construct(PDO $db, CommissionEngine $engine, Commission $model)
    {
        $this->db = $db;
        $this->engine = $engine;
        $this->model = $model;
    }

    // ═══════════════════════════════════════════════════
    // FORMAS DE COMISSÃO
    // ═══════════════════════════════════════════════════

    public function getAllFormas(): array
    {
        return $this->model->getAllFormas();
    }

    public function getForma(int $id): ?array
    {
        return $this->model->getForma($id);
    }

    public function createForma(array $data): array
    {
        $id = $this->model->createForma($data);
        if ($id && $data['tipo_calculo'] === 'faixa' && !empty($data['faixas'])) {
            $this->model->saveFaixas($id, $data['faixas']);
        }
        return ['success' => true, 'id' => $id, 'message' => 'Forma de comissão criada com sucesso.'];
    }

    public function updateForma(int $id, array $data): array
    {
        $ok = $this->model->updateForma($id, $data);
        if ($ok && $data['tipo_calculo'] === 'faixa' && isset($data['faixas'])) {
            $this->model->saveFaixas($id, $data['faixas']);
        }
        return ['success' => $ok, 'message' => $ok ? 'Forma atualizada com sucesso.' : 'Erro ao atualizar.'];
    }

    public function deleteForma(int $id): array
    {
        $ok = $this->model->deleteForma($id);
        return ['success' => $ok, 'message' => $ok ? 'Forma removida com sucesso.' : 'Erro ao remover.'];
    }

    public function getFaixas(int $formaId): array
    {
        return $this->model->getFaixas($formaId);
    }

    // ═══════════════════════════════════════════════════
    // VÍNCULOS GRUPO / USUÁRIO
    // ═══════════════════════════════════════════════════

    public function getGrupoFormas(?int $groupId = null): array
    {
        return $this->model->getGrupoFormas($groupId);
    }

    public function linkGrupoForma(int $groupId, int $formaId): array
    {
        $ok = $this->model->linkGrupoForma($groupId, $formaId);
        return ['success' => $ok, 'message' => $ok ? 'Vínculo criado.' : 'Erro ao criar vínculo.'];
    }

    public function unlinkGrupoForma(int $id): array
    {
        $ok = $this->model->unlinkGrupoForma($id);
        return ['success' => $ok, 'message' => $ok ? 'Vínculo removido.' : 'Erro ao remover.'];
    }

    public function getUsuarioFormas(?int $userId = null): array
    {
        return $this->model->getUsuarioFormas($userId);
    }

    public function linkUsuarioForma(int $userId, int $formaId): array
    {
        $ok = $this->model->linkUsuarioForma($userId, $formaId);
        return ['success' => $ok, 'message' => $ok ? 'Vínculo criado.' : 'Erro ao criar vínculo.'];
    }

    public function unlinkUsuarioForma(int $id): array
    {
        $ok = $this->model->unlinkUsuarioForma($id);
        return ['success' => $ok, 'message' => $ok ? 'Vínculo removido.' : 'Erro ao remover.'];
    }

    // ═══════════════════════════════════════════════════
    // REGRAS POR PRODUTO
    // ═══════════════════════════════════════════════════

    public function getComissaoProdutos(): array
    {
        return $this->model->getComissaoProdutos();
    }

    public function saveComissaoProduto(array $data): array
    {
        $id = $this->model->saveComissaoProduto($data);
        return ['success' => true, 'id' => $id, 'message' => 'Regra salva com sucesso.'];
    }

    public function deleteComissaoProduto(int $id): array
    {
        $ok = $this->model->deleteComissaoProduto($id);
        return ['success' => $ok, 'message' => $ok ? 'Regra removida.' : 'Erro ao remover.'];
    }

    // ═══════════════════════════════════════════════════
    // CÁLCULO / SIMULAÇÃO
    // ═══════════════════════════════════════════════════

    /**
     * Simula comissão sem registrar.
     */
    public function simular(array $context): array
    {
        return $this->engine->calcular($context);
    }

    /**
     * Calcula e registra comissão para um pedido.
     */
    public function calcularComissao(int $orderId, int $userId, ?string $observacao = null): array
    {
        $orderModel = new Order($this->db);
        $order = $orderModel->readOne($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado.'];
        }

        // Verificar se permite calcular pedidos cancelados
        $permiteCancelado = (bool) $this->model->getConfigValue('permite_comissao_cancelado', 0);
        if (!$permiteCancelado && ($order['status'] ?? '') === 'cancelado') {
            return ['success' => false, 'message' => 'Não é permitido calcular comissão para pedidos cancelados.'];
        }

        $context = [
            'user_id'      => $userId,
            'order_id'     => $orderId,
            'valor_venda'  => (float) ($order['total_amount'] ?? 0),
            'margem_lucro' => (float) ($order['profit_margin'] ?? 0),
            'observacao'   => $observacao,
        ];

        $resultado = $this->engine->calcularERegistrar($context);

        if (!empty($resultado['error'])) {
            return ['success' => false, 'message' => $resultado['error']];
        }
        if (!empty($resultado['warning'])) {
            return ['success' => false, 'message' => $resultado['warning']];
        }

        return [
            'success'          => true,
            'message'          => 'Comissão calculada com sucesso.',
            'comissao_id'      => $resultado['comissao_id'] ?? null,
            'valor_comissao'   => $resultado['valor_comissao'],
            'percentual'       => $resultado['percentual_aplicado'],
            'origem'           => $resultado['origem_regra'],
            'status'           => $resultado['status'] ?? 'calculada',
        ];
    }

    // ═══════════════════════════════════════════════════
    // COMISSÕES REGISTRADAS (OPERACIONAL)
    // ═══════════════════════════════════════════════════

    public function getComissoesRegistradas(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        return $this->model->getComissoesRegistradas($filters, $page, $perPage);
    }

    public function getComissaoRegistrada(int $id): ?array
    {
        return $this->model->getComissaoRegistrada($id);
    }

    public function aprovarComissao(int $id, int $approvedBy): array
    {
        $ok = $this->model->updateComissaoStatus($id, 'aprovada', $approvedBy);
        return ['success' => $ok, 'message' => $ok ? 'Comissão aprovada.' : 'Erro ao aprovar.'];
    }

    public function pagarComissao(int $id): array
    {
        $ok = $this->model->updateComissaoStatus($id, 'paga');
        return ['success' => $ok, 'message' => $ok ? 'Comissão marcada como paga.' : 'Erro ao atualizar.'];
    }

    public function cancelarComissao(int $id): array
    {
        $ok = $this->model->updateComissaoStatus($id, 'cancelada');
        return ['success' => $ok, 'message' => $ok ? 'Comissão cancelada.' : 'Erro ao cancelar.'];
    }

    /**
     * Aprovar múltiplas comissões.
     */
    public function aprovarEmLote(array $ids, int $approvedBy): array
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->model->updateComissaoStatus((int) $id, 'aprovada', $approvedBy)) {
                $count++;
            }
        }
        return ['success' => true, 'message' => "{$count} comissões aprovadas.", 'count' => $count];
    }

    /**
     * Pagar múltiplas comissões.
     */
    public function pagarEmLote(array $ids): array
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->model->updateComissaoStatus((int) $id, 'paga')) {
                $count++;
            }
        }
        return ['success' => true, 'message' => "{$count} comissões pagas.", 'count' => $count];
    }

    // ═══════════════════════════════════════════════════
    // DASHBOARD
    // ═══════════════════════════════════════════════════

    public function getDashboardSummary(?int $month = null, ?int $year = null): array
    {
        return $this->model->getDashboardSummary($month, $year);
    }

    // ═══════════════════════════════════════════════════
    // CONFIGURAÇÕES
    // ═══════════════════════════════════════════════════

    public function getConfig(): array
    {
        return $this->model->getConfig();
    }

    public function saveConfig(array $configs): array
    {
        foreach ($configs as $key => $value) {
            $this->model->saveConfig($key, $value);
        }
        return ['success' => true, 'message' => 'Configurações salvas com sucesso.'];
    }

    /**
     * Retorna dados auxiliares para views (grupos, usuários, formas, produtos).
     */
    public function getAuxData(): array
    {
        $userGroupModel = new UserGroup($this->db);
        $groups = $userGroupModel->readAll()->fetchAll(\PDO::FETCH_ASSOC);

        $userModel = new User($this->db);
        $users = $userModel->readAll()->fetchAll(\PDO::FETCH_ASSOC);

        $formas = $this->model->getAllFormas();

        return [
            'groups' => $groups,
            'users'  => $users,
            'formas' => $formas,
        ];
    }

    /**
     * Retorna dados completos para a view de regras por usuário.
     */
    public function getUsuariosComRegras(): array
    {
        return $this->model->getUsuariosComRegras();
    }
}
