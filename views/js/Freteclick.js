/**
 * Módulo para o calculo do frete usando o webservice do FreteClick
 *  @author    Ederson Ferreira (ederson.dev@gmail.com)
 *  @copyright 2010-2015 FreteClick
 *  @license   LICENSE
 */
function maskCep(t, mask) {
    var i = t.value.length;
    var saida = mask.substring(1, 0);
    var texto = mask.substring(i)
    if (texto.substring(0, 1) != saida) {
        t.value += texto.substring(0, 1);
    }
}
function addRowTableFrete(nomeServico, imgLogo, deadline, valorServico) {
    return '<tr><td><img src="' + imgLogo + '" alt="' + nomeServico + '" title="' + nomeServico + '" width = "180" /> <br/><p> ' + nomeServico + ' </p></td><td> Entrega em ' + deadline + ' dia(s) <br/> ' + valorServico + ' </td></tr>';
}

function addRowError(message) {
    return '<tr><td> ' + message + ' </td></tr>';
}

function get_settings_click(onClick) {
    var as = document.getElementsByTagName("a");
    var link = null, all = null;

    for(var i = 0; i < as.length; i++){
        if (as[i].innerText == "Frete Click"){
            link = as[i];
        }
    }

    if (link){
        var href = link.href;

        href = href.split('admin.php')[1];
        href = 'admin.php' + href;

        jQuery("[href='"+href+"']").click(function () {
            setTimeout(onClick, 500);
        });
    }
}

