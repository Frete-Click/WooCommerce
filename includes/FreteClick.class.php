<?php
require_once("variables.php");

use SDK\SDK;
use SDK\Models\QuoteRequest;
use SDK\Models\Package;
use SDK\Models\Origin;
use SDK\Models\Destination;
use SDK\Models\Config;



class FreteClick{
	public static function init(){
		if (is_admin()){
			self::fc_add_scripts();
		}else{
			self::fc_add_styles();
		}
		
		add_action( 'woocommerce_shipping_init', 'freteclick_shipping_methods');	
		add_filter( 'woocommerce_shipping_methods', array('FreteClick','add_fc_shipping_methods'));

		/*Hooks para status dos pedidos*/
		add_action('woocommerce_order_status_changed', array('FreteClick','fc_pedido_alterado'), 10, 3);

		/* Hooks para página de configurações globais */
		add_action('admin_init', array('FreteClick','fc_options_register_fields'));
		add_action('admin_menu', array('FreteClick','fc_options_page'));

		/* Hook para busca frete no carrinho */
		add_action( 'woocommerce_product_meta_start', array('FreteClick','fc_display_product_layout'), 10, 0 );

		/* registrando rota rest para buscar cotações */
		add_action("rest_api_init", function () {
			register_rest_route("freteclick", "/get_shipping", array(
				'methods' => 'POST',
				'callback' => array('FreteClick','rest_get_shipping')
			));
		});
	}
	public static function fc_is_disabled()	{
		printf("<div class='notice notice-warning is-dismissible'><p>O Frete Click está desabilitado. Ative o Frete Click para voltar a usa-lo.</p></div>");
	}	
	/* Frete Click Mensagens */
	public static function fc_wc_missing_notice(){
		printf("<div class='notice notice-warning'><p>O WooCommerce não está intalado, para usar o Frete Click é necessário <a href='https://br.wordpress.org/plugins/woocommerce/' target='blanck'>instalar o WooCommerce</a>.</p></div>");
	}
	
	public static function getErrors(){
		global $fc_errors;
		return $fc_errors ? array(
			'response' => array(
				'data' => 'false',
				'count' => 0,
				'success' => false,
				'error' => $fc_errors
			)
		) : false;
	}
	
