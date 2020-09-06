<?php
global $product, $pluginName, $woocommerce, $post;
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
<form id="formCalcFrete" style="text-transform: uppercase;" class="woocommerce-shipping-calculator"
      accept-charset="utf-8" method="post">
    <h4 style="margin-bottom: 0;">Calcular Frete</h4>
    <small><?= $pluginName ?></small>
    <section class="shipping-calculator-form" style="">
        <p class="form-row form-row-wide" id="calc_shipping_postcode_field">
            <input type="text" class="input-text"
                   value="<?php echo esc_attr(WC()->customer->get_shipping_postcode()); ?>"
                   placeholder="<?php esc_attr_e('Postcode / ZIP', 'woocommerce'); ?>" name="calc_shipping_postcode"
                   id="calc_shipping_postcode"/>
        </p>

        <p>
            <div class="frtck-wrap-button">
                <button  id="btFcSend" type="button" name="calc_shipping" value="1" class="button frtck-button">Calcular</button>
                <div id="btFcSubmit" class="frtck-loader"></div>
            </div>
        </p>
        <?php wp_nonce_field('woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce'); ?>
    </section>
    <input type="hidden" name="k" value="<?= get_option("FC_API_KEY") ?>"/>
    <input type="hidden" name="cep_orign" value="<?= FreteClick::fc_config("FC_CEP_ORIGIN"); ?>"/>
    <input type="hidden" name="street_orign" value="<?= FreteClick::fc_config("FC_STREET_ORIGIN"); ?>"/>
    <input type="hidden" name="number_orign" value="<?= FreteClick::fc_config("FC_NUMBER_ORIGIN"); ?>"/>
    <input type="hidden" name="complement_orign" value="<?= FreteClick::fc_config("FC_COMPLEMENT_ORIGIN"); ?>"/>
    <input type="hidden" name="district_orign" value="<?= FreteClick::fc_config("FC_DISTRICT_ORIGIN"); ?>"/>
    <input type="hidden" name="city_orign" value="<?= FreteClick::fc_config("FC_CITY_ORIGIN"); ?>"/>
    <input type="hidden" name="state_orign" value="<?= FreteClick::fc_config("FC_STATE_ORIGIN"); ?>"/>
    <input type="hidden" name="contry_orign" value="<?= FreteClick::fc_config("FC_CONTRY_ORIGIN"); ?>"/>
    <input type="hidden" name="product_id" value="<?= $product->get_id() ?>"/>
    <input type="hidden" name="product_name" value="<?= $data["name"] ?>"/>
    <input id="product_price" type="hidden" name="product_price" value="<?= $product->get_price() ?>"/>
    <input id="product_weight" type="hidden" name="product_weight" value="<?= $data["weight"] ?>"/>
    <input id="product_height" type="hidden" name="product_height" value="<?= $data["height"] ?>"/>
    <input id="product_width" type="hidden" name="product_width" value="<?= $data["width"] ?>"/>
    <input id="product_length" type="hidden" name="product_length" value="<?= $data["length"] ?>"/>
    <input type="hidden" name="freteclick_quote_type" value="<?= get_option("freteclick_quote_type") ?>"/>
    <input id="fc_prod_quantity" type="hidden" name="product_quantity" value=""/>
</form>
<section style="text-transform: uppercase;" id="fc_freteResults">
</section>

