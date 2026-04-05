<?php
namespace Akti\Models;

use PDO;

/**
 * Model: NfeReportModel
 * Queries para relatórios fiscais do módulo NF-e/NFC-e.
 *
 * Fontes de dados:
 *   - nfe_documents (principal)
 *   - nfe_document_items (detalhamento por item — NCM, CFOP, impostos)
 *   - nfe_logs (comunicação SEFAZ)
 *   - nfe_correction_history (CC-e)
 *   - orders + customers (dados complementares)
 *
 * Entradas: Conexão PDO ($db), períodos (start/end) via parâmetros.
 * Saídas: Arrays de dados para relatórios.
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class NfeReportModel
{
    private $conn;

    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════
    // NF-e POR PERÍODO
    // ═══════════════════════════════════════════

    /**
     * Retorna NF-e emitidas dentro de um período com filtros opcionais.
     *
     * @param string      $start   Data inicial (Y-m-d)
     * @param string      $end     Data final (Y-m-d)
     * @param array       $filters Filtros opcionais: status, modelo
     * @return array Lista de NF-e
     */
    public function getNfesByPeriod(string $start, string $end, array $filters = []): array
    {
        $where = ["DATE(n.created_at) BETWEEN :start AND :end"];
        $params = [':start' => $start, ':end' => $end];

        if (!empty($filters['status'])) {
            $where[] = "n.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['modelo'])) {
            $where[] = "n.modelo = :modelo";
            $params[':modelo'] = (int) $filters['modelo'];
        }

        $whereStr = implode(' AND ', $where);

        $sql = "SELECT n.id,
                       n.numero,
                       n.serie,
                       n.modelo,
                       n.status,
                       n.natureza_op,
                       n.valor_total,
                       n.valor_produtos,
                       n.valor_desconto,
                       n.valor_frete,
                       n.dest_cnpj_cpf,
                       n.dest_nome,
                       n.dest_uf,
                       n.chave,
                       n.protocolo,
                       n.tp_emis,
                       DATE_FORMAT(n.created_at, '%d/%m/%Y %H:%i') AS created_at_fmt,
                       DATE_FORMAT(n.emitted_at, '%d/%m/%Y %H:%i') AS emitted_at_fmt,
                       n.created_at,
                       n.emitted_at,
                       n.order_id
                FROM nfe_documents n
                WHERE {$whereStr}
                ORDER BY n.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // RESUMO DE IMPOSTOS POR PERÍODO
    // ═══════════════════════════════════════════

    /**
     * Retorna resumo de impostos por período a partir dos itens das NF-e autorizadas.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array ['items' => [...], 'totals' => [...]]
     */
    public function getTaxSummary(string $start, string $end): array
    {
        // Totais agrupados por tipo de imposto
        $sql = "SELECT
                    COUNT(DISTINCT n.id) AS total_nfes,
                    COALESCE(SUM(ni.icms_valor), 0) AS total_icms,
                    COALESCE(SUM(ni.pis_valor), 0) AS total_pis,
                    COALESCE(SUM(ni.cofins_valor), 0) AS total_cofins,
                    COALESCE(SUM(ni.ipi_valor), 0) AS total_ipi,
                    COALESCE(SUM(ni.v_prod), 0) AS total_produtos,
                    COALESCE(SUM(n.valor_total), 0) AS total_notas
                FROM nfe_documents n
                INNER JOIN nfe_document_items ni ON ni.nfe_document_id = n.id
                WHERE n.status = 'autorizada'
                  AND DATE(n.emitted_at) BETWEEN :start AND :end";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        // Detalhamento por NCM (top items por volume de impostos)
        $sqlDetail = "SELECT
                        ni.ncm,
                        ni.cfop,
                        COUNT(*) AS qtd_itens,
                        COALESCE(SUM(ni.icms_valor), 0) AS icms,
                        COALESCE(SUM(ni.pis_valor), 0) AS pis,
                        COALESCE(SUM(ni.cofins_valor), 0) AS cofins,
                        COALESCE(SUM(ni.ipi_valor), 0) AS ipi,
                        COALESCE(SUM(ni.v_prod), 0) AS valor_total,
                        COALESCE(SUM(ni.icms_valor + ni.pis_valor + ni.cofins_valor + ni.ipi_valor), 0) AS total_tributos
                      FROM nfe_documents n
                      INNER JOIN nfe_document_items ni ON ni.nfe_document_id = n.id
                      WHERE n.status = 'autorizada'
                        AND DATE(n.emitted_at) BETWEEN :start AND :end
                      GROUP BY ni.ncm, ni.cfop
                      ORDER BY total_tributos DESC";

        $stmtD = $this->conn->prepare($sqlDetail);
        $stmtD->execute([':start' => $start, ':end' => $end]);
        $items = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items'  => $items,
            'totals' => $totals,
        ];
    }

    // ═══════════════════════════════════════════
    // NF-e POR CLIENTE
    // ═══════════════════════════════════════════

    /**
     * Retorna NF-e agrupadas por cliente (destinatário).
     *
     * @param string   $start      Data inicial (Y-m-d)
     * @param string   $end        Data final (Y-m-d)
     * @param int|null $customerId Filtro por cliente (null = todos)
     * @return array Lista agrupada por cliente
     */
    public function getNfesByCustomer(string $start, string $end, ?int $customerId = null): array
    {
        $where = "n.status = 'autorizada' AND DATE(n.emitted_at) BETWEEN :start AND :end";
        $params = [':start' => $start, ':end' => $end];

        if ($customerId) {
            $where .= " AND o.customer_id = :cid";
            $params[':cid'] = $customerId;
        }

        $sql = "SELECT
                    COALESCE(c.name, n.dest_nome, 'Consumidor Final') AS customer_name,
                    c.id AS customer_id,
                    n.dest_cnpj_cpf,
                    COUNT(n.id) AS total_nfes,
                    COALESCE(SUM(n.valor_total), 0) AS valor_total,
                    MIN(DATE_FORMAT(n.emitted_at, '%d/%m/%Y')) AS primeira_emissao,
                    MAX(DATE_FORMAT(n.emitted_at, '%d/%m/%Y')) AS ultima_emissao
                FROM nfe_documents n
                LEFT JOIN orders o ON n.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE {$where}
                GROUP BY COALESCE(c.id, n.dest_cnpj_cpf), customer_name, n.dest_cnpj_cpf
                ORDER BY valor_total DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // RESUMO CFOP
    // ═══════════════════════════════════════════

    /**
     * Retorna ranking de CFOPs utilizados com valores totais.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de CFOPs com totais
     */
    public function getCfopSummary(string $start, string $end): array
    {
        $sql = "SELECT
                    ni.cfop,
                    COUNT(*) AS qtd_itens,
                    COUNT(DISTINCT n.id) AS qtd_nfes,
                    COALESCE(SUM(ni.v_prod), 0) AS valor_total,
                    COALESCE(SUM(ni.icms_valor), 0) AS icms_total,
                    COALESCE(SUM(ni.icms_vbc), 0) AS icms_base_total
                FROM nfe_documents n
                INNER JOIN nfe_document_items ni ON ni.nfe_document_id = n.id
                WHERE n.status = 'autorizada'
                  AND DATE(n.emitted_at) BETWEEN :start AND :end
                GROUP BY ni.cfop
                ORDER BY valor_total DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // NF-e CANCELADAS
    // ═══════════════════════════════════════════

    /**
     * Retorna NF-e canceladas dentro de um período com motivos.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de NF-e canceladas
     */
    public function getCancelledNfes(string $start, string $end): array
    {
        $sql = "SELECT n.id,
                       n.numero,
                       n.serie,
                       n.modelo,
                       n.dest_nome,
                       n.dest_cnpj_cpf,
                       n.valor_total,
                       n.chave,
                       n.cancel_motivo,
                       n.cancel_protocolo,
                       DATE_FORMAT(n.emitted_at, '%d/%m/%Y %H:%i') AS emitted_at_fmt,
                       DATE_FORMAT(n.cancel_date, '%d/%m/%Y %H:%i') AS cancel_date_fmt,
                       n.emitted_at,
                       n.cancel_date,
                       n.order_id
                FROM nfe_documents n
                WHERE n.status = 'cancelada'
                  AND DATE(n.cancel_date) BETWEEN :start AND :end
                ORDER BY n.cancel_date DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // INUTILIZAÇÕES
    // ═══════════════════════════════════════════

    /**
     * Retorna numerações inutilizadas dentro de um período.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de inutilizações
     */
    public function getInutilizacoes(string $start, string $end): array
    {
        $sql = "SELECT n.id,
                       n.numero,
                       n.serie,
                       n.modelo,
                       n.chave,
                       n.protocolo,
                       n.natureza_op AS justificativa,
                       DATE_FORMAT(n.created_at, '%d/%m/%Y %H:%i') AS created_at_fmt,
                       n.created_at
                FROM nfe_documents n
                WHERE n.status = 'inutilizada'
                  AND DATE(n.created_at) BETWEEN :start AND :end
                ORDER BY n.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // LOGS DE COMUNICAÇÃO SEFAZ
    // ═══════════════════════════════════════════

    /**
     * Retorna logs de comunicação com a SEFAZ em um período.
     *
     * @param string      $start  Data inicial (Y-m-d)
     * @param string      $end    Data final (Y-m-d)
     * @param string|null $action Filtro por tipo de ação (null = todas)
     * @return array Lista de logs
     */
    public function getSefazLogs(string $start, string $end, ?string $action = null): array
    {
        $where = "DATE(l.created_at) BETWEEN :start AND :end";
        $params = [':start' => $start, ':end' => $end];

        if ($action) {
            $where .= " AND l.action = :action";
            $params[':action'] = $action;
        }

        $sql = "SELECT l.id,
                       l.nfe_document_id,
                       l.order_id,
                       l.action,
                       l.status,
                       l.code_sefaz,
                       l.message,
                       l.ip_address,
                       DATE_FORMAT(l.created_at, '%d/%m/%Y %H:%i:%s') AS created_at_fmt,
                       l.created_at,
                       n.numero AS nfe_numero,
                       n.serie AS nfe_serie,
                       n.modelo AS nfe_modelo,
                       COALESCE(u.name, 'Sistema') AS user_name
                FROM nfe_logs l
                LEFT JOIN nfe_documents n ON l.nfe_document_id = n.id
                LEFT JOIN users u ON l.user_id = u.id
                WHERE {$where}
                ORDER BY l.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // KPIs FISCAIS (RESUMO EXECUTIVO)
    // ═══════════════════════════════════════════

    /**
     * Retorna KPIs fiscais para resumo executivo dos relatórios.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array KPIs fiscais
     */
    public function getFiscalKpis(string $start, string $end): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_emitidas,
                    SUM(CASE WHEN status = 'autorizada' THEN 1 ELSE 0 END) AS autorizadas,
                    SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    SUM(CASE WHEN status = 'rejeitada' THEN 1 ELSE 0 END) AS rejeitadas,
                    SUM(CASE WHEN status = 'inutilizada' THEN 1 ELSE 0 END) AS inutilizadas,
                    SUM(CASE WHEN modelo = 55 THEN 1 ELSE 0 END) AS nfe_count,
                    SUM(CASE WHEN modelo = 65 THEN 1 ELSE 0 END) AS nfce_count,
                    COALESCE(SUM(CASE WHEN status = 'autorizada' THEN valor_total ELSE 0 END), 0) AS valor_autorizado,
                    COALESCE(SUM(CASE WHEN status = 'cancelada' THEN valor_total ELSE 0 END), 0) AS valor_cancelado,
                    COALESCE(AVG(CASE WHEN status = 'autorizada' THEN valor_total END), 0) AS ticket_medio
                FROM nfe_documents
                WHERE DATE(created_at) BETWEEN :start AND :end";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_emitidas' => 0, 'autorizadas' => 0, 'canceladas' => 0,
            'rejeitadas' => 0, 'inutilizadas' => 0, 'nfe_count' => 0,
            'nfce_count' => 0, 'valor_autorizado' => 0, 'valor_cancelado' => 0,
            'ticket_medio' => 0,
        ];
    }

    // ═══════════════════════════════════════════
    // LABELS LEGÍVEIS
    // ═══════════════════════════════════════════

    /**
     * Mapa de status de NF-e para labels legíveis (pt-BR).
     */
    public static function getNfeStatusLabels(): array
    {
        return [
            'rascunho'    => 'Rascunho',
            'processando' => 'Processando',
            'autorizada'  => 'Autorizada',
            'cancelada'   => 'Cancelada',
            'rejeitada'   => 'Rejeitada',
            'inutilizada' => 'Inutilizada',
            'corrigida'   => 'Corrigida (CC-e)',
            'denegada'    => 'Denegada',
        ];
    }

    /**
     * Retorna label legível de um status de NF-e.
     */
    public static function getNfeStatusLabel(string $status): string
    {
        $labels = self::getNfeStatusLabels();
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Mapa de modelo para label.
     */
    public static function getModeloLabel(int $modelo): string
    {
        $labels = [55 => 'NF-e', 65 => 'NFC-e'];
        return $labels[$modelo] ?? "Mod. {$modelo}";
    }

    /**
     * Mapa de ações de log SEFAZ para labels legíveis.
     */
    public static function getLogActionLabels(): array
    {
        return [
            'emissao'        => 'Emissão',
            'consulta'       => 'Consulta',
            'cancelamento'   => 'Cancelamento',
            'correcao'       => 'Carta de Correção',
            'inutilizacao'   => 'Inutilização',
            'contingencia'   => 'Contingência',
            'status_servico' => 'Status Serviço',
            'info'           => 'Informação',
            'error'          => 'Erro',
        ];
    }

    /**
     * Retorna label legível de uma ação de log.
     */
    public static function getLogActionLabel(string $action): string
    {
        $labels = self::getLogActionLabels();
        return $labels[$action] ?? ucfirst($action);
    }

    /**
     * Descrições legíveis dos CFOPs mais comuns.
     */
    public static function getCfopDescriptions(): array
    {
        return [
            '5101' => 'Venda de prod. do estabelecimento',
            '5102' => 'Venda de merc. adquirida de terceiros',
            '5201' => 'Devolução de compra p/ industrialização',
            '5202' => 'Devolução de compra p/ comercialização',
            '5401' => 'Venda de prod. sujeito a ST',
            '5403' => 'Venda de merc. sujeita a ST',
            '5405' => 'Venda de merc. adq. com ST',
            '5501' => 'Remessa de prod. industrializado',
            '5551' => 'Venda de bem do ativo imobilizado',
            '5910' => 'Remessa por bonificação/doação/brinde',
            '5929' => 'Lançamento p/ doc. fiscal emitido avulso',
            '5949' => 'Outra saída de mercadoria não especificada',
            '6101' => 'Venda interestadual prod. estabelecimento',
            '6102' => 'Venda interestadual merc. de terceiros',
            '6401' => 'Venda interestadual prod. sujeito a ST',
            '6403' => 'Venda interestadual merc. sujeita a ST',
            '6949' => 'Outra saída interestadual não especificada',
        ];
    }

    /**
     * Retorna descrição legível de um CFOP.
     */
    public static function getCfopDescription(string $cfop): string
    {
        $descs = self::getCfopDescriptions();
        return $descs[$cfop] ?? 'CFOP ' . $cfop;
    }

    /**
     * Retorna lista de clientes com NF-e para uso em selects.
     *
     * @return array Lista [id, name]
     */
    public function getCustomersWithNfe(): array
    {
        $sql = "SELECT DISTINCT c.id, c.name
                FROM customers c
                INNER JOIN orders o ON o.customer_id = c.id
                INNER JOIN nfe_documents n ON n.order_id = o.id
                WHERE n.status = 'autorizada'
                ORDER BY c.name ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // DASHBOARD FISCAL — KPIs e GRÁFICOS
    // ═══════════════════════════════════════════

    /**
     * Retorna NF-e emitidas por mês (últimos N meses) para gráfico de barras.
     *
     * @param int $months Quantidade de meses (padrão 12)
     * @return array [['month' => 'Jan/26', 'autorizadas' => int, 'canceladas' => int, 'rejeitadas' => int, 'valor' => float], ...]
     */
    public function getNfesByMonth(int $months = 12): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') AS ym,
                    DATE_FORMAT(created_at, '%b/%y') AS month_label,
                    SUM(CASE WHEN status = 'autorizada' THEN 1 ELSE 0 END) AS autorizadas,
                    SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    SUM(CASE WHEN status = 'rejeitada' THEN 1 ELSE 0 END) AS rejeitadas,
                    COALESCE(SUM(CASE WHEN status = 'autorizada' THEN valor_total ELSE 0 END), 0) AS valor
                FROM nfe_documents
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY ym, month_label
                ORDER BY ym ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna distribuição por status (para gráfico de pizza).
     *
     * @return array [['status' => string, 'count' => int, 'valor' => float], ...]
     */
    public function getStatusDistribution(): array
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) AS count,
                    COALESCE(SUM(valor_total), 0) AS valor
                FROM nfe_documents
                GROUP BY status
                ORDER BY count DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna top N CFOPs mais utilizados.
     *
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getTopCfops(int $limit = 5): array
    {
        $sql = "SELECT 
                    ni.cfop,
                    COUNT(*) AS qtd_itens,
                    COALESCE(SUM(ni.v_prod), 0) AS valor_total
                FROM nfe_document_items ni
                INNER JOIN nfe_documents n ON ni.nfe_document_id = n.id
                WHERE n.status = 'autorizada'
                GROUP BY ni.cfop
                ORDER BY qtd_itens DESC
                LIMIT :lim";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna top N clientes com maior faturamento fiscal.
     *
     * @param int $limit Limite de resultados
     * @return array
     */
    public function getTopCustomers(int $limit = 5): array
    {
        $sql = "SELECT 
                    COALESCE(c.name, n.dest_nome, 'Consumidor Final') AS customer_name,
                    COUNT(n.id) AS total_nfes,
                    COALESCE(SUM(n.valor_total), 0) AS valor_total
                FROM nfe_documents n
                LEFT JOIN orders o ON n.order_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE n.status = 'autorizada'
                GROUP BY customer_name
                ORDER BY valor_total DESC
                LIMIT :lim";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna alertas fiscais ativos.
     *
     * @return array Lista de alertas [['type' => string, 'title' => string, 'message' => string, 'severity' => string], ...]
     */
    public function getFiscalAlerts(): array
    {
        $alerts = [];

        // 1. Certificado próximo de expirar
        try {
            $q = $this->conn->prepare("SELECT certificate_expiry FROM nfe_credentials WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $q->execute();
            $expiry = $q->fetchColumn();
            if ($expiry) {
                $days = (int) ((strtotime($expiry) - time()) / 86400);
                if ($days <= 0) {
                    $alerts[] = ['type' => 'cert_expired', 'title' => 'Certificado Expirado', 'message' => "O certificado digital expirou em {$expiry}. Emissão de NF-e bloqueada.", 'severity' => 'danger'];
                } elseif ($days <= 7) {
                    $alerts[] = ['type' => 'cert_expiring', 'title' => 'Certificado Expira em Breve', 'message' => "O certificado digital expira em {$days} dia(s) ({$expiry}).", 'severity' => 'danger'];
                } elseif ($days <= 30) {
                    $alerts[] = ['type' => 'cert_expiring', 'title' => 'Certificado Expira em Breve', 'message' => "O certificado digital expira em {$days} dia(s) ({$expiry}).", 'severity' => 'warning'];
                }
            }
        } catch (\Throwable $e) { /* ignora */ }

        // 2. NF-e processando há muito tempo (> 30 minutos)
        try {
            $q = $this->conn->prepare("SELECT COUNT(*) FROM nfe_documents WHERE status = 'processando' AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            $q->execute();
            $stuck = (int) $q->fetchColumn();
            if ($stuck > 0) {
                $alerts[] = ['type' => 'stuck_processing', 'title' => 'NF-e Processando', 'message' => "{$stuck} NF-e(s) em processamento há mais de 30 minutos. Verifique a comunicação com a SEFAZ.", 'severity' => 'warning'];
            }
        } catch (\Throwable $e) { /* ignora */ }

        // 3. Gaps na numeração (séries com saltos)
        try {
            $q = $this->conn->prepare("
                SELECT serie, MAX(numero) AS max_num, COUNT(*) AS total
                FROM nfe_documents 
                WHERE status IN ('autorizada', 'cancelada')
                GROUP BY serie
            ");
            $q->execute();
            $series = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($series as $s) {
                $expected = (int) $s['max_num'];
                $actual = (int) $s['total'];
                if ($expected > 0 && $actual < $expected && ($expected - $actual) > 1) {
                    $gap = $expected - $actual;
                    $alerts[] = ['type' => 'number_gap', 'title' => "Gap na Série {$s['serie']}", 'message' => "Possível gap de {$gap} numeração(ões) na série {$s['serie']}. Considere inutilizar números faltantes.", 'severity' => 'info'];
                }
            }
        } catch (\Throwable $e) { /* ignora */ }

        // 4. Taxa de rejeição alta (últimos 30 dias)
        try {
            $q = $this->conn->prepare("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'rejeitada' THEN 1 ELSE 0 END) AS rejeitadas
                FROM nfe_documents
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $q->execute();
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['total'] >= 5) {
                $taxa = round(((int)$row['rejeitadas'] / (int)$row['total']) * 100, 1);
                if ($taxa >= 10) {
                    $alerts[] = ['type' => 'high_rejection', 'title' => 'Alta Taxa de Rejeição', 'message' => "Taxa de rejeição de {$taxa}% nos últimos 30 dias ({$row['rejeitadas']} de {$row['total']}).", 'severity' => 'warning'];
                }
            }
        } catch (\Throwable $e) { /* ignora */ }

        return $alerts;
    }

    /**
     * Retorna resumo dos totais de impostos (últimos 12 meses).
     *
     * @return array ['total_icms' => float, 'total_pis' => float, 'total_cofins' => float, 'total_ipi' => float]
     */
    public function getTotalTaxes12Months(): array
    {
        $sql = "SELECT 
                    COALESCE(SUM(valor_icms), 0) AS total_icms,
                    COALESCE(SUM(valor_pis), 0) AS total_pis,
                    COALESCE(SUM(valor_cofins), 0) AS total_cofins,
                    COALESCE(SUM(valor_ipi), 0) AS total_ipi,
                    COALESCE(SUM(valor_tributos_aprox), 0) AS total_tributos_aprox
                FROM nfe_documents
                WHERE status = 'autorizada'
                  AND emitted_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_icms' => 0, 'total_pis' => 0, 'total_cofins' => 0,
            'total_ipi' => 0, 'total_tributos_aprox' => 0,
        ];
    }

    // ═══════════════════════════════════════════
    // RELATÓRIO DE CARTAS DE CORREÇÃO (CC-e)
    // ═══════════════════════════════════════════

    /**
     * Retorna histórico de Cartas de Correção num período (FASE4-02).
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array Lista de CC-e com dados da NF-e referenciada
     */
    public function getCorrectionHistory(string $start, string $end): array
    {
        $sql = "SELECT 
                    ch.id,
                    ch.nfe_document_id,
                    ch.seq_evento,
                    ch.texto_correcao,
                    ch.protocolo,
                    ch.code_sefaz AS c_stat,
                    ch.motivo_sefaz AS x_motivo,
                    DATE_FORMAT(ch.created_at, '%d/%m/%Y %H:%i') AS created_at_fmt,
                    ch.created_at,
                    n.numero,
                    n.serie,
                    n.chave,
                    n.dest_nome,
                    n.dest_cnpj_cpf,
                    n.valor_total,
                    COALESCE(u.name, 'Sistema') AS user_name
                FROM nfe_correction_history ch
                INNER JOIN nfe_documents n ON ch.nfe_document_id = n.id
                LEFT JOIN users u ON ch.user_id = u.id
                WHERE DATE(ch.created_at) BETWEEN :start AND :end
                ORDER BY ch.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna contagem de CC-e por mês (para gráfico).
     *
     * @param int $months Quantidade de meses
     * @return array
     */
    public function getCorrectionsByMonth(int $months = 12): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(ch.created_at, '%Y-%m') AS ym,
                    DATE_FORMAT(ch.created_at, '%b/%y') AS month_label,
                    COUNT(*) AS total
                FROM nfe_correction_history ch
                WHERE ch.created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY ym, month_label
                ORDER BY ym ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════
    // FASE5-06: LIVRO DE REGISTRO DE SAÍDAS
    // ═══════════════════════════════════════════

    /**
     * Retorna dados para o Livro de Registro de Saídas.
     * Agrupa NF-e autorizadas por CFOP, com colunas de BC ICMS, ICMS, IPI, etc.
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array ['items' => [...], 'totals_by_cfop' => [...], 'total_geral' => [...]]
     */
    public function getLivroSaidas(string $start, string $end): array
    {
        // Detalhamento por NF-e
        $sql = "SELECT 
                    n.id AS nfe_id,
                    n.numero,
                    n.serie,
                    n.modelo,
                    n.chave,
                    DATE_FORMAT(n.emitted_at, '%d/%m/%Y') AS data_emissao,
                    n.emitted_at,
                    n.dest_nome,
                    n.dest_cnpj_cpf,
                    n.dest_uf,
                    n.dest_ie,
                    n.valor_total,
                    n.valor_produtos,
                    n.valor_desconto,
                    n.valor_frete,
                    n.valor_icms,
                    n.valor_pis,
                    n.valor_cofins,
                    n.valor_ipi,
                    n.valor_tributos_aprox,
                    n.order_id,
                    GROUP_CONCAT(DISTINCT ni.cfop ORDER BY ni.cfop) AS cfops,
                    GROUP_CONCAT(DISTINCT ni.icms_cst ORDER BY ni.icms_cst) AS csts,
                    COALESCE(SUM(ni.icms_vbc), 0) AS bc_icms_total,
                    COALESCE(SUM(ni.icms_valor), 0) AS icms_total_items,
                    COALESCE(SUM(ni.pis_valor), 0) AS pis_total_items,
                    COALESCE(SUM(ni.cofins_valor), 0) AS cofins_total_items,
                    COALESCE(SUM(ni.ipi_valor), 0) AS ipi_total_items
                FROM nfe_documents n
                LEFT JOIN nfe_document_items ni ON ni.nfe_document_id = n.id
                WHERE n.status = 'autorizada'
                  AND n.tp_emis NOT IN (0)
                  AND DATE(n.emitted_at) BETWEEN :start AND :end
                GROUP BY n.id
                ORDER BY n.emitted_at ASC, n.numero ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Totais por CFOP
        $sqlCfop = "SELECT 
                        ni.cfop,
                        COUNT(DISTINCT n.id) AS qtd_nfes,
                        COALESCE(SUM(ni.v_prod), 0) AS valor_contabil,
                        COALESCE(SUM(ni.icms_vbc), 0) AS bc_icms,
                        COALESCE(SUM(ni.icms_valor), 0) AS icms,
                        COALESCE(SUM(ni.pis_valor), 0) AS pis,
                        COALESCE(SUM(ni.cofins_valor), 0) AS cofins,
                        COALESCE(SUM(ni.ipi_valor), 0) AS ipi
                    FROM nfe_documents n
                    INNER JOIN nfe_document_items ni ON ni.nfe_document_id = n.id
                    WHERE n.status = 'autorizada'
                      AND DATE(n.emitted_at) BETWEEN :start AND :end
                    GROUP BY ni.cfop
                    ORDER BY ni.cfop ASC";

        $stmtCfop = $this->conn->prepare($sqlCfop);
        $stmtCfop->execute([':start' => $start, ':end' => $end]);
        $totalsByCfop = $stmtCfop->fetchAll(PDO::FETCH_ASSOC);

        // Total geral
        $totalGeral = [
            'qtd_nfes'      => count($items),
            'valor_contabil' => array_sum(array_column($items, 'valor_total')),
            'bc_icms'        => array_sum(array_column($items, 'bc_icms_total')),
            'icms'           => array_sum(array_column($items, 'valor_icms')),
            'pis'            => array_sum(array_column($items, 'valor_pis')),
            'cofins'         => array_sum(array_column($items, 'valor_cofins')),
            'ipi'            => array_sum(array_column($items, 'valor_ipi')),
        ];

        return [
            'items'          => $items,
            'totals_by_cfop' => $totalsByCfop,
            'total_geral'    => $totalGeral,
        ];
    }

    // ═══════════════════════════════════════════
    // FASE5-07: LIVRO DE REGISTRO DE ENTRADAS
    // ═══════════════════════════════════════════

    /**
     * Retorna dados para o Livro de Registro de Entradas.
     * Baseado nos documentos recebidos via DistDFe (nfe_received_documents).
     *
     * @param string $start Data inicial (Y-m-d)
     * @param string $end   Data final (Y-m-d)
     * @return array ['items' => [...], 'total_geral' => [...]]
     */
    public function getLivroEntradas(string $start, string $end): array
    {
        $sql = "SELECT 
                    rd.id,
                    rd.chave,
                    rd.numero,
                    rd.serie,
                    rd.modelo,
                    DATE_FORMAT(rd.data_emissao, '%d/%m/%Y') AS data_emissao_fmt,
                    rd.data_emissao,
                    rd.emit_cnpj,
                    rd.emit_nome,
                    rd.emit_uf,
                    rd.emit_ie,
                    rd.valor_total,
                    rd.valor_icms,
                    rd.valor_pis,
                    rd.valor_cofins,
                    rd.valor_ipi,
                    rd.cfop_predominante,
                    rd.manifestation_status,
                    DATE_FORMAT(rd.created_at, '%d/%m/%Y %H:%i') AS recebido_em
                FROM nfe_received_documents rd
                WHERE rd.manifestation_status IN ('ciencia', 'confirmada')
                  AND DATE(rd.data_emissao) BETWEEN :start AND :end
                ORDER BY rd.data_emissao ASC, rd.numero ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Total geral
        $totalGeral = [
            'qtd_docs'       => count($items),
            'valor_contabil' => array_sum(array_column($items, 'valor_total')),
            'icms'           => array_sum(array_column($items, 'valor_icms')),
            'pis'            => array_sum(array_column($items, 'valor_pis')),
            'cofins'         => array_sum(array_column($items, 'valor_cofins')),
            'ipi'            => array_sum(array_column($items, 'valor_ipi')),
        ];

        return [
            'items'       => $items,
            'total_geral' => $totalGeral,
        ];
    }
}
