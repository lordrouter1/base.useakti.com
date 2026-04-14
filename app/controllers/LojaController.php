<?php

namespace Akti\Controllers;

use Akti\Models\SiteBuilder;
use Akti\Models\Product;
use Akti\Models\Category;
use Akti\Models\Subcategory;
use Akti\Services\TwigRenderer;

/**
 * Controller público da Loja.
 *
 * Responsável por renderizar as páginas fixas da loja usando Twig,
 * com configurações de tema do Site Builder e catálogo de produtos.
 * Páginas fixas: home, produtos, contato, carrinho, perfil.
 */
class LojaController extends BaseController
{
    private SiteBuilder $siteBuilder;
    private TwigRenderer $twig;
    private int $tenantId;
    private array $settings;
    private string $shopName;

    public function __construct(\PDO $db, SiteBuilder $siteBuilder, TwigRenderer $twig, int $tenantId)
    {
        $this->db = $db;
        $this->siteBuilder = $siteBuilder;
        $this->twig = $twig;
        $this->tenantId = $tenantId;
        $this->settings = $this->siteBuilder->getSettings($tenantId);
        $this->shopName = $this->settings['shop_name']
            ?? $_SESSION['tenant']['company_name']
            ?? $_SESSION['tenant']['name']
            ?? 'Minha Loja';
    }

    /**
     * Home page da loja.
     */
    public function home(): void
    {
        $count = (int) ($this->settings['featured_products_count'] ?? 8);
        $products = $this->getFeaturedProducts($count);

        echo $this->twig->render('pages/home.html.twig', $this->buildContext([
            'products' => $products,
        ]));
    }

    /**
     * Catálogo de produtos.
     */
    public function collection(): void
    {
        $currentPage = max(1, (int) ($_GET['p'] ?? 1));
        $perPage = 20;
        $categoryId = !empty($_GET['category']) ? (int) $_GET['category'] : null;
        $subcategoryId = !empty($_GET['subcategory']) ? (int) $_GET['subcategory'] : null;
        $search = !empty($_GET['q']) ? trim($_GET['q']) : null;

        $productModel = new Product($this->db);
        $result = $productModel->readPaginatedFiltered($currentPage, $perPage, $categoryId, $search, $subcategoryId, true);
        $totalPages = (int) ceil(($result['total'] ?? 0) / $perPage);

        $categoryName = null;
        if ($categoryId) {
            $catModel = new Category($this->db);
            $cat = $catModel->getCategory($categoryId);
            $categoryName = $cat['name'] ?? null;
        }

        echo $this->twig->render('pages/collection.html.twig', $this->buildContext([
            'products'       => $result['data'] ?? [],
            'current_page'   => $currentPage,
            'total_pages'    => $totalPages,
            'total'          => $result['total'] ?? 0,
            'search'         => $search,
            'category_id'    => $categoryId,
            'subcategory_id' => $subcategoryId,
            'category_name'  => $categoryName,
        ]));
    }

    /**
     * Página de produto individual.
     */
    public function product(string $slug): void
    {
        $productModel = new Product($this->db);

        $product = null;
        if (method_exists($productModel, 'readBySlug')) {
            $product = $productModel->readBySlug($slug);
        } else {
            $productId = (int) $slug;
            if ($productId > 0 && method_exists($productModel, 'readOne')) {
                $product = $productModel->readOne($productId);
            }
        }

        if (!$product) {
            $this->notFound();
            return;
        }

        // Herdar free_shipping da categoria/subcategoria
        if (!empty($product['category_id'])) {
            $catModel = new Category($this->db);
            $cat = $catModel->getCategory($product['category_id']);
            $product['category_free_shipping'] = $cat['free_shipping'] ?? 0;
        }
        if (!empty($product['subcategory_id'])) {
            $subModel = new Subcategory($this->db);
            $sub = $subModel->readOne($product['subcategory_id']);
            $product['subcategory_free_shipping'] = $sub['free_shipping'] ?? 0;
        }

        $related = $this->getFeaturedProducts(4);

        echo $this->twig->render('pages/product.html.twig', $this->buildContext([
            'product'          => $product,
            'related_products' => $related,
        ]));
    }

    /**
     * Página do carrinho de compras.
     */
    public function cart(): void
    {
        $cartItems = $_SESSION['loja_cart'] ?? [];

        echo $this->twig->render('pages/cart.html.twig', $this->buildContext([
            'cart' => [
                'items'    => $cartItems,
                'total'    => $this->calculateCartTotal($cartItems),
                'count'    => count($cartItems),
            ],
        ]));
    }

    /**
     * Página de contato.
     */
    public function contact(): void
    {
        echo $this->twig->render('pages/contact.html.twig', $this->buildContext());
    }

