<?php
namespace Akti\Services;

use Akti\Models\NfeReportModel;
use Akti\Models\NfeCredential;
use PDO;

/**
 * NfeSpedFiscalService — Gera arquivo SPED Fiscal (EFD ICMS/IPI).
 *
 * O SPED Fiscal (Sistema Público de Escrituração Digital) exige um arquivo
 * texto (.txt) com layout definido por blocos/registros padronizados.
 *
 * Blocos gerados:
 *   - Bloco 0: Abertura e cadastros
 *   - Bloco C: Documentos Fiscais (NF-e/NFC-e)
 *   - Bloco E: Apuração ICMS
 *   - Bloco H: Inventário (simplificado)
 *   - Bloco 9: Encerramento
 *
 * @package Akti\Services
 */
class NfeSpedFiscalService
{
    private PDO $db;
    private NfeReportModel $reportModel;
    private NfeCredential $credModel;
    private array $registroCounts = [];
    private array $lines = [];

    /**
     * Construtor da classe NfeSpedFiscalService.
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
     * Gera o arquivo SPED Fiscal completo para o período.
     *
     * @param string $startDate Data inicial (Y-m-d)
     * @param string $endDate   Data final (Y-m-d)
     * @param array  $options   Opções: finalidade, perfil, atividade
     * @return string Conteúdo do arquivo SPED (.txt)
     */
    public function generate(string $startDate, string $endDate, array $options = []): string
    {
        $this->lines = [];
        $this->registroCounts = [];

        $config = $this->loadConfig();
        $credentials = $this->credModel->get() ?: [];

        $finalidade = $options['finalidade'] ?? ($config['sped_finalidade'] ?? '0');
        $perfil = $options['perfil'] ?? ($config['sped_perfil'] ?? 'A');
        $atividade = $options['atividade'] ?? ($config['sped_atividade'] ?? '0');

        // Dados
        $nfes = $this->reportModel->getNfesByPeriod($startDate, $endDate, ['status' => 'autorizada']);
        $nfeItems = $this->getNfeItems($startDate, $endDate);
        $taxSummary = $this->reportModel->getTaxSummary($startDate, $endDate);

        // ── Bloco 0: Abertura ──
        $this->addBloco0($credentials, $startDate, $endDate, $finalidade, $perfil, $atividade);

        // ── Bloco C: Documentos Fiscais ──
        $this->addBlocoC($nfes, $nfeItems);

        // ── Bloco E: Apuração ICMS ──
        $this->addBlocoE($startDate, $endDate, $taxSummary);

        // ── Bloco H: Inventário (encerramento) ──
        $this->addBlocoH();

        // ── Bloco 9: Encerramento ──
        $this->addBloco9();

        return implode("\r\n", $this->lines) . "\r\n";
    }

    /**
     * Bloco 0 — Abertura, Identificação e Referências.
     */
    private function addBloco0(array $cred, string $start, string $end, string $finalidade, string $perfil, string $atividade): void
    {
        $cnpj = preg_replace('/\D/', '', $cred['cnpj'] ?? '');
        $ie = preg_replace('/\D/', '', $cred['ie'] ?? '');
        $uf = strtoupper($cred['uf'] ?? 'RS');
        $codMun = $cred['cod_municipio'] ?? '';
        $razaoSocial = strtoupper($cred['razao_social'] ?? '');
        $nomeFantasia = strtoupper($cred['nome_fantasia'] ?? $razaoSocial);

        // 0000 - Abertura
        $this->addLine('0000', [
            '017',                              // COD_VER (versão SPED)
            '0',                                // COD_FIN (finalidade)
            $this->formatDate($start),          // DT_INI
            $this->formatDate($end),            // DT_FIN
            $razaoSocial,                       // NOME
            $cnpj,                              // CNPJ
            '',                                 // CPF
            $uf,                                // UF
            $ie,                                // IE
            $codMun,                            // COD_MUN
            '',                                 // IM
            '',                                 // SUFRAMA
            $perfil,                            // IND_PERFIL
            $atividade,                         // IND_ATIV
        ]);

        // 0001 - Abertura Bloco 0
        $this->addLine('0001', ['0']);

        // 0005 - Dados complementares
        $this->addLine('0005', [
            $nomeFantasia,
            preg_replace('/\D/', '', $cred['cep'] ?? ''),
            $cred['logradouro'] ?? '',
            $cred['numero'] ?? '',
            $cred['complemento'] ?? '',
            $cred['bairro'] ?? '',
            preg_replace('/\D/', '', $cred['telefone'] ?? ''),
            '',                                 // FAX
            $cred['email'] ?? '',
        ]);

        // 0100 - Contabilista (simplificado)
        $this->addLine('0100', [
            'CONTADOR', '', $cnpj, '', $uf, '', $codMun, '',
            '', '', '', '', '', '',
        ]);

        // 0990 - Encerramento Bloco 0
        // Será adicionado no final
    }

