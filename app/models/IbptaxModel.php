<?php
namespace Akti\Models;

use Akti\Core\Log;
use PDO;

/**
 * Model: IbptaxModel
 * Gerencia a tabela IBPTax — alíquotas de tributos aproximados (Lei 12.741/2012).
 *
 * Fontes: Tabela IBPTax do IBPT, atualizada semestralmente.
 * Entradas: Conexão PDO ($db), NCM, parâmetros de importação.
 * Saídas: Arrays de alíquotas, percentuais para cálculo do vTotTrib.
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 *
 * @package Akti\Models
 */
class IbptaxModel
{
    private $conn;
    private string $table = 'tax_ibptax';

    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Busca alíquotas IBPTax por NCM.
     * Retorna a entrada vigente (ou mais recente) para o NCM informado.
     *
     * @param string      $ncm NCM de 8 dígitos
     * @param string|null $ex  Exceção do NCM (opcional)
     * @return array|null Dados de alíquotas ou null se não encontrado
     */
    public function getByNcm(string $ncm, ?string $ex = null): ?array
    {
        $ncm = preg_replace('/\D/', '', $ncm);
        if (strlen($ncm) < 8) {
            return null;
        }

        $where = "ncm = :ncm";
        $params = [':ncm' => $ncm];

        if ($ex !== null && $ex !== '') {
            $where .= " AND ex = :ex";
            $params[':ex'] = $ex;
        } else {
            $where .= " AND (ex IS NULL OR ex = '')";
        }

        // Priorizar vigência atual, depois mais recente
        $sql = "SELECT * FROM {$this->table}
                WHERE {$where}
                ORDER BY 
                    CASE WHEN vigencia_fim IS NULL OR vigencia_fim >= CURDATE() THEN 0 ELSE 1 END,
                    vigencia_inicio DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Calcula o valor total de tributos aproximados para um item.
     *
     * @param string $ncm       NCM do produto
     * @param float  $valor     Valor do produto (base de cálculo)
     * @param string $origem    Origem da mercadoria (0=nacional, 1-8=importada)
     * @return array ['vTotTrib' => float, 'federal' => float, 'estadual' => float, 'municipal' => float, 'fonte' => string, 'found' => bool]
     */
    public function calculateTaxApprox(string $ncm, float $valor, string $origem = '0'): array
    {
        $ibptax = $this->getByNcm($ncm);

        if (!$ibptax) {
            return [
                'vTotTrib'  => 0.00,
                'federal'   => 0.00,
                'estadual'  => 0.00,
                'municipal' => 0.00,
                'fonte'     => '',
                'found'     => false,
            ];
        }

        // Determinar se usa alíquota nacional ou importados
        $isImported = in_array((string) $origem, ['1', '2', '3', '6', '7', '8']);
        $aliqFederal = $isImported
            ? (float) $ibptax['aliq_importados']
            : (float) $ibptax['aliq_nacional'];
        $aliqEstadual  = (float) $ibptax['aliq_estadual'];
        $aliqMunicipal = (float) $ibptax['aliq_municipal'];

        $vFederal   = round($valor * $aliqFederal / 100, 2);
        $vEstadual  = round($valor * $aliqEstadual / 100, 2);
        $vMunicipal = round($valor * $aliqMunicipal / 100, 2);
        $vTotTrib   = round($vFederal + $vEstadual + $vMunicipal, 2);

        $fonte = sprintf(
            'IBPTax %s (%s)',
            $ibptax['versao'] ?? '',
            $ibptax['fonte'] ?? 'IBPT'
        );

        return [
            'vTotTrib'  => $vTotTrib,
            'federal'   => $vFederal,
            'estadual'  => $vEstadual,
            'municipal' => $vMunicipal,
            'fonte'     => trim($fonte),
            'found'     => true,
        ];
    }

    /**
     * Gera a mensagem de tributos aproximados para infAdic (Lei 12.741).
     *
     * @param float  $vTotTribTotal Valor total de tributos aproximados de toda a NF-e
     * @param string $fonte         Fonte dos dados (IBPTax versão X)
     * @return string Mensagem formatada
     */
    public static function buildTributosMensagem(float $vTotTribTotal, string $fonte = ''): string
    {
        if ($vTotTribTotal <= 0) {
            return '';
        }

        $msg = sprintf(
            'Val. Aprox. Tributos R$ %s (%s) — Fonte: %s — Lei 12.741/2012',
            number_format($vTotTribTotal, 2, ',', '.'),
            number_format($vTotTribTotal, 2, ',', '.'),
            $fonte ?: 'IBPT'
        );

        return $msg;
    }

    /**
     * Importa dados da tabela IBPTax a partir de um arquivo CSV.
     * Formato esperado: NCM;Ex;Tipo;Descrição;AliqNac;AliqImp;AliqEst;AliqMun;VigInicio;VigFim;Versão;Fonte
     *
     * @param string $csvPath Caminho do arquivo CSV
     * @return array ['imported' => int, 'errors' => int, 'total' => int]
     */
    public function importFromCsv(string $csvPath): array
    {
        if (!file_exists($csvPath) || !is_readable($csvPath)) {
            throw new \RuntimeException("Arquivo CSV não encontrado ou ilegível: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Não foi possível abrir o arquivo CSV: {$csvPath}");
        }

        $imported = 0;
        $errors = 0;
        $total = 0;

        // Pular cabeçalho
        fgets($handle);

        $sql = "INSERT INTO {$this->table}
                (ncm, ex, tipo, descricao, aliq_nacional, aliq_importados, aliq_estadual, aliq_municipal,
                 vigencia_inicio, vigencia_fim, versao, fonte)
                VALUES (:ncm, :ex, :tipo, :desc, :aliq_nac, :aliq_imp, :aliq_est, :aliq_mun,
                        :vig_ini, :vig_fim, :versao, :fonte)
                ON DUPLICATE KEY UPDATE
                    descricao = VALUES(descricao),
                    aliq_nacional = VALUES(aliq_nacional),
                    aliq_importados = VALUES(aliq_importados),
                    aliq_estadual = VALUES(aliq_estadual),
                    aliq_municipal = VALUES(aliq_municipal),
                    vigencia_inicio = VALUES(vigencia_inicio),
                    vigencia_fim = VALUES(vigencia_fim),
                    versao = VALUES(versao),
                    fonte = VALUES(fonte)";

        $stmt = $this->conn->prepare($sql);

        $this->conn->beginTransaction();

        try {
            while (($line = fgetcsv($handle, 0, ';')) !== false) {
                $total++;

                if (count($line) < 8) {
                    $errors++;
                    continue;
                }

                try {
                    $ncm = trim($line[0] ?? '');
                    if (empty($ncm)) {
                        $errors++;
                        continue;
                    }

                    $stmt->execute([
                        ':ncm'      => $ncm,
                        ':ex'       => !empty($line[1]) ? trim($line[1]) : null,
                        ':tipo'     => (int) ($line[2] ?? 0),
                        ':desc'     => mb_substr(trim($line[3] ?? ''), 0, 500),
                        ':aliq_nac' => (float) str_replace(',', '.', $line[4] ?? '0'),
                        ':aliq_imp' => (float) str_replace(',', '.', $line[5] ?? '0'),
                        ':aliq_est' => (float) str_replace(',', '.', $line[6] ?? '0'),
                        ':aliq_mun' => (float) str_replace(',', '.', $line[7] ?? '0'),
                        ':vig_ini'  => !empty($line[8]) ? trim($line[8]) : null,
                        ':vig_fim'  => !empty($line[9]) ? trim($line[9]) : null,
                        ':versao'   => !empty($line[10]) ? trim($line[10]) : null,
                        ':fonte'    => !empty($line[11]) ? trim($line[11]) : 'IBPT',
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                }
            }

            $this->conn->commit();
        } catch (\Exception $e) {
            Log::error('IBPTAX importFromCsv rollback: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->conn->rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return [
            'imported' => $imported,
            'errors'   => $errors,
            'total'    => $total,
        ];
    }

    /**
     * Remove todos os registros da tabela IBPTax (para reimportação).
     * @return int Quantidade de registros removidos
     */
    public function truncate(): int
    {
        $count = (int) $this->conn->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
        $this->conn->exec("TRUNCATE TABLE {$this->table}");
        return $count;
    }

    /**
     * Retorna estatísticas da tabela IBPTax.
     * @return array
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total_registros,
                    MIN(vigencia_inicio) AS vigencia_inicio_min,
                    MAX(vigencia_fim) AS vigencia_fim_max,
                    MAX(versao) AS versao_mais_recente,
                    COUNT(DISTINCT ncm) AS ncms_distintos
                FROM {$this->table}";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_registros' => 0,
            'vigencia_inicio_min' => null,
            'vigencia_fim_max' => null,
            'versao_mais_recente' => null,
            'ncms_distintos' => 0,
        ];
    }
}
