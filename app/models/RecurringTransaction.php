<?php
namespace Akti\Models;

use PDO;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

/**
 * RecurringTransaction — Model para transações recorrentes.
 *
 * Gerencia receitas e despesas fixas mensais (aluguel, salários, assinaturas, etc.).
 * Cada recorrência gera automaticamente transações no financial_transactions quando processada.
 *
 * @package Akti\Models
 */
class RecurringTransaction
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ═══════════════════════════════════════════
    // CRUD
    // ═══════════════════════════════════════════

    /**
     * Cria uma nova recorrência.
     */
    public function create(array $data): ?int
    {
        $sql = "INSERT INTO financial_recurring_transactions 
                (type, category, description, amount, due_day, payment_method, notes, start_month, end_month, user_id)
                VALUES (:type, :category, :description, :amount, :due_day, :payment_method, :notes, :start_month, :end_month, :user_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':type'           => $data['type'] ?? 'saida',
            ':category'       => $data['category'] ?? 'outra_saida',
            ':description'    => $data['description'] ?? '',
            ':amount'         => $data['amount'] ?? 0,
            ':due_day'        => min(28, max(1, (int)($data['due_day'] ?? 10))),
            ':payment_method' => $data['payment_method'] ?: null,
            ':notes'          => $data['notes'] ?: null,
            ':start_month'    => $data['start_month'] . '-01',
            ':end_month'      => !empty($data['end_month']) ? $data['end_month'] . '-01' : null,
            ':user_id'        => $data['user_id'] ?? null,
        ]);

        $id = (int) $this->db->lastInsertId();

        EventDispatcher::dispatch('model.recurring_transaction.created', new Event('model.recurring_transaction.created', [
            'id' => $id, 'type' => $data['type'], 'amount' => $data['amount'], 'description' => $data['description']
        ]));

        return $id ?: null;
    }

    /**
     * Atualiza uma recorrência existente.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE financial_recurring_transactions SET
                    type = :type, category = :category, description = :description,
                    amount = :amount, due_day = :due_day, payment_method = :payment_method,
                    notes = :notes, start_month = :start_month, end_month = :end_month
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':type'           => $data['type'] ?? 'saida',
            ':category'       => $data['category'] ?? 'outra_saida',
            ':description'    => $data['description'] ?? '',
            ':amount'         => $data['amount'] ?? 0,
            ':due_day'        => min(28, max(1, (int)($data['due_day'] ?? 10))),
            ':payment_method' => $data['payment_method'] ?: null,
            ':notes'          => $data['notes'] ?: null,
            ':start_month'    => $data['start_month'] . '-01',
            ':end_month'      => !empty($data['end_month']) ? $data['end_month'] . '-01' : null,
            ':id'             => $id,
        ]);

        EventDispatcher::dispatch('model.recurring_transaction.updated', new Event('model.recurring_transaction.updated', [
            'id' => $id, 'type' => $data['type'], 'amount' => $data['amount']
        ]));

        return $result;
    }

    /**
     * Busca uma recorrência pelo ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM financial_recurring_transactions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lista todas as recorrências (ativas primeiro).
     */
    public function readAll(): array
    {
        $sql = "SELECT * FROM financial_recurring_transactions ORDER BY is_active DESC, type ASC, description ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista apenas recorrências ativas.
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM financial_recurring_transactions WHERE is_active = 1 ORDER BY type ASC, description ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ativa/desativa uma recorrência.
     */
    public function toggleActive(int $id, bool $active): bool
    {
        $stmt = $this->db->prepare("UPDATE financial_recurring_transactions SET is_active = :active WHERE id = :id");
        return $stmt->execute([':active' => $active ? 1 : 0, ':id' => $id]);
    }

    /**
     * Exclui uma recorrência.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM financial_recurring_transactions WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);

        EventDispatcher::dispatch('model.recurring_transaction.deleted', new Event('model.recurring_transaction.deleted', ['id' => $id]));

        return $result;
    }

    // ═══════════════════════════════════════════
    // PROCESSAMENTO MENSAL
    // ═══════════════════════════════════════════

    /**
     * Processa recorrências pendentes para o mês atual.
     * Gera as transações financeiras correspondentes.
     *
     * @param int|null $userId Usuário que disparou o processamento
     * @return array Resumo: [generated => int, skipped => int, errors => array]
     */
    public function processMonth(?int $userId = null): array
    {
        $currentMonth = date('Y-m-01');
        $result = ['generated' => 0, 'skipped' => 0, 'errors' => []];

        $activeRecurrences = $this->getActive();

        foreach ($activeRecurrences as $rec) {
            try {
                // Verificar se já foi gerada neste mês
                if ($rec['last_generated_month'] && $rec['last_generated_month'] >= $currentMonth) {
                    $result['skipped']++;
                    continue;
                }

                // Verificar se o mês atual está dentro do range
                if ($rec['start_month'] > $currentMonth) {
                    $result['skipped']++;
                    continue;
                }
                if ($rec['end_month'] && $rec['end_month'] < $currentMonth) {
                    $result['skipped']++;
                    continue;
                }

                // Gerar transação
                $transactionDate = date('Y-m-') . str_pad($rec['due_day'], 2, '0', STR_PAD_LEFT);

                $financial = new Financial($this->db);
                $txId = $financial->addTransaction([
                    'type'            => $rec['type'],
                    'category'        => $rec['category'],
                    'description'     => '[Recorrência] ' . $rec['description'],
                    'amount'          => $rec['amount'],
                    'transaction_date' => $transactionDate,
                    'payment_method'  => $rec['payment_method'],
                    'reference_type'  => 'recurring',
                    'reference_id'    => $rec['id'],
                    'is_confirmed'    => 0,
                    'notes'           => $rec['notes'],
                    'user_id'         => $userId,
                ]);

                if ($txId) {
                    // Vincular ao recurring_id se a coluna existir
                    $this->linkTransactionToRecurring($txId, $rec['id']);

                    // Atualizar last_generated_month
                    $stmt = $this->db->prepare(
                        "UPDATE financial_recurring_transactions SET last_generated_month = :month WHERE id = :id"
                    );
                    $stmt->execute([':month' => $currentMonth, ':id' => $rec['id']]);

                    $result['generated']++;
                } else {
                    $result['errors'][] = "Falha ao gerar transação para recorrência #{$rec['id']}";
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "Recorrência #{$rec['id']}: " . $e->getMessage();
            }
        }

        EventDispatcher::dispatch('model.recurring_transaction.processed', new Event('model.recurring_transaction.processed', $result));

        return $result;
    }

    /**
     * Vincula uma transação à sua recorrência de origem (se a coluna existir).
     */
    private function linkTransactionToRecurring(int $txId, int $recurringId): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE financial_transactions SET recurring_id = :rid WHERE id = :id");
            $stmt->execute([':rid' => $recurringId, ':id' => $txId]);
        } catch (\Throwable $e) {
            // Coluna pode não existir ainda — silencioso
        }
    }

    // ═══════════════════════════════════════════
    // RESUMO / PROJEÇÃO
    // ═══════════════════════════════════════════

    /**
     * Retorna o resumo mensal de recorrências ativas (total entradas, saídas, saldo).
     */
    public function getMonthlySummary(): array
    {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN type = 'entrada' THEN amount ELSE 0 END), 0) as total_entradas,
                    COALESCE(SUM(CASE WHEN type = 'saida'   THEN amount ELSE 0 END), 0) as total_saidas
                FROM financial_recurring_transactions
                WHERE is_active = 1";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'entradas' => (float) $row['total_entradas'],
            'saidas'   => (float) $row['total_saidas'],
            'saldo'    => (float) $row['total_entradas'] - (float) $row['total_saidas'],
        ];
    }

    /**
     * Projeta recorrências ativas para os próximos N meses.
     * Retorna array mês a mês com entradas/saídas projetadas.
     *
     * @param int $months Horizonte de projeção
     * @return array [['month' => 'YYYY-MM', 'entradas' => float, 'saidas' => float], ...]
     */
    public function projectMonths(int $months = 6): array
    {
        $active = $this->getActive();
        $projection = [];

        for ($i = 0; $i < $months; $i++) {
            $monthDate = date('Y-m-01', strtotime("+{$i} months"));
            $monthKey  = date('Y-m', strtotime("+{$i} months"));
            $entradas  = 0;
            $saidas    = 0;

            foreach ($active as $rec) {
                if ($rec['start_month'] > $monthDate) continue;
                if ($rec['end_month'] && $rec['end_month'] < $monthDate) continue;

                if ($rec['type'] === 'entrada') {
                    $entradas += (float) $rec['amount'];
                } else {
                    $saidas += (float) $rec['amount'];
                }
            }

            $projection[] = [
                'month'    => $monthKey,
                'entradas' => $entradas,
                'saidas'   => $saidas,
            ];
        }

        return $projection;
    }

    /**
     * Verifica se a tabela existe no banco.
     */
    public static function tableExists(PDO $db): bool
    {
        static $exists = null;
        if ($exists !== null) return $exists;

        try {
            $db->query("SELECT 1 FROM financial_recurring_transactions LIMIT 1");
            $exists = true;
        } catch (\Throwable $e) {
            $exists = false;
        }
        return $exists;
    }
}