<script type="text/javascript">
 document.addEventListener("DOMContentLoaded", function () {
        jQuery("#btFcSend").click(function (e) {

            if(document.getElementById("calc_shipping_postcode").value == ""){
                alert('Por favor, preencha o campo CEP');
                document.getElementById("calc_shipping_postcode").focus();
                jQuery('#fc_freteResults').html('');
                return false
            }

            e.preventDefault();
            var btFcSubmit = document.getElementById("btFcSubmit");

            let variations = [];
            try {
                variations = JSON.parse('<?php echo json_encode($variations); ?>');
            } catch (e) {
                console.error(e);
            }

            btFcSubmit.disabled = true;
			jQuery('#btFcSubmit').addClass('button_loading');

            if (variations && variations.length > 0) {
                const variation = variations.find(variation => {
                    return variation.attribute.description === jQuery(`#${variation.attribute.name}`).val();
                });
                if (!variation) {
                    alert('Selecione um tamanho antes de calcular o frete');
					jQuery('#btFcSubmit').removeClass('button_loading');
                    btFcSubmit.disabled = false;
                    return;
                }

                jQuery("#product_weight").val(variation.product_weight)
                jQuery("#product_height").val(variation.product_height)
                jQuery("#product_width").val(variation.product_width)
                jQuery("#product_length").val(variation.product_length)
            }

            jQuery("#fc_prod_quantity").val(jQuery("input[name='quantity']").val());

            if (jQuery("#calc_shipping_postcode").val().length) {
                jQuery.ajax({
                    url: "<?= get_rest_url() ?>freteclick/get_shipping",
                    type: "POST",
                    data: jQuery("#formCalcFrete").serialize(),
                    success: function (data) {
                        btFcSubmit.disabled = false;
						jQuery('#btFcSubmit').removeClass('button_loading');
                        if (typeof data == "string") {
                            data = JSON.parse(data);
                        }
                        var fc_freteResults = document.getElementById("fc_freteResults");
                        fc_freteResults.innerHTML = "";
                        var res = data.response;
                        if (res.data) {
                            var quotes = res.data.quote;
                            if (quotes.length) {
                                for (var i = 0; i < quotes.length; i++) {
                                    createResult(quotes[i]);
                                }
                            } else {
                                createResult(null);
                            }
                        } else {
                            createResult(null);
                        }
                    },
                    error: function (error) {
                        btFcSubmit.disabled = false;
						jQuery('#btFcSubmit').removeClass('button_loading');
                        console.log(error);
                        createResult(null);
                    },
					done: function (){
						jQuery('#btFcSubmit').removeClass('button_loading');
					},
					beforeSend: function(){
						jQuery('#fc_freteResults').html('');
						jQuery('#btFcSubmit').addClass('button_loading');
						btFcSubmit.disabled = true;
					}
                });
            }
        });
    });
    //clear result
    document.addEventListener('keydown', function(event) {
        const key = event.key; 
        if (key === "Delete") {
            jQuery('#fc_freteResults').html('');
        }
        if(key === "Backspace"){
            jQuery('#fc_freteResults').html('');
        }
    });

    function createResult(dds) {
        var fc_freteResults = document.getElementById("fc_freteResults");
        if (!dds) {
            fc_freteResults.innerHTML = "Nenhuma Transportadora Encontrada!";
        } else {
            var div = document.createElement("div");

            div.style.textAlign = "center";

            var deadline = ' ';
            var deadline_extras = "<?php echo get_option("FC_PRAZO_EXTRA") ;?>";
            var deadline_varied = "<?php echo get_option("FC_PRAZO_VARIADO") ;?>";

            if (dds["deadline"] > 1) {
                //add deadline extras
                if(deadline_extras > 1){
                    deadline = dds["deadline"] + parseInt(deadline_extras); 
                }else{
                    deadline = dds["deadline"]; 
                }
                //add deadline varied
                if(deadline_varied > 1){
                    deadline = dds["deadline"] + " até " + deadline_varied;
                }else{
                    deadline = dds["deadline"];
                }
                //add deadline extras end varied
                if(deadline_extras > 1 && deadline_varied > 1){
                    deadline = dds["deadline"] + parseInt(deadline_extras) + " até " + deadline_varied;
                }else{
                    deadline = dds["deadline"];
                }

            } else {
                deadline = dds["deadline"];
            }
            var total = Number(dds["total"]).toFixed(2).replace(',', '').replace('.', ',');
            div.innerHTML =
                "<label>" + dds["carrier-alias"] + " (" + deadline  + " dias úteis )" + "</label> " +
                "<strong>R$: " + total.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'})  + "</strong><hr/>";

            fc_freteResults.appendChild(div);
        }
    }
</script>