    /**
     * Página de perfil do cliente.
     */
    public function profile(): void
    {
        echo $this->twig->render('pages/profile.html.twig', $this->buildContext());
    }

    /**
     * API: Adicionar produto ao carrinho (AJAX POST).
     */
    public function addToCart(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        if ($productId <= 0) {
            $this->json(['success' => false, 'message' => 'Produto inválido'], 422);
            return;
        }

        $productModel = new Product($this->db);
        $product = method_exists($productModel, 'readOne')
            ? $productModel->readOne($productId)
            : null;

        if (!$product) {
            $this->json(['success' => false, 'message' => 'Produto não encontrado'], 404);
            return;
        }

        if (!isset($_SESSION['loja_cart'])) {
            $_SESSION['loja_cart'] = [];
        }

        $key = (string) $productId;
        if (isset($_SESSION['loja_cart'][$key])) {
            $_SESSION['loja_cart'][$key]['quantity'] += $quantity;
        } else {
            $_SESSION['loja_cart'][$key] = [
                'product_id' => $productId,
                'name'       => $product['name'] ?? '',
                'price'      => (float) ($product['price'] ?? 0),
                'quantity'   => $quantity,
                'image'      => $product['photo_url'] ?? $product['main_image_path'] ?? '',
            ];
        }

        $this->json([
            'success' => true,
            'cart_count' => array_sum(array_column($_SESSION['loja_cart'], 'quantity')),
        ]);
    }

    /**
     * API: Remover produto do carrinho (AJAX POST).
     */
    public function removeFromCart(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }

        $productId = (string) ($_POST['product_id'] ?? '');
        unset($_SESSION['loja_cart'][$productId]);

        $this->json([
            'success'    => true,
            'cart_count' => array_sum(array_column($_SESSION['loja_cart'] ?? [], 'quantity')),
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────

    /**
     * Monta o contexto base para todos os templates Twig.
     */
    private function buildContext(array $extra = []): array
    {
        $categories = $this->getCategories();

        $context = [
            'shop' => [
                'name'        => $this->shopName,
                'description' => $this->settings['shop_description'] ?? $_SESSION['tenant']['description'] ?? '',
                'logo'        => $this->settings['shop_logo_url'] ?? '',
                'url'         => $this->getBaseUrl(),
                'email'       => $this->settings['contact_email'] ?? '',
                'phone'       => $this->settings['contact_phone'] ?? '',
                'address'     => $this->settings['contact_address'] ?? '',
                'whatsapp'    => $this->settings['contact_whatsapp'] ?? '',
            ],
            'theme'      => $this->settings,
            'categories' => $categories,
            'menu'       => [
                ['label' => 'Início',   'url' => '/loja/'],
                ['label' => 'Produtos', 'url' => '/loja/produtos'],
                ['label' => 'Contato',  'url' => '/loja/contato'],
            ],
            'cart_count' => array_sum(array_column($_SESSION['loja_cart'] ?? [], 'quantity')),
        ];

        return array_merge($context, $extra);
    }

    /**
     * Busca produtos em destaque.
     */
    private function getFeaturedProducts(int $limit): array
    {
        try {
            $productModel = new Product($this->db);
            $result = $productModel->readPaginatedFiltered(1, $limit, null, null, null, true);
            return $result['data'] ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Calcula o total do carrinho.
     */
    private function calculateCartTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1);
        }
        return $total;
    }

    /**
     * Página 404.
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo $this->twig->render('pages/home.html.twig', $this->buildContext([
            'not_found' => true,
        ]));
    }

    /**
     * Retorna a URL base da loja.
     */
    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    /**
     * Retorna categorias com suas subcategorias.
     */
    private function getCategories(): array
    {
        try {
            $catModel = new Category($this->db);
            $categories = $catModel->readAllVisible();
            foreach ($categories as &$cat) {
                $cat['subcategories'] = $catModel->getVisibleSubcategories($cat['id']);
            }
            unset($cat);
            return $categories;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * API: Sugestões de busca (AJAX GET).
     */
    public function searchSuggestions(): void
    {
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) {
            $this->json(['results' => []]);
            return;
        }

        $productModel = new Product($this->db);
        $result = $productModel->readPaginatedFiltered(1, 8, null, $q, null, true);
        $suggestions = [];
        foreach (($result['data'] ?? []) as $p) {
            $suggestions[] = [
                'id'       => $p['id'],
                'name'     => $p['name'],
                'price'    => (float) ($p['price'] ?? 0),
                'image'    => $p['main_image_path'] ?? $p['photo_url'] ?? '',
                'category' => $p['category_name'] ?? '',
            ];
        }

        $this->json(['results' => $suggestions]);
    }
}
