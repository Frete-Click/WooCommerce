<?php
use SDK\SDK;
use SDK\Models\QuoteRequest;
use SDK\Models\Package;
use SDK\Models\Origin;
use SDK\Models\Destination;
use SDK\Models\Config;
use SDK\Core\Client\API as FCAPI;
use SDK\Client\Order as FCOrder;
use SDK\Client\People as FCPeople;

class WC_FreteClick_Shipping_Simulator {
    
	/**
     * Shipping simulator actions.
     */
    public function __construct()
	{

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		/*Hooks para status dos pedidos*/
		add_action('woocommerce_order_status_changed', array('WC_FreteClick_Shipping_Simulator','fc_pedido_alterado'), 10, 3);

		/* Hook para contratação manual a partir da tela do pedido */
		add_filter('woocommerce_order_actions', array('WC_FreteClick_Shipping_Simulator','fc_add_order_action'));
		add_action('woocommerce_order_action_freteclick_contract', array('WC_FreteClick_Shipping_Simulator','fc_contract_order_action'));

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

		/**
		 * Verifica se o plugin Brazilian Market on WooCommerce está instalado e ativo
		 */
		add_action('admin_init', function () {
			if (!class_exists('Extra_Checkout_Fields_For_Brazil')) {
				add_action('admin_notices', function () {
					echo '<div class="notice notice-error"><p>';
					echo '<strong>Frete Click</strong> requer o plugin ';
					echo '<strong>Brazilian Market on WooCommerce</strong> instalado e ativo.';
					echo '</p></div>';
				});
			}
		});

	}
	
	/**
	 * Verifica se o Frete Click está desabilitado
	 */
	public static function fc_is_disabled()	
	{
	 	printf("<div class='notice notice-warning is-dismissible'><p>O Frete Click está desabilitado. Ative o Frete Click para voltar a usa-lo.</p></div>");
	}	

	/**
	 * Cria log no arquivo freteclick.log
	 * 
	 * @param string $label
	 * @param mixed $data
	 */
	protected static function fc_log($label, $data = null)
	{
		$dir = WOO_FRETECLICK_PATH . 'logs';
		if (!file_exists($dir)) {
			if (function_exists('wp_mkdir_p')) {
				wp_mkdir_p($dir);
			} else {
				@mkdir($dir, 0777, true);
			}
		}
		$file = $dir . '/freteclick.log';
		$line = json_encode(array(
			'ts' => gmdate('c'),
			'label' => $label,
			'data' => $data
		), JSON_UNESCAPED_UNICODE);
		@file_put_contents($file, $line . PHP_EOL, FILE_APPEND);
	}

	/**
	 * Exibe notificação de WooCommerce não instalado
	 */
	public static function fc_wc_missing_notice()
	{
		printf("<div class='notice notice-warning'><p>O WooCommerce não está intalado, para usar o Frete Click é necessário <a href='https://br.wordpress.org/plugins/woocommerce/' target='blanck'>instalar o WooCommerce</a>.</p></div>");
	 }
		
	/**
	 * Retorna lista de transportadoras negadas
	 * 
	 * @return array
	 */
	protected static function deny_carriers()
	{
		return explode(",", get_option('fclick_deny_carriers'));
	}

	/**
	 * Corrige valor para formato decimal
	 * 
	 * @param string $value
	 * @return float
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
	 * Calcula frete com base na cotação da Frete Click
	 * 
	 * @param array $request
	 * @return array
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
				error_log('Frete Click: dados de endereço inválidos para o CEP ' . $request['destination']['postcode']);
				return null;
			}
	
			$destination = new Destination();
			$destination->setCity($data_cep['city']);
			$destination->setState($data_cep['state']);
			$destination->setCountry($data_cep['country']);
			$quote_request->setDestination($destination);
			
			$resposta = self::fc_get_quotes($quote_request);
			if (!is_string($resposta)) {
				error_log('Frete Click: falha na cotação: ' . wp_json_encode($resposta));
				return null;
			}
			return json_decode($resposta, false);
		}	
	}

	/**
	 * Retorna método de envio Frete Click
	 * 
	 * @return object|bool
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
	 * Retorna configuração do método de envio Frete Click
	 * 
	 * @param string $name
	 * @param array $default
	 * @return mixed
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
	 * Formata CEP para remover caracteres não numéricos
	 * 
	 * @param string $data
	 * @return string
	 */
	public static function format_cep($data)
	{
		return preg_replace("/[^0-9]/", "", $data);
	}

