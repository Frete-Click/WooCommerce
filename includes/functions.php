<?php
function fc_get_cep_data($cep){					
    /*Obter dados viacep*/
    $_cep = curl_init();
    curl_setopt($_cep, CURLOPT_URL, 'https://viacep.com.br/ws/'.$cep.'/json/');
    curl_setopt($_cep, CURLOPT_RETURNTRANSFER, true);
    $data_cep = json_decode(curl_exec($_cep));
    curl_close($_cep);
    return $data_cep;
};
function fc_add_scripts(){
	$plugin_uri = plugin_dir_url( __FILE__ );
	
	//Adicionando estilos
	wp_enqueue_script("freteclick", $plugin_uri."views/js/Freteclick.js", array( 'jquery', 'jquery-ui-autocomplete' ), "1.0", true);

};
function fc_pedido_alterado($order_id, $old_status, $new_status){
	$order = new WC_Order( $order_id );
	$data = $order->get_data();
	$shipping = $order->get_items('shipping');
	$shipping_data = array();
	$array_data = array();

	foreach($shipping as $key => $shipping_item){
		$s_data = $shipping_item->get_data();
		$shipping_data[$key] = $s_data;
	}

	$status_espera = array(
		'pending',
		'processing',
		'on-hold'
	);

	if (in_array($data['status'], $status_espera)){

	}

	error_log($old_status);
	error_log($new_status);
	error_log(json_encode($shipping_data));
	error_log(json_encode($data));
};
/* Página de Configurações */
function fc_options_register_fields(){
    add_option( 'freteclick_display_product', '0');
    register_setting( 'freteclick_options_page', 'freteclick_display_product', array(
        "type" => "boolean",
        "description" => "Isso vai adicionar um campo de cálculo de frete nas páginas de produto"
    ) );
}
function fc_options_page(){
    add_options_page("Frete Click", "Frete Click", "manage_options", "freteclick", "fc_options_page_layout");
}
function fc_options_page_layout(){
    global $pluginDir;

    include $pluginDir . "views/templates/options_page_layout.php";
}
/* Formulário na página de produto */
function fc_display_product_layout(){
    if (get_option('freteclick_display_product') == 1){
        global $pluginDir;
    
        include $pluginDir . "views/templates/display_product_layout.php";
    }
}
/* Frete Click Mensagens */
function fc_wc_missing_notice(){
	printf("<div class='notice notice-warning'><p>O WooCommerce não está intalado, para usar o Frete Click é necessário <a href='https://br.wordpress.org/plugins/woocommerce/' target='blanck'>instalar o WooCommerce</a>.</p></div>");
};
function fc_missing_apikey(){
	printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe sua Chave de API</p></div>");
};
function fc_is_disabled(){
	printf("<div class='notice notice-warning is-dismissible'><p>O Frete Click está desabilitado. Ative o Frete Click para voltar a usa-lo.</p></div>");
};
function fc_missing_address(){
	printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe o endereço completo para a coleta dos produtos.</p></div>");
};