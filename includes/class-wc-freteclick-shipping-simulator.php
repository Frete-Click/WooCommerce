<?php
use SDK\SDK;
use SDK\Models\QuoteRequest;
use SDK\Models\Package;
use SDK\Models\Origin;
use SDK\Models\Destination;
use SDK\Models\Config;

class WC_FreteClick_Shipping_Simulator {
    
	/**
     * Shipping simulator actions.
     */
    public function __construct()
	{

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		/*Hooks para status dos pedidos*/
		add_action('woocommerce_order_status_changed', array('WC_FreteClick_Shipping_Simulator','fc_pedido_alterado'), 10, 3);

		/* Hooks para página de configurações globais */
		add_action('admin_init', array('WC_FreteClick_Shipping_Simulator','fc_options_register_fields'));
		add_action('admin_menu', array('WC_FreteClick_Shipping_Simulator','fc_options_page'));

		/* Hook para busca frete no carrinho */
		add_action( 'woocommerce_product_meta_start', array('WC_FreteClick_Shipping_Simulator','fc_display_product_layout'), 10, 0 );

		/* registrando rota rest para buscar cotações */
		add_action("rest_api_init", function () {
			register_rest_route("freteclick", "/get_shipping", array(
				'methods' => 'POST',
				'callback' => array('WC_FreteClick_Shipping_Simulator','rest_get_shipping'),
				'permission_callback' => '__return_true',
			));
		});
	}
	
	/**
	 * 
	 */
	public static function fc_is_disabled()	
	{
	 	printf("<div class='notice notice-warning is-dismissible'><p>O Frete Click está desabilitado. Ative o Frete Click para voltar a usa-lo.</p></div>");
	 }	

	/**
	 * 
	 */
	public static function fc_wc_missing_notice()
	{
		printf("<div class='notice notice-warning'><p>O WooCommerce não está intalado, para usar o Frete Click é necessário <a href='https://br.wordpress.org/plugins/woocommerce/' target='blanck'>instalar o WooCommerce</a>.</p></div>");
	 }
		
	/**
	 * 
	 */
	protected static function deny_carriers()
	{
		return explode(",", get_option('fclick_deny_carriers'));
	}

	/**
	 * 
	 */
	protected static function fix_value($value) 
	{
		$value = trim($value);
		$value = str_replace(',', '.', $value);
		
		if (is_numeric($value)) {
			$value = (float) $value;
		}
	
		if (strpos($value, '.') === false) {
			return $value / 100;
		} else {
			return $value;
		}
	}

	/**
	 * 
	 */
	public static function fc_calculate_shipping($request = array())
	{
		/**
		 * get products
		 */
		global $woocommerce;
		$quote_request = new QuoteRequest();
	
		if (!empty($request['destination']['postcode'])) {
			
			$origin = new Origin();
			$origin->setCity(self::fc_config('FC_CITY_ORIGIN'));
			$origin->setState(self::fc_config('FC_STATE_ORIGIN'));
			$origin->setCountry(self::fc_config('FC_CONTRY_ORIGIN'));
			$quote_request->setOrigin($origin);
	
			$no_retrieve = (get_option('freteclick_noretrieve') === 0) ? false : true;
	
			$config = new Config();
			$config->setQuoteType(get_option("freteclick_quote_type"));
			$config->setOrder('total');
			$config->setNoRetrieve($no_retrieve);
			$config->setDenyCarriers(self::deny_carriers());
			$config->setAppType('WooCommerce');
	
			$quote_request->setConfig($config); 
			$items = isset($woocommerce->cart) ? $woocommerce->cart->get_cart() : [];
	
			if (count($items) > 0) {
				foreach ($items as $item) {
					$package = new Package();
					$product = wc_get_product($item['product_id']);
	
					// Verifica se é uma variação de produto
					if ($product->is_type('variable') && isset($item['variation_id'])) {
						$product_variation = wc_get_product($item['variation_id']);
						$weight = $product_variation->get_weight();
						$height = $product_variation->get_height();
						$width = $product_variation->get_width();
						$length = $product_variation->get_length();
						$price = $product_variation->get_price();
						$name = $product_variation->get_name();
					} else {
						// Caso não seja uma variação, usa os dados do produto principal
						$weight = $product->get_weight();
						$height = $product->get_height();
						$width = $product->get_width();
						$length = $product->get_length();
						$price = $item['line_total'];
						$name = $product->get_title();
					}
	
					// Ajusta os valores da package
					$package->setQuantity($item['quantity']);
					$package->setWeight($weight);
					$package->setHeight(self::fix_value($height)); // Convertendo para metros
					$package->setWidth(self::fix_value($width));   // Convertendo para metros
					$package->setDepth(self::fix_value($length));  // Convertendo para metros
					$package->setProductType($name);
					$package->setProductPrice($price);
					$quote_request->addPackage($package);					
				}					
			} else {				
				foreach ($request['contents'] as $key => $item) {					
					if (class_exists("WC_Product_Factory")) {
						$_pf = new WC_Product_Factory();
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
					$package->setHeight(self::fix_value($p_data['height'])); // Convertendo para metros
					$package->setWidth(self::fix_value($p_data['width']));   // Convertendo para metros
					$package->setDepth(self::fix_value($p_data['length']));  // Convertendo para metros
					$package->setProductType($p_data['name']);
					$package->setProductPrice($p_data['price']);
					$quote_request->addPackage($package);					
				}				
			}
	
			// Obtendo dados do CEP
			$data_cep = self::get_address($request['destination']['postcode']);
			if ($data_cep === null) {
				return error_log('Invalid address data: ' . json_encode($data_cep));
			}
	
			$destination = new Destination();
			$destination->setCity($data_cep['city']);
			$destination->setState($data_cep['state']);
			$destination->setCountry($data_cep['country']);
			$quote_request->setDestination($destination);
			
			return json_decode(self::fc_get_quotes($quote_request), false);
		}	
	}

	/**
	 * 
	 */
	public static function fc_get_mathod()
	{
		$pluginId = 'freteclick';
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

	/**
	 * 
	 */
	public static function fc_config($name, $default = array())
	{
		$pluginId = 'freteclick';
		$method = self::fc_get_mathod();
		if ($method) {
			return $method->get_option($name);
		}
		return $default[$name];
	}	

	/**
	 * Format string
	 */
	public static function format_cep($data)
	{
		return preg_replace("/[^0-9]/", "", $data);
	}

	/**
	 * 
	 */
	public static function get_address($data)
	{
		$cep = self::format_cep($data);
	
		$url_api = "https://api.freteclick.com.br/geo_places?input=$cep";
	
		$headers = array(         
			'Accept: application/json',
			'Content-Type: application/json',
			'api-token: '. get_option('FC_API_KEY')
		);
	
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url_api);
	
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Erro ao acessar API: ' . curl_error($ch);
			return null;
		}
	
		curl_close($ch); 
	
		$data = json_decode($response, true);
		if (isset($data['response']) && isset($data['response']['data']) && is_array($data['response']['data']) && count($data['response']['data']) > 0) {
			return $data['response']['data'][0];
		} else {
			return null;
		}
	}
	
