<?php
/**
 * Portal do Cliente — English Translations
 *
 * @see app/services/PortalLang.php
 */
return [
    // ── General ──
    'portal_title'        => 'Customer Portal',
    'loading'             => 'Loading...',
    'save'                => 'Save',
    'cancel'              => 'Cancel',
    'back'                => 'Back',
    'close'               => 'Close',
    'confirm'             => 'Confirm',
    'yes'                 => 'Yes',
    'no'                  => 'No',
    'search'              => 'Search',
    'filter'              => 'Filter',
    'all'                 => 'All',
    'none'                => 'None',
    'actions'             => 'Actions',
    'details'             => 'Details',
    'success'             => 'Success',
    'error'               => 'Error',
    'warning'             => 'Warning',
    'info'                => 'Information',

    // ── Login ──
    'login_title'         => 'Sign in to your account',
    'login_email'         => 'Email',
    'login_password'      => 'Password',
    'login_btn'           => 'Sign In',
    'login_magic_btn'     => 'Get access link',
    'login_magic_sent'    => 'Access link sent to your email!',
    'login_forgot'        => 'Forgot my password',
    'login_register'      => 'Create my account',
    'login_error'         => 'Invalid email or password.',
    'login_locked'        => 'Account locked. Try again in :minutes minutes.',
    'login_inactive'      => 'Your account is deactivated. Contact the company.',
    'login_session_expired' => 'Your session has expired. Please sign in again.',
    'login_or'            => 'or',
    'login_use_magic_link' => 'This account has no password. Use the email access link.',

    // ── Magic Link ──
    'magic_link_requested' => 'If this email is registered, you will receive an access link shortly.',

    // ── Forgot Password ──
    'forgot_title'        => 'Recover Password',
    'forgot_subtitle'     => 'Enter your email to receive a password reset link.',
    'forgot_email'        => 'Your email',
    'forgot_btn'          => 'Send recovery link',
    'forgot_success'      => 'If this email is registered, you will receive a link to reset your password.',
    'forgot_back'         => 'Back to login',

    // ── Reset Password ──
    'reset_title'         => 'Reset Password',
    'reset_password'      => 'New password',
    'reset_password_confirm' => 'Confirm new password',
    'reset_password_hint' => 'Minimum 8 characters, with letters and numbers.',
    'reset_btn'           => 'Save new password',
    'reset_success'       => 'Password reset successfully! Sign in with your new password.',
    'reset_invalid_token' => 'Invalid or expired reset link. Please request a new one.',

    // ── Registration ──
    'register_title'      => 'Create account',
    'register_name'       => 'Full name',
    'register_email'      => 'Email',
    'register_phone'      => 'Phone / WhatsApp',
    'register_document'   => 'Tax ID',
    'register_password'   => 'Create password',
    'register_password_confirm' => 'Confirm password',
    'register_btn'        => 'Create my account',
    'register_success'    => 'Account created successfully! Sign in to continue.',
    'register_email_exists' => 'This email is already registered.',
    'register_password_mismatch' => 'Passwords do not match.',
    'register_disabled'   => 'Self-registration is disabled.',
    'register_has_account' => 'Already have an account?',
    'register_login'      => 'Sign in',

    // ── Dashboard ──
    'dashboard_greeting'  => 'Hello, :name!',
    'dashboard_active_orders'    => 'Active Orders',
    'dashboard_pending_approval' => 'Pending Approval',
    'dashboard_open_installments' => 'Open Installments',
    'dashboard_open_amount'      => 'Outstanding',
    'dashboard_recent_notifications' => 'Recent Notifications',
    'dashboard_recent_orders'    => 'Recent Orders',
    'dashboard_no_notifications' => 'No notifications at this time.',
    'dashboard_no_orders'        => 'You don\'t have any orders yet.',
    'dashboard_view_all'         => 'View all',

    // ── Bottom Navigation ──
    'nav_home'            => 'Home',
    'nav_orders'          => 'Orders',
    'nav_new_order'       => 'New',
    'nav_financial'       => 'Financial',
    'nav_profile'         => 'Profile',
    'nav_more'            => 'More',

    // ── Orders ──
    'orders_title'        => 'My Orders',
    'orders_all'          => 'All',
    'orders_open'         => 'Open',
    'orders_approval'     => 'Approval',
    'orders_completed'    => 'Completed',
    'orders_empty'        => 'No orders found.',
    'orders_items'        => ':count item(s)',
    'orders_view'         => 'View',
    'orders_track'        => 'Track',
    'orders_approve'      => 'Approve',
    'orders_forecast'     => 'Forecast: :date',
    'orders_next'         => 'Next',
    'orders_no_items'     => 'No items found.',

    // ── Order Detail ──
    'order_detail_title'  => 'Order #:id',
    'order_timeline'      => 'Progress Timeline',
    'order_items'         => 'Order Items',
    'order_subtotal'      => 'Subtotal',
    'order_discount'      => 'Discount',
    'order_total'         => 'Total',
    'order_extra_costs'   => 'Extra Costs',
    'order_installments'  => 'Installments',
    'order_send_message'  => 'Send Message',
    'order_shipping'      => 'Shipping',
    'order_tracking'      => 'Tracking Code',
    'order_notes'         => 'Notes',
    'order_item_product'  => 'Product',
    'order_item_qty'      => 'Qty',
    'order_item_price'    => 'Price',
    'order_item_subtotal' => 'Subtotal',
    'order_installment_number' => 'Installment :n',

    // ── Approval ──
    'approval_title'      => 'Approve Quote #:id',
    'approval_items'      => 'Quote Items',
    'approval_total'      => 'Total',
    'approval_company_notes' => 'Company notes',
    'approval_your_notes' => 'Your notes...',
    'approval_approve_btn' => 'Approve Quote',
    'approval_reject_btn' => 'Reject',
    'approval_disclaimer' => 'By approving, you agree with the above conditions. IP and date will be recorded.',
    'approval_success'    => 'Quote approved successfully!',
    'approval_rejected'   => 'Quote rejected.',
    'approval_already'    => 'This quote has already been :status.',
    'approval_cancelled'  => 'Approval cancelled. The quote is now pending again.',
    'approval_cancel_btn' => 'Cancel Approval',
    'approval_cancel_confirm' => 'Do you really want to cancel the approval? The quote will return to "Pending".',

    // ── Approval — Focused Layout ──
    'approval_focus_order'     => 'Order',
    'approval_focus_date'      => 'Date',
    'approval_focus_decision'  => 'Your Decision',
    'approval_view_full_detail' => 'View full order details',

    // ── Financial ──
    'financial_title'     => 'Financial',
    'financial_summary'   => 'Summary',
    'financial_open'      => 'Outstanding',
    'financial_paid'      => 'Paid',
    'financial_tab_open'  => 'Outstanding',
    'financial_tab_paid'  => 'Paid',
    'financial_tab_all'   => 'All',
    'financial_empty'     => 'No installments found.',
    'financial_due_date'  => 'Due: :date',
    'financial_paid_at'   => 'Paid on :date',
    'financial_overdue'   => 'Overdue',
    'financial_pending'   => 'Pending',
    'financial_pendente'  => 'Pending',
    'financial_pago'      => 'Paid',
    'financial_atrasado'  => 'Overdue',
    'financial_cancelado' => 'Cancelled',
    'financial_view'      => 'View Details',
    'financial_pay'       => 'Pay Online',

    // ── Tracking ──
    'tracking_title'      => 'Tracking',
    'tracking_status'     => 'Status',
    'tracking_code'       => 'Code',
    'tracking_carrier'    => 'Carrier',
    'tracking_destination' => 'Destination',
    'tracking_forecast'   => 'Forecast',
    'tracking_timeline'   => 'Shipping Timeline',
    'tracking_no_code'    => 'Tracking code not yet available.',

    // ── Messages ──
    'messages_title'      => 'Messages',
    'messages_placeholder' => 'Type your message...',
    'messages_send'       => 'Send',
    'messages_empty'      => 'No messages yet. Start a conversation!',

    // ── Profile ──
    'profile_title'       => 'My Profile',
    'profile_name'        => 'Name',
    'profile_email'       => 'Email',
    'profile_phone'       => 'Phone',
    'profile_document'    => 'Tax ID',
    'profile_address'     => 'Address',
    'profile_password'    => 'New password',
    'profile_password_current' => 'Current password',
    'profile_password_current_required' => 'Enter your current password to change it.',
    'profile_password_current_wrong' => 'Incorrect current password.',
    'profile_password_current_hint' => 'Required to change password.',
    'profile_password_new' => 'New password',
    'profile_password_confirm' => 'Confirm new password',
    'profile_password_weak' => 'Password must be at least 8 characters, with letters and numbers.',
    'profile_password_hint' => 'Minimum 8 characters, with letters and numbers.',
    'profile_save'        => 'Save Changes',
    'profile_updated'     => 'Profile updated successfully!',
    'profile_language'    => 'Language',
    'profile_logout'      => 'Sign Out',
    'profile_logout_confirm' => 'Do you really want to sign out?',

    // ── Order Status ──
    'status_orcamento'    => 'Quote',
    'status_venda'        => 'Sale',
    'status_producao'     => 'In Production',
    'status_preparacao'   => 'Preparation',
    'status_envio'        => 'Shipping',
    'status_financeiro'   => 'Financial',
    'status_concluido'    => 'Completed',
    'status_cancelado'    => 'Cancelled',
    'status_contato'      => 'Contact',

    // ── Approval Status ──
    'approval_status_pendente' => 'Pending',
    'approval_status_aprovado' => 'Approved',
    'approval_status_recusado' => 'Rejected',

    // ── Payment Link ──
    'payment_link_title'        => 'Payment Link',
    'payment_link_description'  => 'Click the button below to make a secure payment.',
    'payment_link_btn'          => 'Pay Now',
    'payment_link_generated_at' => 'Link generated on :date',

    // ── Catalog / Quote Link ──
    'catalog_link_title'       => 'View Quote',
    'catalog_link_description' => 'Click the button below to view the full quote with products and prices.',
    'catalog_link_btn'         => 'View Full Quote',

    // ── PWA / Install ──
    'pwa_install_title'   => 'Install App',
    'pwa_install_text'    => 'Install the Customer Portal on your device for quick access!',
    'pwa_install_btn'     => 'Install',
    'pwa_install_dismiss' => 'Not now',

    // ── Errors ──
    'error_404'           => 'Page not found.',
    'error_403'           => 'Access denied.',
    'error_500'           => 'Internal error. Please try again later.',
    'error_generic'       => 'Something went wrong. Please try again.',
    'error_required'      => 'This field is required.',
    'error_invalid_email' => 'Invalid email.',

    // ── New Order (Phase 3) ──
    'new_order_title'          => 'New Order',
    'new_order_all_categories' => 'All categories',
    'new_order_no_products'    => 'No products available at this time.',
    'new_order_add'            => 'Add',
    'new_order_cart'           => 'Cart',
    'new_order_notes_placeholder' => 'Notes about the order...',
    'new_order_submit'         => 'Submit Order',
    'new_order_success'        => 'Order submitted successfully! Awaiting analysis.',
    'cart_item_added'          => 'Product added to cart!',
    'cart_item_removed'        => 'Product removed from cart.',
    'cart_empty'               => 'Your cart is empty.',

    // ── Financial (Phase 4) ──
    'financial_overdue_alert'  => 'You have :count overdue installment(s).',
    'financial_method'         => 'Payment method',

    // ── Tracking (Phase 4) ──
    'tracking_empty'           => 'No orders currently being tracked.',
    'tracking_orders_title'    => 'Orders in Shipping',
    'tracking_track_btn'       => 'Track Package',
    'tracking_copy_code'       => 'Copy Code',

    // ── Messages (Phase 5) ──
    'messages_filter_order'    => 'Order',
    'messages_sent'            => 'Message sent!',
    'messages_attachment'      => 'Attachment',

    // ── Documents (Phase 5) ──
    'documents_title'          => 'Documents',
    'documents_empty'          => 'No documents available.',
    'documents_nfe'            => 'Invoice',

    // ── Formats ──
    'currency_prefix'     => '$',
    'date_format'         => 'm/d/Y',
    'datetime_format'     => 'm/d/Y h:i A',

    // ── 2FA (Phase 7) ──
    '2fa_title'           => 'Two-Factor Authentication',
    '2fa_description'     => 'Enable two-factor authentication for an extra layer of security. A 6-digit code will be sent to your email on each login.',
    '2fa_status_on'       => 'Active',
    '2fa_status_off'      => 'Inactive',
    '2fa_enabled'         => 'Two-factor authentication enabled!',
    '2fa_disabled'        => 'Two-factor authentication disabled.',
    '2fa_verify_title'    => 'Security Verification',
    '2fa_verify_subtitle' => 'Enter the 6-digit code sent to your email.',
    '2fa_code_label'      => 'Verification code',
    '2fa_verify_btn'      => 'Verify',
    '2fa_invalid_code'    => 'Invalid or expired code. Please try again.',
    '2fa_code_resent'     => 'New code sent to your email!',
    '2fa_resend'          => 'Didn\'t receive it? Resend code',
    '2fa_code_placeholder'=> '000000',

    // ── Avatar (Phase 7) ──
    'avatar_change'       => 'Change photo',
    'avatar_updated'      => 'Photo updated successfully!',
    'avatar_upload_error' => 'Error uploading photo. Please try again.',
    'avatar_invalid_type' => 'Invalid format. Use JPG, PNG, or WebP.',
    'avatar_too_large'    => 'Image too large. Maximum 2MB.',

    // ── Offline / PWA (Phase 7) ──
    'offline_title'       => 'No Connection',
    'offline_message'     => 'You are offline. Check your connection and try again.',
    'offline_retry'       => 'Try Again',

    // ── Rate Limiting (Phase 7) ──
    'rate_limit_exceeded' => 'Too many attempts. Please try again in a few minutes.',
];