    /**
     * Bloco C — Documentos Fiscais I — Mercadorias (ICMS/IPI).
     */
    private function addBlocoC(array $nfes, array $nfeItems): void
    {
        // C001 - Abertura Bloco C
        $this->addLine('C001', [empty($nfes) ? '1' : '0']);

        foreach ($nfes as $nfe) {
            $modelo = str_pad($nfe['modelo'] ?? '55', 2, '0', STR_PAD_LEFT);
            $serie = str_pad($nfe['serie'] ?? '1', 3, '0', STR_PAD_LEFT);
            $numero = str_pad($nfe['numero'] ?? '', 9, '0', STR_PAD_LEFT);
            $chave = $nfe['chave'] ?? '';
            $dtEmissao = $this->formatDate($nfe['emitted_at'] ?? $nfe['created_at'] ?? '');

            // C100 - Nota Fiscal (modelo 01, 1A, 04, 55, 65)
            $this->addLine('C100', [
                '1',                            // IND_OPER (1=Saída)
                '1',                            // IND_EMIT (1=Própria)
                '',                             // COD_PART
                $modelo,                        // COD_MOD
                '00',                           // COD_SIT (regular)
                $serie,                         // SER
                $numero,                        // NUM_DOC
                $chave,                         // CHV_NFE
                $dtEmissao,                     // DT_DOC
                $dtEmissao,                     // DT_E_S
                number_format($nfe['valor_total'] ?? 0, 2, ',', ''),    // VL_DOC
                '0',                            // IND_PGTO
                number_format($nfe['valor_desconto'] ?? 0, 2, ',', ''), // VL_DESC
                '0,00',                         // VL_ABAT_NT
                number_format($nfe['valor_produtos'] ?? 0, 2, ',', ''), // VL_MERC
                '9',                            // IND_FRT
                number_format($nfe['valor_frete'] ?? 0, 2, ',', ''),    // VL_FRT
                '0,00',                         // VL_SEG
                '0,00',                         // VL_OUT_DA
                '0,00',                         // VL_BC_ICMS
                '0,00',                         // VL_ICMS
                '0,00',                         // VL_BC_ICMS_ST
                '0,00',                         // VL_ICMS_ST
                '0,00',                         // VL_IPI
                '0,00',                         // VL_PIS
                '0,00',                         // VL_COFINS
                '0,00',                         // VL_PIS_ST
                '0,00',                         // VL_COFINS_ST
            ]);

            // C170 - Itens do documento
            $docItems = array_filter($nfeItems, function ($item) use ($nfe) {
                return ($item['nfe_document_id'] ?? 0) == ($nfe['id'] ?? 0);
            });

            foreach ($docItems as $item) {
                $this->addLine('C170', [
                    $item['num_item'] ?? '1',
                    $item['cod_item'] ?? '',
                    $item['descricao'] ?? '',
                    number_format($item['quantidade'] ?? 0, 5, ',', ''),
                    $item['unidade'] ?? 'UN',
                    number_format($item['v_prod'] ?? 0, 2, ',', ''),
                    '0,00',                     // VL_DESC
                    '0',                         // IND_MOV
                    $item['cst_icms'] ?? '',
                    $item['cfop'] ?? '',
                    $item['ncm'] ?? '',
                    number_format($item['icms_vbc'] ?? 0, 2, ',', ''),
                    number_format($item['icms_aliquota'] ?? 0, 2, ',', ''),
                    number_format($item['icms_valor'] ?? 0, 2, ',', ''),
                    '0,00', '0,00', '0,00',
                    number_format($item['pis_valor'] ?? 0, 2, ',', ''),
                    number_format($item['cofins_valor'] ?? 0, 2, ',', ''),
                    '0,00',
                ]);
            }

            // C190 - Registro analítico do documento
            $this->addLine('C190', [
                '000',                          // CST_ICMS
                $docItems ? ($docItems[array_key_first($docItems)]['cfop'] ?? '5102') : '5102',
                '0,00',                         // ALIQ_ICMS
                number_format($nfe['valor_total'] ?? 0, 2, ',', ''),
                '0,00', '0,00', '0,00', '0,00', '0,00',
                '0,00', '0,00', '0,00', '0,00',
            ]);
        }

        // C990 - Encerramento Bloco C
        $this->addLine('C990', [$this->countRegistros('C')]);
    }