jQuery(function ($) {
    $(document).ready(function () {
        $.fn.extend({
            propAttr: $.fn.prop || $.fn.attr
        });
        $("[data-field-qty=qty],.cart_quantity_button a").click(function () {
            setTimeout(function () {
                $("#quantity_wanted,.cart_quantity input").trigger('change');
            }, 300);
        });
        $("#quantity_wanted").change(function () {
            $('[name="product-package[0][qtd]"]').attr('value', $(this).val());
            var price = $('#product-total-price').data('value') * $(this).val();
            $('#product-total-price').attr('value', price.toString().replace('.', ','));
        });

        $('#fk-cep').keydown(function (event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                return false;
            }
        });

        $('#btCalcularFrete').click(function () {
            var $btn = $(this).button('loading');
            $('#resultado-frete').hide();
            $("#frete-valores tbody").empty();
            var inputForm = $('#calcular_frete').serialize();
            $.ajax({
                url: $('#calcular_frete').attr('data-action'),
                type: 'post',
                dataType: 'json',
                data: inputForm,
                success: function (json) {
                    if (json.response.success === true) {
                        jQuery.each(json.response.data.quote, function (index, val) {
                            $("#frete-valores tbody").append(addRowTableFrete(val['carrier-name'], val['carrier-logo'], val.deadline, val.total));
                        });
                        $('#resultado-frete').show('slow');
                    } else {
                        //erro
						if (typeof json.response.error == "string"){
							$("#frete-valores tbody").append(addRowError(json.response.error));
						}
						else if (typeof json.response.error == "object" ){
							var erros = json.response.error;
							if (erros.length > 0) {
								for (var i = 0; i < erros.length; i++){
									$("#frete-valores tbody").append(addRowError(erros[i].message));
								}
							}
							else{
								$("#frete-valores tbody").append(addRowError(erros.message));
							}
						}
						$('#resultado-frete').show('slow');
                    }
                },
                complete: function () {
                    $btn.button('reset');
                }
            });
        });

        $('#resultado-frete').hide();
        if ($('[name="fkcorreiosg2_cep"]').length > 0) {
            $(".cart_quantity input").change(function () {
                setTimeout(function () {
                    $('.fkcorreiosg2-button').trigger('click');
                }, 3000);
            });
            $('#calcular_frete,#box-frete-click').hide();
            $('[name="fkcorreiosg2_cep"]').change(function () {
                $('#fk-cep').val($('[name="fkcorreiosg2_cep"]').val());
            });
            $('#fk-cep').val($('[name="fkcorreiosg2_cep"]').val());
            if ($('#fk-cep').val().length == 9) {
                $('#btCalcularFrete').click();
                $('#box-frete-click').show();
            }
            $('.fkcorreiosg2-button').click(function () {
                $('#btCalcularFrete').click();
                $('#box-frete-click').show();
            });

            $("#quantity_wanted").change(function () {
                setTimeout(function () {
                    $('#btCalcularFrete').trigger('click');
                }, 1000);
            });
        }



        /*
         delivery_option_radio
         $(button).prop('disabled', true);
         */

        $('input[name="fc_transportadora"]').click(function () {
            var button = $('[name="processCarrier"]');
            var fprice = $(this).attr('data-fprice'),
                    nome_transportadora = $(this).attr('data-name'),
                    module_name = $('#module_name').val();
            var descricao = '<strong>' + module_name + '</strong><br/>' + 'Transportadora:' + nome_transportadora + '<br/>';
            $.ajax({
                url: $('#url_transportadora').val(),
                type: "post",
                dataType: "json",
                data: {
                    quote_id: $(this).val(),
                    nome_transportadora: nome_transportadora,
                    valor_frete: $(this).attr('data-price')
                },
                success: function (json) {
                    if (json.status === true) {
                        $('.delivery_option_radio:checked').closest('tr').find('td.delivery_option_price').prev().html(descricao);
                        $('.delivery_option_radio:checked').closest('tr').find('td.delivery_option_price').html(fprice);
                    }
                }
            });
        });

        $(document).on('submit', 'form[name=carrier_area]', function () {
            var valTransportadora = $('input[name="fc_transportadora"]:checked').length;
            if (valTransportadora === 0 && $('input[name="fc_transportadora"]').length) {
                alert('Selecione uma transportadora');
                return false;
            }
        });

        get_settings_click(function () {
            $(".fc-input-cep").keypress(function (event) {
                maskCep(this, "#####-###");
            });
            
            //ViaCep
            var tb_cep, tb_rua, tb_cidade, tb_bairro, se_estado, span_estado, tb_pais;
    
            tb_cep = document.getElementsByClassName("cep-origin");
            tb_rua = document.getElementsByClassName("street-origin");
            tb_cidade = document.getElementsByClassName("city-origin");
            tb_bairro = document.getElementsByClassName("district-origin");
            se_estado = document.getElementsByClassName("state-origin");
            tb_pais = document.getElementsByClassName("country-origin");
            if (tb_cep.length > 0){
                var tb_cep_keyup = function(n){
                    var reseta = function (i) {
                        tb_cep[i].disabled = false;
                        tb_rua[i].disabled = false;
                        tb_cidade[i].disabled = false;
                        tb_bairro[i].disabled = false;
                        se_estado[i].disabled = false;			 
                    };
                    var num = tb_cep[n].value.length;
                    if (num == 9){
                        tb_cep[n].disabled = true;
                        tb_rua[n].disabled = true;
                        tb_cidade[n].disabled = true;
                        tb_bairro[n].disabled = true;
                        se_estado[n].disabled = true;
                    
                        $.ajax({
                            url: "https://viacep.com.br/ws/"+tb_cep[n].value+"/json/",
                            data: null,
                            success: function (data) {
                                if (!data.erro){
                                    tb_rua[n].value = data.logradouro;
                                    tb_cidade[n].value = data.localidade;
                                    tb_bairro[n].value = data.bairro;
                                    se_estado[n].value = data.uf;
                                }
                                reseta(n);
                            },
                            dataType: "json"
                        });
                    }
                    else{
                        reseta(n);
                    }
                }
                for (var i = 0; i < tb_cep.length; i++){
                    (function (num) {
                        tb_cep[num].onkeyup = function () { tb_cep_keyup(num); }
                    })(i);
                    if (tb_pais[i]){
                        tb_pais[i].value = "Brasil";
                    }
                }
            }
        });

    });
}
);