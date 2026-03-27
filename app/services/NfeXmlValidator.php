<?php
namespace Akti\Services;

/**
 * NfeXmlValidator — Valida XML da NF-e contra schema XSD antes do envio à SEFAZ.
 *
 * Usa DOMDocument::schemaValidate() com os schemas XSD incluídos no pacote sped-nfe.
 *
 * @package Akti\Services
 */
class NfeXmlValidator
{
    /**
     * Caminhos possíveis para os schemas XSD do sped-nfe.
     */
    private const SCHEMA_PATHS = [
        'vendor/nfephp-org/sped-nfe/schemas/',
        'vendor/nfephp-org/sped-common/schemas/',
    ];

    /**
     * Valida o XML assinado contra o schema XSD da NF-e 4.00.
     *
     * @param string $xmlSigned XML assinado (antes do envio)
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public static function validate(string $xmlSigned): array
    {
        $errors = [];

        if (empty($xmlSigned)) {
            return ['valid' => false, 'errors' => ['XML vazio.']];
        }

        // Suprimir warnings do libxml para capturar como array
        $previousUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->loadXML($xmlSigned)) {
            $xmlErrors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);

            foreach ($xmlErrors as $error) {
                $errors[] = self::formatXmlError($error);
            }
            return ['valid' => false, 'errors' => $errors ?: ['Falha ao carregar o XML.']];
        }

        // Tentar encontrar o schema XSD
        $xsdPath = self::findSchemaPath('nfe_v4.00.xsd');

        if ($xsdPath === null) {
            // Sem schema disponível — validar apenas estrutura básica
            $basicValidation = self::basicValidation($dom);
            libxml_use_internal_errors($previousUseErrors);
            return $basicValidation;
        }

        // Validar contra XSD
        if (!$dom->schemaValidate($xsdPath)) {
            $xmlErrors = libxml_get_errors();
            libxml_clear_errors();

            foreach ($xmlErrors as $error) {
                $errors[] = self::formatXmlError($error);
            }
        }

        libxml_use_internal_errors($previousUseErrors);

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validação básica de estrutura (quando XSD não está disponível).
     * Verifica campos obrigatórios mínimos da NF-e.
     *
     * @param \DOMDocument $dom
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    private static function basicValidation(\DOMDocument $dom): array
    {
        $errors = [];

        // Verificar presença do nó raiz infNFe
        $infNFe = $dom->getElementsByTagName('infNFe');
        if ($infNFe->length === 0) {
            $errors[] = 'Elemento raiz <infNFe> não encontrado no XML.';
            return ['valid' => false, 'errors' => $errors];
        }

        // Tags obrigatórias
        $requiredTags = ['ide', 'emit', 'dest', 'det', 'total', 'transp', 'pag'];
        foreach ($requiredTags as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            if ($elements->length === 0) {
                $errors[] = "Tag obrigatória <{$tag}> não encontrada no XML.";
            }
        }

        // Verificar campos da ide
        $ide = $dom->getElementsByTagName('ide');
        if ($ide->length > 0) {
            $ideNode = $ide->item(0);
            $ideRequired = ['cUF', 'cNF', 'natOp', 'mod', 'serie', 'nNF', 'dhEmi', 'tpNF', 'idDest', 'cMunFG', 'tpImp', 'tpEmis', 'tpAmb', 'finNFe', 'indFinal', 'indPres', 'procEmi', 'verProc'];
            foreach ($ideRequired as $field) {
                $nodes = $ideNode->getElementsByTagName($field);
                if ($nodes->length === 0) {
                    $errors[] = "Campo obrigatório <ide><{$field}> ausente.";
                }
            }
        }

        // Verificar CNPJ do emitente
        $emitCnpj = $dom->getElementsByTagName('CNPJ');
        if ($emitCnpj->length === 0) {
            $errors[] = 'CNPJ do emitente não encontrado.';
        }

        // Verificar se há pelo menos 1 item (det)
        $det = $dom->getElementsByTagName('det');
        if ($det->length === 0) {
            $errors[] = 'Nenhum item (det) encontrado na NF-e.';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Procura o schema XSD no sistema de arquivos.
     *
     * @param string $schemaFile Nome do arquivo XSD
     * @return string|null Caminho absoluto ou null
     */
    private static function findSchemaPath(string $schemaFile): ?string
    {
        $basePath = defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : __DIR__ . '/../../';

        foreach (self::SCHEMA_PATHS as $dir) {
            $fullPath = realpath($basePath . $dir . $schemaFile);
            if ($fullPath && file_exists($fullPath)) {
                return $fullPath;
            }

            // Tentar em subpasta PL_009_V4
            $fullPath = realpath($basePath . $dir . 'PL_009_V4/' . $schemaFile);
            if ($fullPath && file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Formata erro do libxml para string legível.
     */
    private static function formatXmlError(\LibXMLError $error): string
    {
        $levels = [
            LIBXML_ERR_WARNING => 'Aviso',
            LIBXML_ERR_ERROR   => 'Erro',
            LIBXML_ERR_FATAL   => 'Erro Fatal',
        ];

        $level = $levels[$error->level] ?? 'Erro';
        $message = trim($error->message);
        $line = $error->line;
        $col = $error->column;

        return "{$level} (linha {$line}, col {$col}): {$message}";
    }
}
