<?php
/**
 * Traduções do Portal do Cliente — Português (Brasil)
 * Idioma padrão do sistema.
 *
 * @see app/services/PortalLang.php
 */
return [
    // ── Geral ──
    'portal_title'        => 'Portal do Cliente',
    'loading'             => 'Carregando...',
    'save'                => 'Salvar',
    'cancel'              => 'Cancelar',
    'back'                => 'Voltar',
    'close'               => 'Fechar',
    'confirm'             => 'Confirmar',
    'yes'                 => 'Sim',
    'no'                  => 'Não',
    'search'              => 'Buscar',
    'filter'              => 'Filtrar',
    'all'                 => 'Todos',
    'none'                => 'Nenhum',
    'actions'             => 'Ações',
    'details'             => 'Detalhes',
    'success'             => 'Sucesso',
    'error'               => 'Erro',
    'warning'             => 'Atenção',
    'info'                => 'Informação',

    // ── Login ──
    'login_title'         => 'Acesse sua conta',
    'login_email'         => 'E-mail',
    'login_password'      => 'Senha',
    'login_btn'           => 'Entrar',
    'login_magic_btn'     => 'Receber link de acesso',
    'login_magic_sent'    => 'Link de acesso enviado para o seu e-mail!',
    'login_forgot'        => 'Esqueci minha senha',
    'login_register'      => 'Criar minha conta',
    'login_error'         => 'E-mail ou senha inválidos.',
    'login_locked'        => 'Conta bloqueada. Tente novamente em :minutes minutos.',
    'login_inactive'      => 'Sua conta está desativada. Entre em contato com a empresa.',
    'login_session_expired' => 'Sua sessão expirou. Faça login novamente.',
    'login_or'            => 'ou',

    // ── Registro ──
    'register_title'      => 'Criar conta',
    'register_name'       => 'Nome completo',
    'register_email'      => 'E-mail',
    'register_phone'      => 'Telefone / WhatsApp',
    'register_document'   => 'CPF / CNPJ',
    'register_password'   => 'Criar senha',
    'register_password_confirm' => 'Confirmar senha',
    'register_btn'        => 'Criar minha conta',
    'register_success'    => 'Conta criada com sucesso! Faça login para continuar.',
    'register_email_exists' => 'Este e-mail já está cadastrado.',
    'register_password_mismatch' => 'As senhas não conferem.',
    'register_disabled'   => 'O auto-registro está desabilitado.',
    'register_has_account' => 'Já tem conta?',
    'register_login'      => 'Faça login',

    // ── Dashboard ──
    'dashboard_greeting'  => 'Olá, :name!',
    'dashboard_active_orders'    => 'Pedidos Ativos',
    'dashboard_pending_approval' => 'Aguardando Aprovação',
    'dashboard_open_installments' => 'Parcelas Abertas',
    'dashboard_open_amount'      => 'Em Aberto',
    'dashboard_recent_notifications' => 'Notificações Recentes',
    'dashboard_recent_orders'    => 'Pedidos Recentes',
    'dashboard_no_notifications' => 'Nenhuma notificação no momento.',
    'dashboard_no_orders'        => 'Você ainda não tem pedidos.',
    'dashboard_view_all'         => 'Ver todos',

    // ── Navegação Inferior ──
    'nav_home'            => 'Home',
    'nav_orders'          => 'Pedidos',
    'nav_new_order'       => 'Novo',
    'nav_financial'       => 'Financeiro',
    'nav_profile'         => 'Perfil',

    // ── Pedidos ──
    'orders_title'        => 'Meus Pedidos',
    'orders_all'          => 'Todos',
    'orders_open'         => 'Abertos',
    'orders_approval'     => 'Aprovação',
    'orders_completed'    => 'Concluídos',
    'orders_empty'        => 'Nenhum pedido encontrado.',
    'orders_items'        => ':count item(ns)',
    'orders_view'         => 'Ver',
    'orders_track'        => 'Rastrear',
    'orders_approve'      => 'Aprovar',
    'orders_forecast'     => 'Previsão: :date',

    // ── Detalhe do Pedido ──
    'order_detail_title'  => 'Pedido #:id',
    'order_timeline'      => 'Timeline de Progresso',
    'order_items'         => 'Itens do Pedido',
    'order_subtotal'      => 'Subtotal',
    'order_discount'      => 'Desconto',
    'order_total'         => 'Total',
    'order_extra_costs'   => 'Custos Extras',
    'order_installments'  => 'Parcelas',
    'order_send_message'  => 'Enviar Mensagem',
    'order_shipping'      => 'Envio',
    'order_tracking'      => 'Código de Rastreio',
    'order_notes'         => 'Observações',

    // ── Aprovação ──
    'approval_title'      => 'Aprovar Orçamento #:id',
    'approval_items'      => 'Itens do Orçamento',
    'approval_total'      => 'Total',
    'approval_company_notes' => 'Observações da empresa',
    'approval_your_notes' => 'Suas observações...',
    'approval_approve_btn' => 'Aprovar Orçamento',
    'approval_reject_btn' => 'Recusar',
    'approval_disclaimer' => 'Ao aprovar, você concorda com as condições acima. IP e data serão registrados.',
    'approval_success'    => 'Orçamento aprovado com sucesso!',
    'approval_rejected'   => 'Orçamento recusado.',
    'approval_already'    => 'Este orçamento já foi :status.',

    // ── Financeiro ──
    'financial_title'     => 'Financeiro',
    'financial_summary'   => 'Resumo',
    'financial_open'      => 'Em Aberto',
    'financial_paid'      => 'Pago',
    'financial_tab_open'  => 'Em aberto',
    'financial_tab_paid'  => 'Pagas',
    'financial_tab_all'   => 'Todas',
    'financial_empty'     => 'Nenhuma parcela encontrada.',
    'financial_due_date'  => 'Vence: :date',
    'financial_paid_at'   => 'Paga em :date',
    'financial_overdue'   => 'Atrasada',
    'financial_pending'   => 'Pendente',
    'financial_view'      => 'Ver Detalhes',
    'financial_pay'       => 'Pagar Online',

    // ── Rastreamento ──
    'tracking_title'      => 'Rastreamento',
    'tracking_status'     => 'Status',
    'tracking_code'       => 'Código',
    'tracking_carrier'    => 'Transportadora',
    'tracking_destination' => 'Destino',
    'tracking_forecast'   => 'Previsão',
    'tracking_timeline'   => 'Timeline de Envio',
    'tracking_no_code'    => 'Código de rastreio ainda não disponível.',

    // ── Mensagens ──
    'messages_title'      => 'Mensagens',
    'messages_placeholder' => 'Digite sua mensagem...',
    'messages_send'       => 'Enviar',
    'messages_empty'      => 'Nenhuma mensagem ainda. Inicie uma conversa!',

    // ── Perfil ──
    'profile_title'       => 'Meu Perfil',
    'profile_name'        => 'Nome',
    'profile_email'       => 'E-mail',
    'profile_phone'       => 'Telefone',
    'profile_document'    => 'CPF / CNPJ',
    'profile_address'     => 'Endereço',
    'profile_password'    => 'Nova senha',
    'profile_password_current' => 'Senha atual',
    'profile_password_new' => 'Nova senha',
    'profile_password_confirm' => 'Confirmar nova senha',
    'profile_save'        => 'Salvar Alterações',
    'profile_updated'     => 'Perfil atualizado com sucesso!',
    'profile_language'    => 'Idioma',
    'profile_logout'      => 'Sair',
    'profile_logout_confirm' => 'Deseja realmente sair?',

    // ── Status dos Pedidos ──
    'status_orcamento'    => 'Orçamento',
    'status_venda'        => 'Venda',
    'status_producao'     => 'Em Produção',
    'status_preparacao'   => 'Preparação',
    'status_envio'        => 'Envio/Entrega',
    'status_financeiro'   => 'Financeiro',
    'status_concluido'    => 'Concluído',
    'status_cancelado'    => 'Cancelado',
    'status_contato'      => 'Contato',

    // ── Aprovação de status ──
    'approval_status_pendente' => 'Pendente',
    'approval_status_aprovado' => 'Aprovado',
    'approval_status_recusado' => 'Recusado',

    // ── PWA / Instalação ──
    'pwa_install_title'   => 'Instalar App',
    'pwa_install_text'    => 'Instale o Portal do Cliente no seu dispositivo para acesso rápido!',
    'pwa_install_btn'     => 'Instalar',
    'pwa_install_dismiss' => 'Agora não',

    // ── Erros ──
    'error_404'           => 'Página não encontrada.',
    'error_403'           => 'Acesso negado.',
    'error_500'           => 'Erro interno. Tente novamente mais tarde.',
    'error_generic'       => 'Algo deu errado. Tente novamente.',
    'error_required'      => 'Este campo é obrigatório.',
    'error_invalid_email' => 'E-mail inválido.',

    // ── Formatos ──
    'currency_prefix'     => 'R$',
    'date_format'         => 'd/m/Y',
    'datetime_format'     => 'd/m/Y H:i',
];
