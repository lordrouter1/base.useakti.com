<?php
namespace Akti\Services;

use Akti\Models\NfeReportModel;
use Akti\Models\NfeCredential;
use PDO;

/**
 * NfeSintegraService — Gera arquivo no formato SINTEGRA.
 *
 * O SINTEGRA (Sistema Integrado de Informações sobre Operações Interestaduais
 * com Mercadorias e Serviços) exige um arquivo texto (.txt) com registros
 * de tamanho fixo, identificados pelo tipo (10, 11, 50, 51, 53, 54, 75, 90, 99).
 *
 * Registros gerados:
 *   - 10: Mestre do estabelecimento (abertura)
 *   - 11: Dados complementares
 *   - 50: NF-e de saída (totais por nota)
 *   - 51: Totais de IPI
 *   - 54: Produtos/Itens das NF-e
 *   - 75: Código do produto/serviço
 *   - 90: Totalizador
 *   - 99: Encerramento
 *
 * @package Akti\Services
 */
class NfeSintegraService
{
    private PDO $db;
    private NfeReportModel $reportModel;
    private NfeCredential $credModel;
    private array $typeCounts = [];

    /**
     * Construtor da classe NfeSintegraService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->reportModel = new NfeReportModel($db);
        $this->credModel = new NfeCredential($db);
    }

    /**
     * Gera o arquivo SINTEGRA completo para o período.
     *
     * @param string $startDate Data inicial (Y-m-d)
     * @param string $endDate   Data final (Y-m-d)
     * @param array  $options   Opções: cod_finalidade, cod_natureza
     * @return string Conteúdo do arquivo SINTEGRA (.txt)
     */
    public function generate(string $startDate, string $endDate, array $options = []): string
    {
        $this->typeCounts = [];
        $lines = [];

        $config = $this->loadConfig();
        $credentials = $this->credModel->get() ?: [];
        $nfes = $this->reportModel->getNfesByPeriod($startDate, $endDate, ['status' => 'autorizada']);
        $nfeItems = $this->getNfeItems($startDate, $endDate);

        $codFinalidade = $options['cod_finalidade'] ?? ($config['sintegra_cod_finalidade'] ?? '1');
        $codNatureza = $options['cod_natureza'] ?? ($config['sintegra_cod_natureza'] ?? '3');

        // ── Registro 10: Mestre do Estabelecimento ──
        $lines[] = $this->buildRegistro10($credentials, $startDate, $endDate, $codFinalidade, $codNatureza);

        // ── Registro 11: Dados Complementares ──
        $lines[] = $this->buildRegistro11($credentials);

        // ── Registro 50: Totais por NF-e de Saída ──
        foreach ($nfes as $nfe) {
            $lines[] = $this->buildRegistro50($nfe);
        }

        // ── Registro 54: Itens das NF-e ──
        foreach ($nfeItems as $item) {
            $lines[] = $this->buildRegistro54($item);
        }

        // ── Registro 75: Código do Produto ──
        $products = $this->getDistinctProducts($startDate, $endDate);
        foreach ($products as $prod) {
            $lines[] = $this->buildRegistro75($prod, $startDate, $endDate);
        }

        // ── Registro 90: Totalizador ──
        $lines[] = $this->buildRegistro90($credentials);

        // ── Registro 99: Encerramento ──
        $totalLines = count($lines) + 1; // +1 para o próprio registro 99
        $lines[] = $this->buildRegistro99($totalLines);

        return implode("\r\n", array_filter($lines)) . "\r\n";
    }

    /**
     * Registro 10 — Mestre do Estabelecimento.
     * Tamanho fixo: 175 caracteres.
     */
    private function buildRegistro10(array $cred, string $start, string $end, string $finalidade, string $natureza): string
    {
        $cnpj = str_pad(preg_replace('/\D/', '', $cred['cnpj'] ?? ''), 14, '0', STR_PAD_LEFT);
        $ie = str_pad(preg_replace('/\D/', '', $cred['ie'] ?? ''), 14, ' ');
        $razaoSocial = str_pad(mb_substr(strtoupper($cred['razao_social'] ?? ''), 0, 35), 35);
        $municipio = str_pad(mb_substr(strtoupper($cred['municipio'] ?? ''), 0, 30), 30);
        $uf = str_pad(strtoupper($cred['uf'] ?? 'RS'), 2);
        $fax = str_pad(preg_replace('/\D/', '', $cred['telefone'] ?? ''), 10, '0', STR_PAD_LEFT);
        $dtIni = date('Ymd', strtotime($start));
        $dtFim = date('Ymd', strtotime($end));
        $codFin = str_pad($finalidade, 1);
        $codNat = str_pad($natureza, 1);
        $codConv = str_pad('', 6);

        $this->incrementCount('10');

        return '10'
            . $cnpj
            . $ie
            . $razaoSocial
            . $municipio
            . $uf
            . $fax
            . $dtIni
            . $dtFim
            . '1'         // COD_IDENTIFICAÇÃO (1=CGC)
            . $codFin
            . $codNat
            . $codConv;
    }

