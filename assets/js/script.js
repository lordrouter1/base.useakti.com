$(document).ready(function() {
    console.log("Sistema de Gestão Iniciado");

    // ── CSRF Token: enviar automaticamente em todas as requisições AJAX ──
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }

    // ── Interceptor global AJAX: redirecionar para login se sessão expirou (401) ──
    // ── Interceptor CSRF: recarregar página se token inválido (403) ──
    $(document).ajaxComplete(function(event, xhr) {
        if (xhr.status === 401) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.session_expired) {
                    window.location.href = '?page=login&session_expired=1';
                }
            } catch(e) {}
        }
        if (xhr.status === 403) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.csrf_error) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sessão expirada',
                        text: 'Sua sessão de segurança expirou. A página será recarregada.',
                        confirmButtonText: 'Recarregar',
                        allowOutsideClick: false
                    }).then(function() {
                        location.reload();
                    });
                }
            } catch(e) {}
        }
    });

    // Exemplo de interação genérica
    $('.btn-delete').click(function(e) {
        if(!confirm('Tem certeza que deseja excluir este item?')) {
            e.preventDefault();
        }
    });

    // Preview da imagem ao selecionar arquivo
    $('#photo').change(function() {
        const file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(event) {
                $('#photoPreview').attr('src', event.target.result).show();
                $('#photoPlaceholder').hide();
            }
            reader.readAsDataURL(file);
        } else {
             $('#photoPreview').hide();
             $('#photoPlaceholder').show();
        }
    });

    // Máscaras de Entrada
    var behavior = function (val) {
        return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    },
    options = {
        onKeyPress: function (val, e, field, options) {
            field.mask(behavior.apply({}, arguments), options);
        }
    };

    $('#phone').mask(behavior, options);
    $('#zipcode').mask('00000-000');
    
    // Máscara dinâmica para CPF/CNPJ
    var cpfCnpjBehavior = function (val) {
        return val.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';
    },
    cpfCnpjOptions = {
        onKeyPress: function (val, e, field, options) {
            field.mask(cpfCnpjBehavior.apply({}, arguments), options);
        }
    };
    $('#document').mask(cpfCnpjBehavior, cpfCnpjOptions);

    // ═══════════════════════════════════════════════════
    // ATALHOS DE TECLADO — Navegação rápida
    // ═══════════════════════════════════════════════════
    $(document).on('keydown', function(e) {
        console.log("entrou no atalho");
        // Não disparar atalhos se estiver em input, textarea ou select
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || $(e.target).attr('contenteditable') === 'true') {
            return;
        }

        // Alt + tecla = atalhos do sistema
        if (e.altKey && !e.ctrlKey && !e.metaKey) {
            var handled = true;
            switch (e.key.toLowerCase()) {
                case 'h': // Alt+H → Dashboard
                    window.location.href = '?page=dashboard';
                    break;
                case 'p': // Alt+P → Pipeline (Kanban)
                    window.location.href = '?page=pipeline';
                    break;
                case 'o': // Alt+O → Pedidos
                    window.location.href = '?page=orders';
                    break;
                case 'n': // Alt+N → Novo Pedido
                    window.location.href = '?page=orders&action=create';
                    break;
                case 'c': // Alt+C → Clientes
                    window.location.href = '?page=customers';
                    break;
                case 'r': // Alt+R → Produtos
                    window.location.href = '?page=products';
                    break;
                case 'e': // Alt+E → Estoque
                    window.location.href = '?page=stock';
                    break;
                case 'f': // Alt+F → Financeiro
                    window.location.href = '?page=financial';
                    break;
                case 's': // Alt+S → Configurações
                    window.location.href = '?page=settings';
                    break;
                case 'u': // Alt+U → Usuários
                    window.location.href = '?page=users';
                    break;
                case 'a': // Alt+A → Agenda
                    window.location.href = '?page=orders&action=agenda';
                    break;
                case 'k': // Alt+K → Mostrar painel de atalhos
                    showKeyboardShortcuts();
                    break;
                default:
                    handled = false;
            }
            if (handled) {
                e.preventDefault();
            }
        }
    });

    // Painel de atalhos (Alt+K)
    function showKeyboardShortcuts() {
        var html = '<table class="table table-sm table-bordered mb-0 text-start" style="font-size:0.9rem;">';
        html += '<thead class="table-light"><tr><th style="width:120px;">Atalho</th><th>Ação</th></tr></thead><tbody>';
        var shortcuts = [
            ['Alt + H', 'Dashboard'],
            ['Alt + P', 'Pipeline (Kanban)'],
            ['Alt + O', 'Pedidos'],
            ['Alt + N', 'Novo Pedido'],
            ['Alt + C', 'Clientes'],
            ['Alt + R', 'Produtos'],
            ['Alt + E', 'Estoque'],
            ['Alt + F', 'Financeiro'],
            ['Alt + S', 'Configurações'],
            ['Alt + U', 'Usuários'],
            ['Alt + A', 'Agenda de Contatos'],
            ['Alt + K', 'Este painel de atalhos']
        ];
        for (var i = 0; i < shortcuts.length; i++) {
            html += '<tr><td><kbd>' + shortcuts[i][0] + '</kbd></td><td>' + shortcuts[i][1] + '</td></tr>';
        }
        html += '</tbody></table>';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<i class="fas fa-keyboard me-2"></i>Atalhos do Teclado',
                html: html,
                width: 480,
                showConfirmButton: true,
                confirmButtonText: 'Fechar',
                customClass: { popup: 'text-start' }
            });
        } else {
            alert('Atalhos: Alt+H=Dashboard, Alt+P=Pipeline, Alt+O=Pedidos, Alt+N=Novo Pedido, Alt+C=Clientes, Alt+R=Produtos, Alt+E=Estoque, Alt+F=Financeiro, Alt+S=Config, Alt+U=Usuários, Alt+A=Agenda');
        }
    }

});
