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

});
