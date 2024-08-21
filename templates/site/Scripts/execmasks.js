$(document).ready(function () {

    var SPMaskBehavior = function (val) {
        return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    }, spOptions = {
        onKeyPress: function (val, e, field, options) {
            field.mask(SPMaskBehavior.apply({}, arguments), options);
        }
    };

    if ($(".cep-mask").length > 0) {
        $(".cep-mask").mask('99.999-999');
    }

    if ($(".date-mask").length > 0) {
        $(".date-mask").mask('99/99/9999');
    }

    if ($(".number-mask").length > 0) {
        $(".number-mask").mask('00000');
    }

    if ($(".cvv-mask").length > 0) {
        $(".cvv-mask").mask('999');
    }

    if ($(".cpf-mask").length > 0) {
        $(".cpf-mask").mask('999.999.999-99');
    }

    if ($(".phone-mask").length > 0) {
        $('.phone-mask').mask(SPMaskBehavior, spOptions);
    }
});