<?php
global $product;
$data = $product->get_data();
?>
<form id="formCalcFrete" class="woocommerce-shipping-calculator" method="post">
    <h4>Calcular Frete</h4>
    <section class="shipping-calculator-form" style="">
        <p class="form-row form-row-wide" id="calc_shipping_postcode_field">
            <input type="text" class="input-text" value="<?php echo esc_attr( WC()->customer->get_shipping_postcode() ); ?>" placeholder="<?php esc_attr_e( 'Postcode / ZIP', 'woocommerce' ); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" />
        </p>

		<p>
            <button type="submit" name="calc_shipping" value="1" class="button">Calcular</button>
        </p>
		<?php wp_nonce_field( 'woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce' ); ?>
    </section>
    <input type="hidden" name="cep_orign" value="<?= fc_config("FC_CEP_ORIGIN"); ?>"/>
    <input type="hidden" name="street_orign" value="<?= fc_config("FC_STREET_ORIGIN"); ?>"/>
    <input type="hidden" name="number_orign" value="<?= fc_config("FC_NUMBER_ORIGIN"); ?>"/>
    <input type="hidden" name="complement_orign" value="<?= fc_config("FC_COMPLEMENT_ORIGIN"); ?>"/>
    <input type="hidden" name="district_orign" value="<?= fc_config("FC_DISTRICT_ORIGIN"); ?>"/>
    <input type="hidden" name="city_orign" value="<?= fc_config("FC_CITY_ORIGIN"); ?>"/>
    <input type="hidden" name="state_orign" value="<?= fc_config("FC_STATE_ORIGIN"); ?>"/>
    <input type="hidden" name="contry_orign" value="<?= fc_config("FC_CONTRY_ORIGIN"); ?>"/>
    <input type="hidden" name="product_id" value="<?= $product->get_id() ?>" />
    <input type="hidden" name="product_name" value="<?= $data["name"] ?>" />
    <input type="hidden" name="product_price" value="<?= $product->get_price() ?>" />
    <input type="hidden" name="product_weight" value="<?= $data["weight"] ?>" />
    <input type="hidden" name="product_height" value="<?= $data["height"] ?>" />
    <input type="hidden" name="product_width" value="<?= $data["width"] ?>" />
    <input type="hidden" name="product_length" value="<?= $data["length"] ?>" />
    <input id="fc_prod_quantity" type="hidden" name="product_quantity" value="" />
</form>
<script>
document.addEventListener("DOMContentLoaded", function () {
    jQuery("#formCalcFrete").submit(function (e){
        e.preventDefault();

        jQuery("#fc_prod_quantity").val(jQuery("input[name='quantity']").val());

        if (this.calc_shipping_postcode.value.length){    
            jQuery.ajax({
                url: "<?= get_site_url() ?>/wp-content/plugins/freteclick/includes/get_shipping.php?k=<?= get_option("FC_API_KEY") ?>",
                type: "POST",
                data: jQuery("#formCalcFrete").serialize(),
                success: function (data){
                    console.log(data);
                },
                error: function (error){
                    console.log(error);
                }
            });
        }
    });
});
</script>