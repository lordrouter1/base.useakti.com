<?php
/**
 * Portal do Cliente — Traducciones en Español
 *
 * @see app/services/PortalLang.php
 */
return [
    // ── General ──
    'portal_title'        => 'Portal del Cliente',
    'loading'             => 'Cargando...',
    'save'                => 'Guardar',
    'cancel'              => 'Cancelar',
    'back'                => 'Volver',
    'close'               => 'Cerrar',
    'confirm'             => 'Confirmar',
    'yes'                 => 'Sí',
    'no'                  => 'No',
    'search'              => 'Buscar',
    'filter'              => 'Filtrar',
    'all'                 => 'Todos',
    'none'                => 'Ninguno',
    'actions'             => 'Acciones',
    'details'             => 'Detalles',
    'success'             => 'Éxito',
    'error'               => 'Error',
    'warning'             => 'Atención',
    'info'                => 'Información',

    // ── Login ──
    'login_title'         => 'Accede a tu cuenta',
    'login_email'         => 'Correo electrónico',
    'login_password'      => 'Contraseña',
    'login_btn'           => 'Iniciar sesión',
    'login_magic_btn'     => 'Recibir enlace de acceso',
    'login_magic_sent'    => '¡Enlace de acceso enviado a tu correo!',
    'login_forgot'        => 'Olvidé mi contraseña',
    'login_register'      => 'Crear mi cuenta',
    'login_error'         => 'Correo o contraseña inválidos.',
    'login_locked'        => 'Cuenta bloqueada. Inténtalo de nuevo en :minutes minutos.',
    'login_inactive'      => 'Tu cuenta está desactivada. Contacta con la empresa.',
    'login_session_expired' => 'Tu sesión ha expirado. Inicia sesión nuevamente.',
    'login_or'            => 'o',
    'login_use_magic_link' => 'Esta cuenta no tiene contraseña. Utiliza el enlace de acceso por correo.',

    // ── Magic Link ──
    'magic_link_requested' => 'Si este correo está registrado, recibirás un enlace de acceso en instantes.',

    // ── Olvidé mi contraseña ──
    'forgot_title'        => 'Recuperar Contraseña',
    'forgot_subtitle'     => 'Ingresa tu correo para recibir un enlace de restablecimiento.',
    'forgot_email'        => 'Tu correo electrónico',
    'forgot_btn'          => 'Enviar enlace de recuperación',
    'forgot_success'      => 'Si este correo está registrado, recibirás un enlace para restablecer tu contraseña.',
    'forgot_back'         => 'Volver al inicio de sesión',

    // ── Restablecer contraseña ──
    'reset_title'         => 'Restablecer Contraseña',
    'reset_password'      => 'Nueva contraseña',
    'reset_password_confirm' => 'Confirmar nueva contraseña',
    'reset_password_hint' => 'Mínimo 8 caracteres, con letras y números.',
    'reset_btn'           => 'Guardar nueva contraseña',
    'reset_success'       => '¡Contraseña restablecida con éxito! Inicia sesión con tu nueva contraseña.',
    'reset_invalid_token' => 'Enlace de restablecimiento inválido o expirado. Solicita uno nuevo.',

    // ── Registro ──
    'register_title'      => 'Crear cuenta',
    'register_name'       => 'Nombre completo',
    'register_email'      => 'Correo electrónico',
    'register_phone'      => 'Teléfono / WhatsApp',
    'register_document'   => 'Documento de identidad',
    'register_password'   => 'Crear contraseña',
    'register_password_confirm' => 'Confirmar contraseña',
    'register_btn'        => 'Crear mi cuenta',
    'register_success'    => '¡Cuenta creada con éxito! Inicia sesión para continuar.',
    'register_email_exists' => 'Este correo ya está registrado.',
    'register_password_mismatch' => 'Las contraseñas no coinciden.',
    'register_disabled'   => 'El auto-registro está deshabilitado.',
    'register_has_account' => '¿Ya tienes cuenta?',
    'register_login'      => 'Inicia sesión',

    // ── Dashboard ──
    'dashboard_greeting'  => '¡Hola, :name!',
    'dashboard_active_orders'    => 'Pedidos Activos',
    'dashboard_pending_approval' => 'Pendientes de Aprobación',
    'dashboard_open_installments' => 'Cuotas Abiertas',
    'dashboard_open_amount'      => 'Pendiente',
    'dashboard_recent_notifications' => 'Notificaciones Recientes',
    'dashboard_recent_orders'    => 'Pedidos Recientes',
    'dashboard_no_notifications' => 'Sin notificaciones por el momento.',
    'dashboard_no_orders'        => 'Aún no tienes pedidos.',
    'dashboard_view_all'         => 'Ver todos',

    // ── Navegación Inferior ──
    'nav_home'            => 'Inicio',
    'nav_orders'          => 'Pedidos',
    'nav_new_order'       => 'Nuevo',
    'nav_financial'       => 'Financiero',
    'nav_profile'         => 'Perfil',
    'nav_more'            => 'Más',

    // ── Pedidos ──
    'orders_title'        => 'Mis Pedidos',
    'orders_all'          => 'Todos',
    'orders_open'         => 'Abiertos',
    'orders_approval'     => 'Aprobación',
    'orders_completed'    => 'Completados',
    'orders_empty'        => 'No se encontraron pedidos.',
    'orders_items'        => ':count artículo(s)',
    'orders_view'         => 'Ver',
    'orders_track'        => 'Rastrear',
    'orders_approve'      => 'Aprobar',
    'orders_forecast'     => 'Previsión: :date',
    'orders_next'         => 'Siguiente',
    'orders_no_items'     => 'No se encontraron artículos.',

    // ── Detalle del Pedido ──
    'order_detail_title'  => 'Pedido #:id',
    'order_timeline'      => 'Línea de Progreso',
    'order_items'         => 'Artículos del Pedido',
    'order_subtotal'      => 'Subtotal',
    'order_discount'      => 'Descuento',
    'order_total'         => 'Total',
    'order_extra_costs'   => 'Costos Extra',
    'order_installments'  => 'Cuotas',
    'order_send_message'  => 'Enviar Mensaje',
    'order_shipping'      => 'Envío',
    'order_tracking'      => 'Código de Rastreo',
    'order_notes'         => 'Observaciones',
    'order_item_product'  => 'Producto',
    'order_item_qty'      => 'Cant',
    'order_item_price'    => 'Precio',
    'order_item_subtotal' => 'Subtotal',
    'order_installment_number' => 'Cuota :n',

    // ── Aprobación ──
    'approval_title'      => 'Aprobar Presupuesto #:id',
    'approval_items'      => 'Artículos del Presupuesto',
    'approval_total'      => 'Total',
    'approval_company_notes' => 'Notas de la empresa',
    'approval_your_notes' => 'Tus observaciones...',
    'approval_approve_btn' => 'Aprobar Presupuesto',
    'approval_reject_btn' => 'Rechazar',
    'approval_disclaimer' => 'Al aprobar, aceptas las condiciones anteriores. Se registrarán la IP y la fecha.',
    'approval_success'    => '¡Presupuesto aprobado con éxito!',
    'approval_rejected'   => 'Presupuesto rechazado.',
    'approval_already'    => 'Este presupuesto ya fue :status.',
    'approval_cancelled'  => 'Aprobación cancelada. El presupuesto volvió a pendiente.',
    'approval_cancel_btn' => 'Cancelar Aprobación',
    'approval_cancel_confirm' => '¿Deseas cancelar la aprobación? El presupuesto volverá a "Pendiente".',

    // ── Aprobación — Layout Enfocado ──
    'approval_focus_order'     => 'Pedido',
    'approval_focus_date'      => 'Fecha',
    'approval_focus_decision'  => 'Tu Decisión',
    'approval_view_full_detail' => 'Ver detalles completos del pedido',

    // ── Financiero ──
    'financial_title'     => 'Financiero',
    'financial_summary'   => 'Resumen',
    'financial_open'      => 'Pendiente',
    'financial_paid'      => 'Pagado',
    'financial_tab_open'  => 'Pendientes',
    'financial_tab_paid'  => 'Pagadas',
    'financial_tab_all'   => 'Todas',
    'financial_empty'     => 'No se encontraron cuotas.',
    'financial_due_date'  => 'Vence: :date',
    'financial_paid_at'   => 'Pagada el :date',
    'financial_overdue'   => 'Atrasada',
    'financial_pending'   => 'Pendiente',
    'financial_pendente'  => 'Pendiente',
    'financial_pago'      => 'Pagado',
    'financial_atrasado'  => 'Atrasada',
    'financial_cancelado' => 'Cancelado',
    'financial_view'      => 'Ver Detalles',
    'financial_pay'       => 'Pagar Online',

    // ── Rastreo ──
    'tracking_title'      => 'Rastreo',
    'tracking_status'     => 'Estado',
    'tracking_code'       => 'Código',
    'tracking_carrier'    => 'Transportista',
    'tracking_destination' => 'Destino',
    'tracking_forecast'   => 'Previsión',
    'tracking_timeline'   => 'Línea de Envío',
    'tracking_no_code'    => 'Código de rastreo aún no disponible.',

    // ── Mensajes ──
    'messages_title'      => 'Mensajes',
    'messages_placeholder' => 'Escribe tu mensaje...',
    'messages_send'       => 'Enviar',
    'messages_empty'      => '¡Sin mensajes aún. Inicia una conversación!',

    // ── Perfil ──
    'profile_title'       => 'Mi Perfil',
    'profile_name'        => 'Nombre',
    'profile_email'       => 'Correo electrónico',
    'profile_phone'       => 'Teléfono',
    'profile_document'    => 'Documento',
    'profile_address'     => 'Dirección',
    'profile_password'    => 'Nueva contraseña',
    'profile_password_current' => 'Contraseña actual',
    'profile_password_current_required' => 'Ingresa tu contraseña actual para cambiarla.',
    'profile_password_current_wrong' => 'Contraseña actual incorrecta.',
    'profile_password_current_hint' => 'Obligatoria para cambiar la contraseña.',
    'profile_password_new' => 'Nueva contraseña',
    'profile_password_confirm' => 'Confirmar nueva contraseña',
    'profile_password_weak' => 'La contraseña debe tener al menos 8 caracteres, con letras y números.',
    'profile_password_hint' => 'Mínimo 8 caracteres, con letras y números.',
    'profile_save'        => 'Guardar Cambios',
    'profile_updated'     => '¡Perfil actualizado con éxito!',
    'profile_language'    => 'Idioma',
    'profile_logout'      => 'Cerrar sesión',
    'profile_logout_confirm' => '¿Deseas cerrar sesión?',

    // ── Estado de Pedidos ──
    'status_orcamento'    => 'Presupuesto',
    'status_venda'        => 'Venta',
    'status_producao'     => 'En Producción',
    'status_preparacao'   => 'Preparación',
    'status_envio'        => 'Envío/Entrega',
    'status_financeiro'   => 'Financiero',
    'status_concluido'    => 'Completado',
    'status_cancelado'    => 'Cancelado',
    'status_contato'      => 'Contacto',

    // ── Estado de Aprobación ──
    'approval_status_pendente' => 'Pendiente',
    'approval_status_aprovado' => 'Aprobado',
    'approval_status_recusado' => 'Rechazado',

    // ── Enlace de Pago ──
    'payment_link_title'        => 'Enlace de Pago',
    'payment_link_description'  => 'Haz clic en el botón de abajo para realizar el pago de forma segura.',
    'payment_link_btn'          => 'Pagar Ahora',
    'payment_link_generated_at' => 'Enlace generado el :date',

    // ── Enlace de Catálogo / Presupuesto ──
    'catalog_link_title'       => 'Ver Presupuesto',
    'catalog_link_description' => 'Haz clic en el botón de abajo para ver el presupuesto completo con productos y precios.',
    'catalog_link_btn'         => 'Ver Presupuesto Completo',

    // ── PWA / Instalación ──
    'pwa_install_title'   => 'Instalar App',
    'pwa_install_text'    => '¡Instala el Portal del Cliente en tu dispositivo para acceso rápido!',
    'pwa_install_btn'     => 'Instalar',
    'pwa_install_dismiss' => 'Ahora no',

    // ── Errores ──
    'error_404'           => 'Página no encontrada.',
    'error_403'           => 'Acceso denegado.',
    'error_500'           => 'Error interno. Inténtalo más tarde.',
    'error_generic'       => 'Algo salió mal. Inténtalo de nuevo.',
    'error_required'      => 'Este campo es obligatorio.',
    'error_invalid_email' => 'Correo inválido.',

    // ── Nuevo Pedido (Fase 3) ──
    'new_order_title'          => 'Nuevo Pedido',
    'new_order_all_categories' => 'Todas las categorías',
    'new_order_no_products'    => 'No hay productos disponibles en este momento.',
    'new_order_add'            => 'Agregar',
    'new_order_cart'           => 'Carrito',
    'new_order_notes_placeholder' => 'Observaciones sobre el pedido...',
    'new_order_submit'         => 'Enviar Pedido',
    'new_order_success'        => '¡Pedido enviado con éxito! Esperando análisis.',
    'cart_item_added'          => '¡Producto agregado al carrito!',
    'cart_item_removed'        => 'Producto eliminado del carrito.',
    'cart_empty'               => 'Tu carrito está vacío.',

    // ── Financiero (Fase 4) ──
    'financial_overdue_alert'  => 'Tienes :count cuota(s) atrasada(s).',
    'financial_method'         => 'Forma de pago',

    // ── Rastreo (Fase 4) ──
    'tracking_empty'           => 'No hay pedidos en rastreo en este momento.',
    'tracking_orders_title'    => 'Pedidos en Envío',
    'tracking_track_btn'       => 'Rastrear Paquete',
    'tracking_copy_code'       => 'Copiar Código',

    // ── Mensajes (Fase 5) ──
    'messages_filter_order'    => 'Pedido',
    'messages_sent'            => '¡Mensaje enviado!',
    'messages_attachment'      => 'Adjunto',

    // ── Documentos (Fase 5) ──
    'documents_title'          => 'Documentos',
    'documents_empty'          => 'No hay documentos disponibles.',
    'documents_nfe'            => 'Factura',

    // ── Formatos ──
    'currency_prefix'     => '$',
    'date_format'         => 'd/m/Y',
    'datetime_format'     => 'd/m/Y H:i',

    // ── 2FA (Fase 7) ──
    '2fa_title'           => 'Verificación en dos pasos',
    '2fa_description'     => 'Activa la verificación en dos pasos para una capa adicional de seguridad. Se enviará un código de 6 dígitos a tu correo en cada inicio de sesión.',
    '2fa_status_on'       => 'Activo',
    '2fa_status_off'      => 'Inactivo',
    '2fa_enabled'         => '¡Verificación en dos pasos activada!',
    '2fa_disabled'        => 'Verificación en dos pasos desactivada.',
    '2fa_verify_title'    => 'Verificación de Seguridad',
    '2fa_verify_subtitle' => 'Ingresa el código de 6 dígitos enviado a tu correo.',
    '2fa_code_label'      => 'Código de verificación',
    '2fa_verify_btn'      => 'Verificar',
    '2fa_invalid_code'    => 'Código inválido o expirado. Inténtalo de nuevo.',
    '2fa_code_resent'     => '¡Nuevo código enviado a tu correo!',
    '2fa_resend'          => '¿No lo recibiste? Reenviar código',
    '2fa_code_placeholder'=> '000000',

    // ── Avatar (Fase 7) ──
    'avatar_change'       => 'Cambiar foto',
    'avatar_updated'      => '¡Foto actualizada con éxito!',
    'avatar_upload_error' => 'Error al subir la foto. Inténtalo de nuevo.',
    'avatar_invalid_type' => 'Formato inválido. Usa JPG, PNG o WebP.',
    'avatar_too_large'    => 'Imagen muy grande. Máximo 2MB.',

    // ── Offline / PWA (Fase 7) ──
    'offline_title'       => 'Sin Conexión',
    'offline_message'     => 'Estás sin internet. Verifica tu conexión e inténtalo de nuevo.',
    'offline_retry'       => 'Intentar de Nuevo',

    // ── Rate Limiting (Fase 7) ──
    'rate_limit_exceeded' => 'Demasiados intentos. Inténtalo de nuevo en unos minutos.',
];