    /**
     * Bloco E — Apuração do ICMS.
     */
    private function addBlocoE(string $start, string $end, array $taxSummary): void
    {
        $this->addLine('E001', ['0']);

        $totals = $taxSummary['totals'] ?? [];
        $totalIcms = number_format($totals['total_icms'] ?? 0, 2, ',', '');

        // E100 - Período de apuração ICMS
        $this->addLine('E100', [
            $this->formatDate($start),
            $this->formatDate($end),
        ]);

        // E110 - Apuração do ICMS — Operações Próprias
        $this->addLine('E110', [
            $totalIcms,       // VL_TOT_DEBITOS
            '0,00',           // VL_AJ_DEBITOS
            $totalIcms,       // VL_TOT_AJ_DEBITOS
            '0,00',           // VL_ESTORNOS_CRED
            '0,00',           // VL_TOT_CREDITOS
            '0,00',           // VL_AJ_CREDITOS
            '0,00',           // VL_TOT_AJ_CREDITOS
            '0,00',           // VL_ESTORNOS_DEB
            '0,00',           // VL_SLD_CREDOR_ANT
            $totalIcms,       // VL_SLD_APURADO
            '0,00',           // VL_TOT_DED
            $totalIcms,       // VL_ICMS_RECOLHER
            '0,00',           // VL_SLD_CREDOR_TRANSPORTAR
            '0,00',           // DEB_ESP
        ]);

        $this->addLine('E990', [$this->countRegistros('E')]);
    }

    /**
     * Bloco H — Inventário Físico.
     */
    private function addBlocoH(): void
    {
        $this->addLine('H001', ['1']); // Sem dados de inventário
        $this->addLine('H990', [$this->countRegistros('H')]);
    }

    /**
     * Bloco 9 — Controle e Encerramento.
     */
    private function addBloco9(): void
    {
        $this->addLine('9001', ['0']);

        // 9900 — Registros do arquivo
        foreach ($this->registroCounts as $reg => $count) {
            $this->addLine('9900', [$reg, (string) $count]);
        }

        // Contar 9900 e 9990 e 9999 adicionais
        $this->addLine('9900', ['9900', (string) (count($this->registroCounts) + 3)]);
        $this->addLine('9900', ['9990', '1']);
        $this->addLine('9900', ['9999', '1']);

        $totalLines = count($this->lines) + 2; // +2 para 9990 e 9999
        $this->addLine('9990', [$this->countRegistros('9')]);
        $this->addLine('9999', [(string) (count($this->lines) + 1)]);
    }

    /**
     * Adiciona uma linha SPED (pipe-delimited).
     */
    private function addLine(string $registro, array $campos = []): void
    {
        $line = '|' . $registro . '|' . implode('|', $campos) . '|';
        $this->lines[] = $line;

        // Contar por registro
        if (!isset($this->registroCounts[$registro])) {
            $this->registroCounts[$registro] = 0;
        }
        $this->registroCounts[$registro]++;
    }

    /**
     * Conta registros de um bloco.
     */
    private function countRegistros(string $bloco): string
    {
        $count = 0;
        foreach ($this->registroCounts as $reg => $c) {
            if (strpos($reg, $bloco) === 0 || ($bloco === '0' && $reg[0] === '0')) {
                $count += $c;
            }
        }
        return (string) ($count + 1); // +1 para o registro de encerramento
    }

    /**
     * Formata data para o formato SPED (ddmmaaaa).
     */
    private function formatDate(string $date): string
    {
        if (empty($date)) {
            return '';
        }
        $ts = strtotime($date);
        return $ts ? date('dmY', $ts) : '';
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
            $sql = "SELECT ni.*, ni.nfe_document_id,
                           ni.v_prod, ni.ncm, ni.cfop,
                           ni.icms_vbc, ni.icms_aliquota, ni.icms_valor,
                           ni.pis_valor, ni.cofins_valor,
                           ni.num_item, ni.cod_item, ni.descricao,
                           ni.quantidade, ni.unidade,
                           ni.cst_icms
                    FROM nfe_document_items ni
                    INNER JOIN nfe_documents n ON ni.nfe_document_id = n.id
                    WHERE n.status = 'autorizada'
                      AND DATE(n.emitted_at) BETWEEN :start AND :end
                    ORDER BY ni.nfe_document_id, ni.num_item";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':start' => $start, ':end' => $end]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
