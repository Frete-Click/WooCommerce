<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $product, $woocommerce, $post;

/**
 * get product variables
 */
$available_variations = $product->is_type('variable') ? $available_variations = $product->get_available_variations() : [];
$variations = [];

foreach ($available_variations as $variation) {
    array_push($variations, [
        'attribute' => [
            'name' => substr(key($variation['attributes']), 1+strpos(key($variation['attributes']), '_')),
            'description' => $variation['attributes'][key($variation['attributes'])]
        ],
        'product_weight' => $variation['weight'],
        'product_height' => $variation['dimensions']['height'],
        'product_width' => $variation['dimensions']['width'],
        'product_length' => $variation['dimensions']['length']
    ]);
}

$data = $product->get_data();
?>
<form id="formCalcFrete" style="text-transform: uppercase;" class="woocommerce-shipping-calculator" accept-charset="utf-8" method="post">
    <h5 style="margin-bottom: 0;">Calcular Frete</h5>
    <small>Frete Click</small>
    <section class="shipping-calculator-form" style="">
        <input type="text" class="input-text"
            value="<?php echo esc_attr(WC()->customer->get_shipping_postcode()); ?>"
            placeholder="<?php esc_attr_e('Postcode / ZIP', 'woocommerce'); ?>" name="calc_shipping_postcode"
            id="calc_shipping_postcode"/>

        <div class="frtck-wrap-button">
            <button id="btFcSend" type="button" name="calc_shipping" value="1" class="button frtck-button">Calcular</button>
        </div>
        <?php wp_nonce_field('woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce'); ?>
    </section>    
</form>
<section style="text-transform: uppercase;" id="fc_freteResults" class="frtck-loader"></section>

<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function () {
    jQuery("#btFcSend").click(function (e) {
        e.preventDefault();

        if (document.getElementById("calc_shipping_postcode").value == "") {
            alert('Por favor, preencha o campo CEP');
            document.getElementById("calc_shipping_postcode").focus();
            jQuery('#fc_freteResults').html('');
            return false;
        }

        var btFcSubmit = document.getElementById("fc_freteResults");
        btFcSubmit.disabled = true;
        jQuery('#fc_freteResults').addClass('button_loading');

        let variations = [];
        try {
            variations = JSON.parse('<?php echo json_encode($variations); ?>');
        } catch (e) {
            console.error(e);
        }

        let selectedVariation = null;

        if (variations && variations.length > 0) {
            selectedVariation = variations.find(variation => {
                return variation.attribute.description === jQuery(`#${variation.attribute.name}`).val();
            });

            if (!selectedVariation) {
                alert('Selecione um tamanho antes de calcular o frete');
                jQuery('#btFcSubmit').removeClass('button_loading');
                btFcSubmit.disabled = false;
                return;
            }
        }

        const data = {
            nonce: "<?php echo wp_create_nonce('woocommerce-shipping-calculator'); ?>",
            cep_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_CEP_ORIGIN'); ?>",
            street_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_STREET_ORIGIN'); ?>",
            number_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_NUMBER_ORIGIN'); ?>",
            complement_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_COMPLEMENT_ORIGIN'); ?>",
            district_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_DISTRICT_ORIGIN'); ?>",
            city_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_CITY_ORIGIN'); ?>",
            state_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_STATE_ORIGIN'); ?>",
            country_origin: "<?= WC_FreteClick_Shipping_Simulator::fc_config('FC_COUNTRY_ORIGIN'); ?>",
            product_id: <?= $product->get_id(); ?>,
            product_name: "<?= $data['name']; ?>",
            product_price: <?= $product->get_price(); ?>,
            product_weight: selectedVariation ? selectedVariation.product_weight : <?= $data['weight']; ?>,
            product_height: selectedVariation ? selectedVariation.product_height : <?= $data['height']; ?>,
            product_width: selectedVariation ? selectedVariation.product_width : <?= $data['width']; ?>,
            product_length: selectedVariation ? selectedVariation.product_length : <?= $data['length']; ?>,
            product_quantity: jQuery("input[name='quantity']").val(),
            calc_shipping_postcode: jQuery("#calc_shipping_postcode").val(),
            freteclick_quote_type: "<?= get_option('freteclick_quote_type'); ?>"
        };

        jQuery.ajax({
            url: "<?= get_rest_url() ?>freteclick/get_shipping",
            type: "POST",
            data: data,
            success: function (response) {
                btFcSubmit.disabled = false;
                jQuery('#fc_freteResults').removeClass('button_loading');

                var fc_freteResults = document.getElementById("fc_freteResults");
                fc_freteResults.innerHTML = "";
                var res = response.response;

                if (res.data) {
                    var quotes = res.data.order.quotes;
                    if (quotes.length) {
                        quotes.forEach(createResult);
                    } else {
                        createResult(null);
                    }
                } else {
                    createResult(null);
                }
            },
            error: function (error) {
                btFcSubmit.disabled = false;
                jQuery('#fc_freteResults').removeClass('button_loading');
                console.error(error);
                createResult(null);
            },
            beforeSend: function () {
                jQuery('#fc_freteResults').html('');
                jQuery('#fc_freteResults').addClass('button_loading');
                btFcSubmit.disabled = true;
            }
        });
    });
});

function createResult(quote) {
    var fc_freteResults = document.getElementById("fc_freteResults");

    if (!quote) {
        fc_freteResults.innerHTML = "Nenhuma Transportadora Encontrada!";
    } else {
        var div = document.createElement("div");
        div.style.textAlign = "left";

        var deadline = parseInt(quote["retrieveDeadline"]) + parseInt(quote["deliveryDeadline"]);
        var deadline_extras = parseInt("<?php echo get_option('FC_PRAZO_EXTRA'); ?>");
        var deadline_varied = "<?php echo get_option('FC_PRAZO_VARIADO'); ?>";

        var add_deadline = deadline;
        if (deadline_extras > 0 || deadline_varied > 0) {
            if (deadline_extras > 0) add_deadline += deadline_extras;
            if (deadline_varied > 0) add_deadline += ` até ${deadline_varied}`;
        }

        var total = Number(quote["total"]).toFixed(2).replace('.', ',');
        div.innerHTML = `<label>${quote["carrier"]["alias"]} (${add_deadline} dias úteis)</label> <strong>R$: ${total}</strong><hr/>`;
        
        fc_freteResults.appendChild(div);
    }
}
</script>
