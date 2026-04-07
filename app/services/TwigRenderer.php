<?php

namespace Akti\Services;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Serviço de renderização Twig para a Loja.
 *
 * Configurado com os diretórios de template da loja e extensões
 * necessárias para renderização de páginas públicas.
 */
class TwigRenderer
{
    private Environment $twig;

    public function __construct(string $basePath)
    {
        $loader = new FilesystemLoader([
            $basePath . '/loja',
            $basePath . '/loja/templates',
        ]);
        $loader->addPath($basePath . '/loja/templates/sections', 'sections');
        $loader->addPath($basePath . '/loja/templates/snippets', 'snippets');
        $loader->addPath($basePath . '/loja/templates/pages', 'pages');
        $loader->addPath($basePath . '/loja/layouts', 'layouts');

        $cachePath = $basePath . '/storage/cache/twig';
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0775, true);
        }

        $this->twig = new Environment($loader, [
            'cache'       => $cachePath,
            'auto_reload' => true,
            'autoescape'  => 'html',
            'debug'       => (getenv('APP_ENV') === 'development'),
        ]);

        if (getenv('APP_ENV') === 'development') {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }
    }

    /**
     * Renderiza um template Twig e retorna o HTML.
     *
     * @param string $template Caminho do template (ex: 'pages/home.html.twig')
     * @param array  $context  Variáveis disponíveis no template
     */
    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }

    /**
     * Retorna a instância Twig para extensões customizadas.
     */
    public function getEnvironment(): Environment
    {
        return $this->twig;
    }
}
