//################# BOOTSTRAP #####################

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[tooltip="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

//################# FIM BOOTSTRAP #################

$(document).ready(function () {

    $('.formularioAjax').submit(function (event) {
        event.preventDefault();
        var carregando = $('.ajaxLoading');
        var botao = $(':input[type="submit"]');
        var url = $(this).attr('action');
        var formulario = $(this);
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function () {
                carregando.show().fadeIn(200);
                botao.prop('disable', false).addClass('disabled');
              // Remover a classe 'is-invalid' de todos os campos antes de enviar o formulário
              formulario.find('.is-invalid').removeClass('is-invalid');
            },
            success: function (retorno) {

                if (retorno.erro) {
                    alerta(retorno.erro, 'yellow');
                    console.log(retorno.erro);
                    // Se a mensagem de erro indicar campos obrigatórios
                    if (retorno.erro.includes('preencha os campos obrigatórios')) {
                        // Adicione a classe 'is-invalid' aos campos obrigatórios
                        formulario.find('.obg').addClass('is-invalid');
                    }
                }
                if (retorno.successo) {
                    $('.formularioAjax')[0].reset();
                    alerta(retorno.successo, 'green');
                }

                if (retorno.redirecionar) {
                    window.location.href = retorno.redirecionar;
                }

            },
            complete: function () {
                carregando.hide().fadeOut(200);
                botao.removeClass('disabled');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
            }
        });

    });

});


function alerta(mensagem, cor) {
    new jBox('Notice', {
        content: mensagem,
        color: cor,
        animation: 'pulse',
        showCountdown: true
    });
}

$(document).ready(function(){
    $('.product__carousel').owlCarousel({
        loop: true,
        nav: false,
        dots: true,
        autoplay: true,
        autoplayTimeout: 3000,
        responsive: {
            0: {
                items: 1,
                margin: 0,
            }
        }
    });
});
