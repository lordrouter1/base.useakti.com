<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Core\Log;
use PDO;

/**
 * Model: DashboardWidget
 *
 * Gerencia a configuração de widgets do dashboard por grupo de usuários.
 * Cada grupo pode ter uma lista personalizada de widgets (visíveis/ocultos, com ordem).
 * Se o grupo não tiver configuração, o sistema retorna o padrão global.
 *
 * Entradas: Conexão PDO ($db), group_id, widget_key, sort_order, is_visible.
 * Saídas: Arrays de configuração de widgets.
 * Eventos: 'model.dashboard_widget.saved' (ao salvar configuração de grupo)
 *
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class DashboardWidget
{
    private $conn;
    private $table = 'dashboard_widgets';

    /**
     * Registro canônico de todos os widgets disponíveis no dashboard.
     * Chave = widget_key, Valor = metadados para UI e renderização.
     *
     * 'label'       → Nome amigável para exibição
     * 'icon'        → Classe Font Awesome
     * 'description' → Breve explicação do widget
     * 'file'        → Partial PHP que renderiza o widget
     */
    public const AVAILABLE_WIDGETS = [
        'header' => [
            'label'       => 'Saudação e Atalhos',
            'icon'        => 'fas fa-hand-sparkles',
            'description' => 'Saudação com nome do usuário, data e botões de atalho rápido.',
            'file'        => 'app/views/home/widgets/header.php',
        ],
        'cards_summary' => [
            'label'       => 'Cards de Resumo',
            'icon'        => 'fas fa-th-large',
            'description' => 'Indicadores principais: pedidos ativos, criados hoje, atrasados, concluídos no mês.',
            'file'        => 'app/views/home/widgets/cards_summary.php',
        ],
        'pipeline' => [
            'label'       => 'Pipeline',
            'icon'        => 'fas fa-stream',
            'description' => 'Visão resumida das etapas do pipeline com contadores.',
            'file'        => 'app/views/home/widgets/pipeline.php',
        ],
        'financeiro' => [
            'label'       => 'Resumo Financeiro',
            'icon'        => 'fas fa-coins',
            'description' => 'Recebido, a receber, em atraso e pendentes de confirmação.',
            'file'        => 'app/views/home/widgets/financeiro.php',
        ],
        'atrasados' => [
            'label'       => 'Pedidos Atrasados',
            'icon'        => 'fas fa-exclamation-triangle',
            'description' => 'Lista dos pedidos que ultrapassaram a meta de tempo na etapa.',
            'file'        => 'app/views/home/widgets/atrasados.php',
        ],
        'agenda' => [
            'label'       => 'Próximos Contatos',
            'icon'        => 'fas fa-calendar-check',
            'description' => 'Contatos agendados para os próximos dias.',
            'file'        => 'app/views/home/widgets/agenda.php',
        ],
        'atividade' => [
            'label'       => 'Atividade Recente',
            'icon'        => 'fas fa-history',
            'description' => 'Últimas movimentações de pedidos no pipeline.',
            'file'        => 'app/views/home/widgets/atividade.php',
        ],
    ];

    /**
     * Construtor
     * @param PDO $db Conexão PDO
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Retorna a lista de widgets disponíveis (estática).
     * @return array
     */
    public static function getAvailableWidgets(): array
    {
        return self::AVAILABLE_WIDGETS;
    }

    /**
     * Retorna a configuração de widgets para um grupo.
     * Se o grupo não tiver configuração, retorna array vazio.
     *
     * @param int $groupId ID do grupo
     * @return array Lista de registros [widget_key, sort_order, is_visible]
     */
    public function getByGroup(int $groupId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT widget_key, sort_order, is_visible 
             FROM {$this->table} 
             WHERE group_id = :gid 
             ORDER BY sort_order ASC"
        );
        $stmt->execute([':gid' => $groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se um grupo possui configuração personalizada.
     *
     * @param int $groupId
     * @return bool
     */
    public function hasConfig(int $groupId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE group_id = :gid"
        );
        $stmt->execute([':gid' => $groupId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Retorna a lista ordenada de widgets visíveis para um grupo.
     * Se o grupo não tiver configuração, retorna todos na ordem padrão.
     *
     * @param int $groupId
     * @return array Lista de widget_keys na ordem correta
     */
    public function getVisibleWidgetsForGroup(int $groupId): array
    {
        $config = $this->getByGroup($groupId);

        if (empty($config)) {
            // Sem personalização: retorna todos os widgets na ordem padrão
            return array_keys(self::AVAILABLE_WIDGETS);
        }

        $visible = [];
        foreach ($config as $row) {
            if ((int)$row['is_visible'] === 1) {
                $visible[] = $row['widget_key'];
            }
        }

        return $visible;
    }

    /**
     * Salva a configuração completa de widgets para um grupo.
     * Remove a configuração anterior e insere a nova.
     *
     * @param int   $groupId ID do grupo
     * @param array $widgets Array ordenado de ['widget_key' => string, 'is_visible' => 0|1]
     * @return bool
     */
    public function saveForGroup(int $groupId, array $widgets): bool
    {
        $this->conn->beginTransaction();

        try {
            // Limpar configuração anterior
            $del = $this->conn->prepare("DELETE FROM {$this->table} WHERE group_id = :gid");
            $del->execute([':gid' => $groupId]);

            // Inserir nova configuração
            $ins = $this->conn->prepare(
                "INSERT INTO {$this->table} (group_id, widget_key, sort_order, is_visible) 
                 VALUES (:gid, :wk, :so, :iv)"
            );

            $order = 0;
            foreach ($widgets as $w) {
                $key = $w['widget_key'] ?? '';
                if (!isset(self::AVAILABLE_WIDGETS[$key])) {
                    continue; // Ignorar widgets inválidos
                }
                $ins->execute([
                    ':gid' => $groupId,
                    ':wk'  => $key,
                    ':so'  => $order,
                    ':iv'  => (int)($w['is_visible'] ?? 1),
                ]);
                $order++;
            }

            $this->conn->commit();

            EventDispatcher::dispatch('model.dashboard_widget.saved', new Event('model.dashboard_widget.saved', [
                'group_id'     => $groupId,
                'widget_count' => $order,
            ]));

            return true;
        } catch (\Exception $e) {
            Log::error('DashboardWidget saveForGroup rollback', [
                'method' => __METHOD__,
                'error'  => $e->getMessage(),
                'code'   => $e->getCode(),
            ]);
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Remove toda a configuração de um grupo (volta ao padrão global).
     *
     * @param int $groupId
     * @return bool
     */
    public function resetGroup(int $groupId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE group_id = :gid");
        return $stmt->execute([':gid' => $groupId]);
    }
}