	/**
	 * 
	 */
	public function enqueue_scripts()
	{	
		wp_enqueue_style( 'freteclick-shipping-simulator', plugins_url('views/css/simulator.css', plugin_dir_path(__FILE__)), array(), '1.0.28', 'all');
	}

	/**
	 * 
	 */
	public static function fc_display_product_layout()
	{
		if (get_option('freteclick_display_product') == 1) {
			include WC_FreteClick_Main::get_plugin_path() . "views/templates/shipping-simulator.php";
		}
	}	
	
	/**
	 * 
	 */
	public static function fc_options_page()
	{
		add_options_page("Frete Click", "Frete Click", "manage_options", "freteclick", array('WC_FreteClick_Shipping_Simulator',"fc_options_page_layout"));
	}
	
	/**
	 * 
	 */
	public static function fc_options_page_layout()
	{
		include WC_FreteClick_Main::get_plugin_path() .  "views/templates/options_page_layout.php";
	}	
	
	/**
	 *  Incluir contratação do pedido
	 */
	public static function fc_pedido_alterado($order_id, $old_status, $new_status)
	{
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

	/**
	 * 
	 */
	public static function invoice_tax()
	{
		if(get_option('fclick_invoice') === '1'){
			return  (WC()->cart->cart_contents_total / 100) * 3;
		}

		return 0;
	}
	
	/**
	 * 
	 */
	public static function fc_options_register_fields()
	{

		add_option("freteclick_quote_type", "simple");
		add_option('freteclick_display_product', '0');
		add_option('freteclick_noretrieve', '0');
		add_option('fclick_invoice', '0');
		add_option('fclick_deny_carriers', '');
		add_option('FC_API_KEY', '');
		add_option('FC_PRAZO_EXTRA', '0');
		add_option('FC_PRAZO_VARIADO', '0');

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
		register_setting('freteclick_options_page', 'freteclick_noretrieve', array(
			"type" => "boolean",
			"description" => "Exibe ou não transportadoras sem coletas"
		));	
		register_setting('freteclick_options_page', 'fclick_invoice', array(
			"type" => "boolean",
			"description" => ""
		));	
		register_setting('freteclick_options_page', 'fclick_deny_carriers', array(
			"type" => "string",
			"description" => ""
		));			
		register_setting('freteclick_options_page', 'freteclick_quote_type', array(
			"type" => "string",
			"description" => ""
		));
	}	
	
	/**
	 * 
	 */
	public static function fc_missing_apikey()
	{
		printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe sua Chave de API</p></div>");
	}

	/**
	 * Retorna para pagina do produto
	 */
	public static function rest_get_shipping(WP_REST_Request $request)
	{
		$data = $request->get_params();

		$result = self::fc_calculate_shipping(array(
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

		echo wp_json_encode($result);
		die;
	}	
	
	/**
	 * 
	 */
	public static function fc_get_quotes(QuoteRequest $QuoteRequest)
	{
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
	
}
new WC_FreteClick_Shipping_Simulator();