	/**
	 * Obtém dados de endereço a partir de um CEP
	 * 
	 * @param string $data
	 * @return array|null
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
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			error_log('Frete Click: erro ao acessar API: ' . curl_error($ch));
			return null;
		}
	
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
		wp_enqueue_style( 'freteclick-shipping-simulator', plugins_url('views/css/simulator.css', plugin_dir_path(__FILE__)), array(), '1.0.29', 'all');
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
	 * Adiciona ação manual de contratação na tela do pedido
	 *
	 * @param array $actions
	 * @return array
	 */
	public static function fc_add_order_action($actions)
	{
		$actions['freteclick_contract'] = __('Contratar na Frete Click', 'freteclick-shipping-gateway');
		return $actions;
	}

	/**
	 * Executa a contratação manual na Frete Click a partir da tela do pedido
	 *
	 * @param WC_Order $order
	 */
	public static function fc_contract_order_action($order)
	{
		if (!$order) {
			return;
		}
		$order->add_order_note('Frete Click: contratação manual solicitada.');
		self::fc_pedido_alterado($order->get_id(), '', $order->get_status());
	}

	/**
	 * Garante endereço completo para a API Frete Click.
	 *
	 * A API exige country, state, city, district, street, postal_code (8 dígitos)
	 * e number (numérico) preenchidos no choose-quote. Campos de bairro/rua vazios
	 * são preenchidos via consulta de CEP (geo_places) e número ausente vira "0".
	 *
	 * @param array $address
	 * @return array
	 */
	protected static function fc_complete_address($address)
	{
		$cep = isset($address['postal_code']) ? $address['postal_code'] : '';

		if (
			empty($address['district']) ||
			empty($address['street']) ||
			empty($address['city']) ||
			empty($address['state'])
		) {
			$geo = self::get_address($cep);
			if ($geo) {
				if (empty($address['district']) && !empty($geo['district'])) $address['district'] = $geo['district'];
				if (empty($address['street']) && !empty($geo['street'])) $address['street'] = $geo['street'];
				if (empty($address['city']) && !empty($geo['city'])) $address['city'] = $geo['city'];
				if (empty($address['state']) && !empty($geo['state'])) $address['state'] = $geo['state'];
			}
		}

		if (!isset($address['number']) || !is_numeric($address['number'])) {
			$address['number'] = '0';
		}

		if (empty($address['country'])) {
			$address['country'] = 'Brasil';
		}

		return $address;
	}

