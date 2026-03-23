<?php
/**
 * Registro de todas as rotas testáveis do sistema Akti.
 *
 * Cada entrada é um array com:
 *   'route'       => Query string da rota (sem '?')
 *   'label'       => Descrição amigável para logs/relatórios
 *   'auth'        => true se exige login, false se pública
 *   'method'      => 'GET' (padrão) ou 'POST'
 *   'contains'    => String(s) esperada(s) no HTML de resposta (opcional)
 *   'admin_only'  => true se exige permissão de admin (opcional)
 *
 * ── Como adicionar novas rotas ──
 * 1. Adicione um novo item ao array abaixo.
 * 2. Execute: vendor/bin/phpunit tests
 * 3. Se o teste falhar, verifique se a rota exige parâmetros (id, etc.)
 *    e adicione-os à query string.
 *
 * ── Rotas excluídas ──
 * - Rotas POST (store, update, delete) — alteram dados, testadas separadamente.
 * - Rotas AJAX que retornam JSON — testadas separadamente.
 * - Rotas que exigem ID específico — testadas em testes unitários dos módulos.
 */

return [

    // ══════════════════════════════════════════════════════════════
    // PÁGINAS PÚBLICAS (sem autenticação)
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=login',
        'label'    => 'Login',
        'auth'     => false,
        'contains' => ['<form'],
    ],

    // ══════════════════════════════════════════════════════════════
    // PÁGINAS AUTENTICADAS — GERAIS
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '',
        'label'    => 'Home / Dashboard',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=home',
        'label'    => 'Home',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=profile',
        'label'    => 'Perfil do Usuário',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ══════════════════════════════════════════════════════════════
    // PRODUTOS
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=products',
        'label'    => 'Produtos — Listagem',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=products&action=create',
        'label'    => 'Produtos — Novo',
        'auth'     => true,
        'contains' => ['<form'],
    ],

    // ══════════════════════════════════════════════════════════════
    // CATEGORIAS
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=categories',
        'label'    => 'Categorias — Listagem',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SETORES DE PRODUÇÃO
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=sectors',
        'label'    => 'Setores de Produção',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ══════════════════════════════════════════════════════════════
    // CLIENTES
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=customers',
        'label'    => 'Clientes — Listagem',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=customers&action=create',
        'label'    => 'Clientes — Novo',
        'auth'     => true,
        'contains' => ['<form'],
    ],

    // ══════════════════════════════════════════════════════════════
    // PEDIDOS
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=orders',
        'label'    => 'Pedidos — Listagem',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=orders&action=create',
        'label'    => 'Pedidos — Novo',
        'auth'     => true,
        'contains' => ['<form'],
    ],

    [
        'route'    => '?page=orders&action=agenda',
        'label'    => 'Agenda de Contatos',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=orders&action=report',
        'label'    => 'Relatório de Pedidos',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ══════════════════════════════════════════════════════════════
    // PIPELINE (LINHA DE PRODUÇÃO)
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=pipeline',
        'label'    => 'Pipeline — Kanban',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=pipeline&action=settings',
        'label'    => 'Pipeline — Configurações',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=production_board',
        'label'    => 'Painel de Produção',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ══════════════════════════════════════════════════════════════
    // ESTOQUE
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=stock',
        'label'    => 'Estoque — Listagem',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=stock&action=warehouses',
        'label'    => 'Estoque — Armazéns',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=stock&action=entry',
        'label'    => 'Estoque — Entrada',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=stock&action=movements',
        'label'    => 'Estoque — Movimentações',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ══════════════════════════════════════════════════════════════
    // TABELAS DE PREÇO
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=price_tables',
        'label'    => 'Tabelas de Preço',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ══════════════════════════════════════════════════════════════
    // CONFIGURAÇÕES
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=settings',
        'label'    => 'Configurações',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=settings&tab=dashboard',
        'label'    => 'Configurações — Dashboard Widgets',
        'auth'     => true,
        'contains' => ['Dashboard'],
    ],

    // ══════════════════════════════════════════════════════════════
    // FINANCEIRO
    // ══════════════════════════════════════════════════════════════

    [
        'route'    => '?page=financial',
        'label'    => 'Financeiro — Pagamentos',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=financial&action=transactions',
        'label'    => 'Financeiro — Transações',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=financial_payments',
        'label'    => 'Financeiro — Pagamentos (atalho)',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    [
        'route'    => '?page=financial_transactions',
        'label'    => 'Financeiro — Transações (atalho)',
        'auth'     => true,
        'contains' => ['<html'],
    ],

    // ── Fase 3/4 — Rotas AJAX (retornam JSON, testadas via Unit tests) ──
    // ?page=financial&action=getDre — DRE (JSON)
    // ?page=financial&action=getCashflow — Fluxo de Caixa (JSON)
    // ?page=financial&action=recurringList — Recorrências (JSON)
    // ?page=financial&action=exportTransactionsCsv — Export CSV
    // ?page=financial&action=exportDreCsv — Export DRE CSV
    // ?page=financial&action=exportCashflowCsv — Export Cashflow CSV

    // ══════════════════════════════════════════════════════════════
    // USUÁRIOS / ADMIN
    // ══════════════════════════════════════════════════════════════

    [
        'route'      => '?page=users',
        'label'      => 'Usuários — Listagem',
        'auth'       => true,
        'admin_only' => true,
        'contains'   => ['<html'],
    ],

    [
        'route'      => '?page=users&action=create',
        'label'      => 'Usuários — Novo',
        'auth'       => true,
        'admin_only' => true,
        'contains'   => ['<form'],
    ],

    [
        'route'      => '?page=users&action=groups',
        'label'      => 'Grupos de Permissão',
        'auth'       => true,
        'admin_only' => true,
        'contains'   => ['<html'],
    ],

];