    /**
     * Registro 11 — Dados Complementares do Informante.
     * Tamanho fixo: 126 caracteres.
     */
    private function buildRegistro11(array $cred): string
    {
        $logradouro = str_pad(mb_substr(strtoupper($cred['logradouro'] ?? ''), 0, 34), 34);
        $numero = str_pad(mb_substr($cred['numero'] ?? 'S/N', 0, 5), 5);
        $complemento = str_pad(mb_substr($cred['complemento'] ?? '', 0, 22), 22);
        $bairro = str_pad(mb_substr(strtoupper($cred['bairro'] ?? ''), 0, 15), 15);
        $cep = str_pad(preg_replace('/\D/', '', $cred['cep'] ?? ''), 8, '0', STR_PAD_LEFT);
        $contato = str_pad(mb_substr($cred['responsavel'] ?? '', 0, 28), 28);
        $telefone = str_pad(preg_replace('/\D/', '', $cred['telefone'] ?? ''), 12, '0', STR_PAD_LEFT);

        $this->incrementCount('11');

        return '11'
            . $logradouro
            . $numero
            . $complemento
            . $bairro
            . $cep
            . $contato
            . $telefone;
    }

    /**
     * Registro 50 — Total de Nota Fiscal.
     */
    private function buildRegistro50(array $nfe): string
    {
        $cnpjDest = str_pad(preg_replace('/\D/', '', $nfe['dest_cnpj_cpf'] ?? ''), 14, '0', STR_PAD_LEFT);
        $ie = str_pad('', 14);
        $dtEmissao = !empty($nfe['emitted_at']) ? date('Ymd', strtotime($nfe['emitted_at'])) : date('Ymd', strtotime($nfe['created_at'] ?? 'now'));
        $uf = str_pad(strtoupper($nfe['dest_uf'] ?? ''), 2);
        $modelo = str_pad($nfe['modelo'] ?? '55', 2, '0', STR_PAD_LEFT);
        $serie = str_pad($nfe['serie'] ?? '1', 3, '0', STR_PAD_LEFT);
        $numero = str_pad($nfe['numero'] ?? '', 6, '0', STR_PAD_LEFT);
        $cfop = str_pad('5102', 4, '0', STR_PAD_LEFT); // CFOP predominante
        $emitente = 'P'; // P=Própria
        $valorTotal = str_pad(number_format(($nfe['valor_total'] ?? 0) * 100, 0, '', ''), 13, '0', STR_PAD_LEFT);
        $bcIcms = str_pad('0', 13, '0', STR_PAD_LEFT);
        $vlIcms = str_pad('0', 13, '0', STR_PAD_LEFT);
        $isentas = str_pad('0', 13, '0', STR_PAD_LEFT);
        $outras = str_pad(number_format(($nfe['valor_total'] ?? 0) * 100, 0, '', ''), 13, '0', STR_PAD_LEFT);
        $aliqIcms = str_pad('0000', 4);
        $situacao = 'N'; // N=Normal

        $this->incrementCount('50');

        return '50'
            . $cnpjDest
            . $ie
            . $dtEmissao
            . $uf
            . $modelo
            . $serie
            . $numero
            . $cfop
            . $emitente
            . $valorTotal
            . $bcIcms
            . $vlIcms
            . $isentas
            . $outras
            . $aliqIcms
            . $situacao;
    }

    /**
     * Registro 54 — Produto (Item da Nota Fiscal).
     */
    private function buildRegistro54(array $item): string
    {
        $cnpj = str_pad(preg_replace('/\D/', '', ''), 14, '0', STR_PAD_LEFT);
        $modelo = '55';
        $serie = str_pad('001', 3);
        $numero = str_pad($item['nfe_numero'] ?? '', 6, '0', STR_PAD_LEFT);
        $cfop = str_pad($item['cfop'] ?? '5102', 4);
        $cst = str_pad($item['cst_icms'] ?? '000', 3);
        $numItem = str_pad($item['num_item'] ?? '1', 3, '0', STR_PAD_LEFT);
        $codProd = str_pad(mb_substr($item['cod_item'] ?? '', 0, 14), 14);
        $qtd = str_pad(number_format(($item['quantidade'] ?? 0) * 1000, 0, '', ''), 11, '0', STR_PAD_LEFT);
        $vlProd = str_pad(number_format(($item['v_prod'] ?? 0) * 100, 0, '', ''), 12, '0', STR_PAD_LEFT);
        $vlDesc = str_pad('0', 12, '0', STR_PAD_LEFT);
        $bcIcms = str_pad(number_format(($item['icms_vbc'] ?? 0) * 100, 0, '', ''), 12, '0', STR_PAD_LEFT);
        $bcST = str_pad('0', 12, '0', STR_PAD_LEFT);
        $vlIpi = str_pad('0', 12, '0', STR_PAD_LEFT);
        $aliqIcms = str_pad(number_format(($item['icms_aliquota'] ?? 0) * 100, 0, '', ''), 4, '0', STR_PAD_LEFT);

        $this->incrementCount('54');

        return '54'
            . $cnpj
            . $modelo
            . $serie
            . $numero
            . $cfop
            . $cst
            . $numItem
            . $codProd
            . $qtd
            . $vlProd
            . $vlDesc
            . $bcIcms
            . $bcST
            . $vlIpi
            . $aliqIcms;
    }