	/**
	 * Realiza a contratação do pedido quando o status muda para "processing"
	 */
	public static function fc_pedido_alterado($order_id, $old_status, $new_status)
	{
		try {

			self::fc_log('fc_pedido_alterado:start', array('order_id' => $order_id, 'old' => $old_status, 'new' => $new_status));

			/**
			 * API Key para autenticação na Frete Click
			 */
			$api_key = get_option('FC_API_KEY');
			if (empty($api_key)) {
				self::fc_log('fc_pedido_alterado:no_api_key', array('order_id' => $order_id));
				return;
			}

			$order = wc_get_order($order_id);
			if (!$order) {
				self::fc_log('fc_pedido_alterado:no_order', array('order_id' => $order_id));
				return;
			}
			
			// Evita múltiplas contratações para o mesmo pedido
			if ($order->get_meta('_freteclick_checkout_done') === 'yes') {
				self::fc_log('fc_pedido_alterado:already_done', array('order_id' => $order_id));
				return;
			}

			// Considera apenas estados iniciais de pagamento
			$status_espera = array('pending', 'processing', 'on-hold');
			if (!in_array($order->get_status(), $status_espera)) {
				self::fc_log('fc_pedido_alterado:status_skip', array('order_id' => $order_id, 'status' => $order->get_status()));
				return;
			}

			$shipping_items = $order->get_items('shipping');
			if (empty($shipping_items)) {
				self::fc_log('fc_pedido_alterado:no_shipping', array('order_id' => $order_id));
				return;
			}

			$fc_shipping_item = null;
			foreach ($shipping_items as $item) {
				$data = $item->get_data();
				if (isset($data['method_id']) && $data['method_id'] === 'freteclick') {
					$fc_shipping_item = $item;
					break;
				}
			}
			if (!$fc_shipping_item) {
				self::fc_log('fc_pedido_alterado:no_freteclick_item', array('order_id' => $order_id));
				return;
			}

			$quote_id = $fc_shipping_item->get_meta('Cotação');
			$fc_order_api_id = $fc_shipping_item->get_meta('Código de Rastreamento');
			if (empty($fc_order_api_id) || empty($quote_id)) {
				self::fc_log('fc_pedido_alterado:missing_ids', array('order_id' => $order_id, 'freteclick_order' => $fc_order_api_id, 'quote' => $quote_id));
				return;
			}
			self::fc_log('fc_pedido_alterado:ids_ok', array('order_id' => $order_id, 'freteclick_order' => $fc_order_api_id, 'quote' => $quote_id));

			/**
			 * Obtém o preço da cotação pela API; se falhar, usa o total do frete do pedido
			 */
			$price = null;
			try {
				$api = new FCAPI($api_key);
				$orderClient = new FCOrder($api);
				$price = $orderClient->getQuotationTotal((int) $quote_id);
			} catch (\Exception $e) {
				$price = (float) $fc_shipping_item->get_total();
			}
			if ($price === null) {
				$price = (float) $fc_shipping_item->get_total();
			}

			/**
			 * Endereço de origem (loja) a partir das configurações do plugin
			 */
			$retrieve_address = array(
				'id' => null,
				'country' => self::fc_config('FC_CONTRY_ORIGIN'),
				'state' => self::fc_config('FC_STATE_ORIGIN'),
				'city' => self::fc_config('FC_CITY_ORIGIN'),
				'district' => self::fc_config('FC_DISTRICT_ORIGIN'),
				'postal_code' => self::fc_config('FC_CEP_ORIGIN'),
				'street' => self::fc_config('FC_STREET_ORIGIN'),
				'number' => self::fc_config('FC_NUMBER_ORIGIN'),
				'complement' => self::fc_config('FC_COMPLEMENT_ORIGIN')
			);
			$retrieve_address = self::fc_complete_address($retrieve_address);

			$shipping_country = $order->get_shipping_country() ?: 'BR';
			if ($shipping_country === 'BR') {
				$shipping_country = 'Brasil';
			}

			$delivery_address = array(
				"id" => (string) $order->get_customer_id(),
				"country" => $shipping_country,
				"state" => $order->get_shipping_state() ?: $order->get_billing_state(),
				"city" => $order->get_shipping_city() ?: $order->get_billing_city(),
				"district" => $order->get_meta('_shipping_neighborhood')
					?: $order->get_meta('_billing_neighborhood'),
				"postal_code" =>  preg_replace('/\D/', '', $order->get_shipping_postcode() ?: $order->get_billing_postcode()),
				"street" => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
				"number" => $order->get_meta('_shipping_number')
					?: $order->get_meta('_billing_number'),
				"complement" => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
			);
			$delivery_address = self::fc_complete_address($delivery_address);

			$api = new FCAPI($api_key);
			$orderClient = new FCOrder($api);
			$peopleClient = new FCPeople($api);
			
			/**
			 * Obter dados do cliente remetente (loja)
			 */
			$me = $peopleClient->getMe();
			$companyId = null;
			$myPeopleId = null;
			if (is_object($me)) {
				if (isset($me->companyId)) $companyId = $me->companyId;
				if (isset($me->company_id)) $companyId = $me->company_id;
				if (isset($me->peopleId)) $myPeopleId = $me->peopleId;
				if (isset($me->people_id)) $myPeopleId = $me->people_id;
				if (isset($me->id) && !$myPeopleId) $myPeopleId = $me->id;
			} else {
				self::fc_log('fc_pedido_alterado:getMe_failed', array('order_id' => $order_id, 'me' => $me));
			}
			self::fc_log('fc_pedido_alterado:getMe', array('order_id' => $order_id, 'company_id' => $companyId, 'my_people_id' => $myPeopleId, 'me' => $me));

			/**
			 * Obter dados do cliente destinatário (cliente do pedido)
			 */
			$customer_email = $order->get_billing_email();
			$customerId = $peopleClient->getIdByEmail($customer_email);
			self::fc_log('fc_pedido_alterado:customer_email', array('order_id' => $order_id, 'email' => $customer_email, 'customer_id' => $customerId));

			$deliveryContactId = null;
			if (!empty($customerId)) {
				if (isset($customerId)) {
					$deliveryContactId = $customerId;
				}
			} else {				
				$person_type = $order->get_meta('_billing_persontype');

				$type = 'F';
				$document = '';

				if ($person_type == '1') { // Pessoa Física
					$type = 'F';
					$document = preg_replace('/\D/', '', (string) $order->get_meta('_billing_cpf'));
				} elseif ($person_type == '2') { // Pessoa Jurídica
					$type = 'J';
					$document = preg_replace('/\D/', '', (string) $order->get_meta('_billing_cnpj'));
				}

				$customer_payload = array(
					"name" => trim(
						$order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()
					),
					"alias" => $order->get_shipping_first_name(),
					"type" => $type,
					"document" => $document,
					"email" => $order->get_billing_email(),
					"address" => $delivery_address
				);

				/**
				 * Criar um novo cliente na Frete Click
				 */
				$new_customer = $peopleClient->createCustomer($customer_payload);
				
				/**
				 * Se o cliente foi criado com sucesso, obter o ID do cliente
				 */
				if (!empty($new_customer)) {
					if (isset($new_customer)) {
						$deliveryContactId = $new_customer;
					}
				}
			}

			$payload = array(
				'quote' => (string) $quote_id,
				'price' => (float) $price,
				'payer' => (string) $companyId,
				'retrieve' => array(
					'id' => (string) $companyId,
					'address' => $retrieve_address,
					'contact' => (string) $myPeopleId
				),
				'delivery' => array(
					'id' => (string) $deliveryContactId,
					'address' => $delivery_address,
					'contact' => (string) $deliveryContactId
				)
			);

			/**
			 * Finalizar o checkout na Frete Click
			 */
			self::fc_log('fc_pedido_alterado:finish_checkout_payload', array(
				'order_id' => $order_id,
				'freteclick_order' => $fc_order_api_id,
				'quote' => $quote_id,
				'price' => $price,
				'payload' => $payload
			));
			$peopleId = $orderClient->finishCheckout((int) $fc_order_api_id, $payload);

			if ($peopleId) {
				$order->update_meta_data('_freteclick_checkout_done', 'yes');
				$order->update_meta_data('_freteclick_people_id', $peopleId);
				$order->save();
				self::fc_log('fc_pedido_alterado:success', array('order_id' => $order_id, 'people_id' => $peopleId, 'freteclick_order' => $fc_order_api_id));
			} else {
				self::fc_log('fc_pedido_alterado:finish_checkout_failed', array('order_id' => $order_id, 'freteclick_order' => $fc_order_api_id, 'quote' => $quote_id, 'company_id' => $companyId, 'my_people_id' => $myPeopleId, 'delivery_contact_id' => $deliveryContactId));
				$order->add_order_note('Frete Click: falha ao contratar pedido ' . $fc_order_api_id . ' (cotação ' . $quote_id . '). Verifique o log do Frete Click.');
			} 
		} catch (\Exception $e) {
			error_log('Frete Click contratação error: ' . $e->getMessage());
			self::fc_log('fc_pedido_alterado:exception', array('order_id' => $order_id, 'error' => $e->getMessage()));
		}
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
		
		} catch (\Throwable $ex) {
			$array_resp = array(
				'response' => array('success' => false, 'error' => $ex->getMessage())
			);
		}

		return $array_resp;
	}
	
}
new WC_FreteClick_Shipping_Simulator();