	/* Fazer Cotação */
	public static function fc_calculate_shipping($request = array()){

		/**
		 * get products
		 */
		global $woocommerce;
		$quote_request = new QuoteRequest();
		session_start();
		

		if (!empty($request['destination']['postcode'])) {		

			/*Dados de origem*/
			$origin = new Origin;			
			$origin->setCEP(self::fc_config('FC_CEP_ORIGIN'));
			$origin->setStreet(self::fc_config('FC_STREET_ORIGIN'));
			$origin->setNumber(self::fc_config('FC_NUMBER_ORIGIN'));
			$origin->setComplement(self::fc_config('FC_COMPLEMENT_ORIGIN'));
			$origin->setDistrict(self::fc_config('FC_DISTRICT_ORIGIN'));
			$origin->setCity(self::fc_config('FC_CITY_ORIGIN'));
			$origin->setState(self::fc_config('FC_STATE_ORIGIN'));
			$origin->setCountry(self::fc_config('FC_CONTRY_ORIGIN'));
			$quote_request->setOrigin($origin);

			$config = new Config;
			$config->setQuoteType(isset($orign["freteclick_quote_type"]) ? $orign["freteclick_quote_type"] : get_option("freteclick_quote_type"));
			$config->setOrder('total');

			$quote_request->setConfig($config); 
			
			/*Dados do produto*/
			if (class_exists("WC_Product_Factory")) {
				$_pf = new WC_Product_Factory();
			}						

			$items = isset($woocommerce->cart) ? $woocommerce->cart->get_cart() : [];
			if (count($items) > 0) {
				
				foreach ($items as $item) {
					$package = new Package();
					$package->setQuantity($item['quantity']);
					$package->setWeight($item['data']->get_weight());
					$package->setHeight($item['data']->get_height() / 100);
					$package->setWidth($item['data']->get_width() / 100);
					$package->setDepth($item['data']->get_length() / 100);
					$package->setProductType($item['data']->get_title());
					$package->setProductPrice($item['line_total']);					
					$quote_request->addPackage($package);					
				}					
			} else {				
				foreach ($request['contents'] as $key => $item) {					
					if (class_exists("WC_Product_Factory")) {
						$product = $_pf->get_product($item['product_id']);
						$p_data = $product->get_data();
						if (!$p_data['weight']) {
							$p_data = $item["data"];
						}
					} else {
						$product = $item;
						$p_data = $item["data"];
					}					
					
					$package = new Package();
					$package->setQuantity($item['quantity']);
					$package->setWeight($p_data['weight']);
					$package->setHeight($p_data['height']  / 100);
					$package->setWidth($p_data['width'] / 100);
					$package->setDepth($p_data['length'] / 100);
					$package->setProductType($p_data['name']);
					/*
					* @todo Verificar o preço
					*/
					$package->setProductPrice(1);
					$quote_request->addPackage($package);					
				}				
			}

			/*Dados do destino*/			
			$data_cep = self::fc_get_cep_data($request['destination']['postcode']);
			$destination = new Destination;			
			$destination->setCEP($dest['postcode']);
			$destination->setStreet(preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $data_cep->logradouro) ?: 'Rua não encontrada');
			$destination->setNumber(preg_replace('/[^0-9]/', '', $dest['address'])?:1);
			$destination->setComplement(strlen($data_cep->complemento) ? $data_cep->complemento : "SEM COMPLEMENTO");
			$destination->setDistrict($data_cep->bairro ?: 'Bairro não encontrado');
			$destination->setCity($data_cep->localidade);
			$destination->setState($data_cep->uf);
			$destination->setCountry('Brasil');
			$quote_request->setDestination($destination);
			$array_resp = self::fc_get_quotes($quote_request);

			return $array_resp;
		}
	}	
	public static function fc_get_mathod(){
		global $pluginId;
		if (class_exists("WC_Shipping_Zones")) {
			$zones = WC_Shipping_Zones::get_zones();
			foreach ($zones as $zone) {
				$methods = $zone["shipping_methods"];
				foreach ($methods as $method) {
					if ($method->id === $pluginId) {
						return $method;
					}
				}
			}
		}
		return false;
	}	
	public static function fc_config($name, $default = array()){
		global $pluginId;
		$method = self::fc_get_mathod();
		if ($method) {
			return $method->get_option($name);
		}
		return $default[$name];
	}	
	public static function fc_get_cep_data($cep){
		/*Obter dados viacep*/
		$data_cep = wp_remote_get('https://viacep.com.br/ws/' . $cep . '/json/', array());
		return json_decode(wp_remote_retrieve_body($data_cep));
	}	
	public static function fc_add_scripts(){
		$plugin_uri = str_replace('/includes', '', plugin_dir_url(__FILE__));

		//Adicionando estilos
		wp_enqueue_script("freteclick", $plugin_uri . "views/js/Freteclick.js", array('jquery', 'jquery-ui-autocomplete'), "1.0", true);

	}
	public static function fc_add_styles(){
		$plugin_uri = str_replace('/includes', '', plugin_dir_url(__FILE__));
	
		wp_enqueue_style( 'frtck_front_style', $plugin_uri . "views/css/frtck_front.css" );
	}

	public static function fc_display_product_layout(){
		if (get_option('freteclick_display_product') == 1) {
			global $pluginDir;

			include $pluginDir . "views/templates/display_product_layout.php";
		}
	}	
	public static function fc_options_page(){
		add_options_page("Frete Click", "Frete Click", "manage_options", "freteclick", array('FreteClick',"fc_options_page_layout"));
	}
	public static function fc_options_page_layout(){
		global $pluginDir;

		include $pluginDir . "views/templates/options_page_layout.php";
	}	
	public static function add_fc_shipping_methods( $methods ) {
		$methods['freteclick'] = 'freteclick_shipping_methods';
		return $methods;
	}
	
	public static function fc_pedido_alterado($order_id, $old_status, $new_status){
		$order = new WC_Order($order_id);
		$data = $order->get_data();
		$shipping = $order->get_items('shipping');
		$shipping_data = array();
		$array_data = array();

		foreach ($shipping as $key => $shipping_item) {
			$s_data = $shipping_item->get_data();
			$shipping_data[$key] = $s_data;
		}

		$status_espera = array(
			'pending',
			'processing',
			'on-hold'
		);

		if (in_array($data['status'], $status_espera)) {

		}

		error_log($old_status);
		error_log($new_status);
		error_log(json_encode($shipping_data));
		error_log(json_encode($data));
	}
	
	/* Página de Configurações */
	public static function fc_options_register_fields(){
		add_option("freteclick_quote_type", "simple");
		add_option('freteclick_display_product', '0');
		add_option('FC_API_KEY', '');
		add_option('FC_PRAZO_EXTRA', '');
		add_option('FC_PRAZO_VARIADO', '');
		register_setting('freteclick_options_page', 'FC_API_KEY', array(
			"type" => "string",
			"description" => ""
		));
		register_setting('freteclick_options_page', 'FC_PRAZO_EXTRA', array(
			"type" => "string",
			"description" => ""
		));
		register_setting('freteclick_options_page', 'FC_PRAZO_VARIADO', array(
			"type" => "string",
			"description" => ""
		));
		register_setting('freteclick_options_page', 'freteclick_display_product', array(
			"type" => "boolean",
			"description" => "Isso vai adicionar um campo de cálculo de frete nas páginas de produto"
		));
		register_setting('freteclick_options_page', 'freteclick_quote_type', array(
			"type" => "string",
			"description" => ""
		));
	}	
	public static function fc_missing_apikey(){
		printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe sua Chave de API</p></div>");
	}

	/* Formulário na página de produto */
	public static function rest_get_shipping(WP_REST_Request $request){
		$data = $request->get_params();


		$result = FreteClick::fc_calculate_shipping(array(
			"cart_subtotal" => $data["product_price"] * $data["product_quantity"],
			"destination" => array(
				"postcode" => $data["calc_shipping_postcode"]
			),
			"contents" => array(
				array(
					"product_id" => $data["product_id"],
					"quantity" => $data["product_quantity"],
					"data" => array(
						"name" => $data["product_name"],
						"weight" => $data["product_weight"],
						"height" => $data["product_height"],
						"width" => $data["product_width"],
						"length" => $data["product_length"]
					)
				)
			)
		));

		echo(json_encode($result));
		exit;
	}	
	
	public static function fc_get_quotes(QuoteRequest $QuoteRequest){

		try{
			$api_key = get_option('FC_API_KEY');			
			$SDK = new SDK($api_key);
			$cotafacil = $SDK->cotaFacilClient();			
			$array_resp = $cotafacil::quote($QuoteRequest);				
		
		} catch (Exception $ex) {
			$array_resp = array(
				'response' => array('success' => false, 'error' => $ex->getMessage())
			);
		}

		return $array_resp;
	}
	
	
	public static function addError($error){
		global $fc_errors;
		array_push($fc_errors, array(
			'code' => md5($error),
			'message' => $error
		));
		return self::getErrors();
	}
		public static function fc_missing_address(){
			printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe o endereço completo para a coleta dos produtos.</p></div>");
		}	
}
