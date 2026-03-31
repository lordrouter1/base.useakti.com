<?php
namespace Akti\Services;

use Akti\Core\Log;

/**
 * NfePdfGenerator — Gera DANFE (PDF) a partir do XML autorizado.
 *
 * Usa a biblioteca nfephp-org/sped-da para gerar o DANFE.
 *
 * @package Akti\Services
 */
class NfePdfGenerator
{
    /**
     * Gera o DANFE a partir do XML autorizado.
     * @param string $xmlAutorizado XML completo (procNFe)
     * @param string $outputPath    Caminho onde salvar o PDF
     * @return bool true se gerou com sucesso
     */
    public static function generate(string $xmlAutorizado, string $outputPath): bool
    {
        if (!class_exists(\NFePHP\DA\NFe\Danfe::class)) {
            // Fallback: sem sped-da, não gera PDF
            return false;
        }

        try {
            $danfe = new \NFePHP\DA\NFe\Danfe($xmlAutorizado);
            $danfe->debugMode(false);
            $danfe->creditsIntegr498('');

            $pdf = $danfe->render();

            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($outputPath, $pdf);
            return true;
        } catch (\Exception $e) {
            Log::error('NfePdfGenerator: Erro ao gerar DANFE', ['exception' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Retorna o PDF como string (para download direto).
     * @param string $xmlAutorizado
     * @return string|null PDF binary ou null se erro
     */
    public static function renderToString(string $xmlAutorizado): ?string
    {
        if (!class_exists(\NFePHP\DA\NFe\Danfe::class)) {
            return null;
        }

        try {
            $danfe = new \NFePHP\DA\NFe\Danfe($xmlAutorizado);
            $danfe->debugMode(false);
            return $danfe->render();
        } catch (\Exception $e) {
            Log::error('NfePdfGenerator: Erro ao renderizar DANFE', ['exception' => $e->getMessage()]);
            return null;
        }
    }
}
