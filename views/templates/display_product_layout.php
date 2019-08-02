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
</form>
<script>
document.addEventListener("DOMContentLoaded", function () {
    jQuery("#formCalcFrete").submit(function (e){
        e.preventDefault();

        jQuery.ajax({
            url: "<?= get_site_url() ?>/wp-content/plugins/freteclick/includes/get_shipping.php",
            type: "POST",
            data: jQuery("#formCalcFrete").serialize(),
            success: function (data){
                console.log(data);
            },
            error: function (error){
                console.log(error);
            }
        });
    });
});
</script>