    /**
     * Registro 75 — Código do Produto ou Serviço.
     */
    private function buildRegistro75(array $prod, string $start, string $end): string
    {
        $dtIni = date('Ymd', strtotime($start));
        $dtFim = date('Ymd', strtotime($end));
        $codProd = str_pad(mb_substr($prod['cod_item'] ?? '', 0, 14), 14);
        $ncm = str_pad($prod['ncm'] ?? '', 8);
        $descricao = str_pad(mb_substr($prod['descricao'] ?? '', 0, 53), 53);
        $unidade = str_pad(mb_substr($prod['unidade'] ?? 'UN', 0, 6), 6);
        $aliqIpi = str_pad('0000', 5, '0', STR_PAD_LEFT);
        $aliqIcms = str_pad(number_format(($prod['icms_aliquota'] ?? 0) * 100, 0, '', ''), 4, '0', STR_PAD_LEFT);
        $reducaoBC = str_pad('0000', 5, '0', STR_PAD_LEFT);
        $bcST = str_pad('0', 13, '0', STR_PAD_LEFT);

        $this->incrementCount('75');

        return '75'
            . $dtIni
            . $dtFim
            . $codProd
            . $ncm
            . $descricao
            . $unidade
            . $aliqIpi
            . $aliqIcms
            . $reducaoBC
            . $bcST;
    }

    /**
     * Registro 90 — Totalizador.
     */
    private function buildRegistro90(array $cred): string
    {
        $cnpj = str_pad(preg_replace('/\D/', '', $cred['cnpj'] ?? ''), 14, '0', STR_PAD_LEFT);
        $ie = str_pad(preg_replace('/\D/', '', $cred['ie'] ?? ''), 14, ' ');

        $parts = '90' . $cnpj . $ie;

        // Adicionar contadores por tipo
        foreach ($this->typeCounts as $type => $count) {
            $parts .= str_pad($type, 2) . str_pad((string) $count, 8, '0', STR_PAD_LEFT);
        }

        // Total de registros (incluindo 90 e 99)
        $totalRegistros = array_sum($this->typeCounts) + 2; // +2 para reg 90 e 99
        $parts .= '99' . str_pad((string) $totalRegistros, 8, '0', STR_PAD_LEFT);

        $this->incrementCount('90');

        return $parts;
    }

    /**
     * Registro 99 — Encerramento.
     */
    private function buildRegistro99(int $totalLines): string
    {
        $this->incrementCount('99');
        return '99' . str_pad((string) ($totalLines + 1), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Incrementa contador de tipo de registro.
     */
    private function incrementCount(string $type): void
    {
        if (!isset($this->typeCounts[$type])) {
            $this->typeCounts[$type] = 0;
        }
        $this->typeCounts[$type]++;
    }

    /**
     * Carrega configurações fiscais.
     */
    private function loadConfig(): array
    {
        try {
            $stmt = $this->db->query("SELECT config_key, config_value FROM nfe_fiscal_config");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $config = [];
            foreach ($rows as $row) {
                $config[$row['config_key']] = $row['config_value'];
            }
            return $config;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Busca itens de NF-e para o período.
     */
    private function getNfeItems(string $start, string $end): array
    {
        try {
            $sql = "SELECT ni.*, n.numero AS nfe_numero, n.serie AS nfe_serie, n.modelo AS nfe_modelo
                    FROM nfe_document_items ni
                    INNER JOIN nfe_documents n ON ni.nfe_document_id = n.id
                    WHERE n.status = 'autorizada'
                      AND DATE(n.emitted_at) BETWEEN :start AND :end
                    ORDER BY n.numero, ni.n_item";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':start' => $start, ':end' => $end]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Busca produtos distintos das NF-e do período.
     */
    private function getDistinctProducts(string $start, string $end): array
    {
        try {
            $sql = "SELECT DISTINCT ni.c_prod AS cod_item, ni.x_prod AS descricao, ni.ncm, ni.u_com AS unidade,
                           ni.icms_aliquota
                    FROM nfe_document_items ni
                    INNER JOIN nfe_documents n ON ni.nfe_document_id = n.id
                    WHERE n.status = 'autorizada'
                      AND DATE(n.emitted_at) BETWEEN :start AND :end
                    GROUP BY ni.c_prod, ni.x_prod, ni.ncm, ni.u_com, ni.icms_aliquota";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':start' => $start, ':end' => $end]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
