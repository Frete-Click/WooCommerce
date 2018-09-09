<?php
/*
Plugin Name:  Frete Click WooCommerce
Plugin URI:   https://freteclick.com.br/
Description:  Cálculo do frete com o serviço da web Frete Click
Version:      1.0
Author:       Guilherme Cristino
Author URI:   http://twitter.com/guilhermeCDP7
License:      Todos os Direitos Reservados
*/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	/*Variáveis globais*/
	$url_freteclick_settings;
	$url_shipping_quote = "https://api.freteclick.com.br/sales/shipping-quote.json";
	$url_origin_company = "https://app.freteclick.com.br/sales/add-quote-origin-company.json";
	$url_destination_client = "https://app.freteclick.com.br/sales/add-quote-destination-client.json";
	if (is_admin()){
		fc_add_scripts();
	}
	
	function fc_shipping_methods() {
		/*Adicionar os métidos de entrega*/
		if ( ! class_exists( 'Fc_shipping_methods' ) ) {
			class Fc_shipping_methods extends WC_Shipping_Method {
				public $error;
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					global $url_freteclick_settings;
					$this->id = 'freteclick';
					$this->method_title = __( 'Frete Click' ); 
					$this->method_description = __( 'Cálculo do frete com o serviço da web Frete Click' );
					$this->title = "Frete Click";
					$this->availability = 'including';
					$this->countries = array('BR');

					$url_freteclick_settings = "admin.php?page=wc-settings&tab=shipping&section=".$this->id;
					$this->error = array();

					$this->init();
				}
				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields();
					$this->init_settings();
					
					$this->enabled = isset($this->settings['FC_IS_ACTIVE']) ? $this->settings['FC_IS_ACTIVE'] : 'yes';

					$this->fc_check_settings($this->settings);

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}
				function init_form_fields() {
					$this->form_fields = array(
						'FC_IS_ACTIVE' => array(
							'title' => __( 'Status do Frete Click' ),
							'type' => 'checkbox',
							'description' => __( 'Para ativar os métodos de entrega do frete click você deve checar essa opção.' ),
							'label' => 'Ativar o Frete Click como Método de Entrega?'
					   ),
						'FC_CEP_ORIGIN' => array(
							 'title' => __( 'CEP de Origem' ),
							 'type' => 'text',
							 'description' => __( '' ),
							 'class' => 'fc-input-cep cep-origin'
						),
						'FC_STREET_ORIGIN' => array(
							 'title' => __( 'Rua' ),
							 'type' => 'text',
							 'description' => __( '' ),
							 'class' => 'street-origin'
						),
						'FC_NUMBER_ORIGIN' => array(
							 'title' => __( 'Número' ),
							 'type' => 'text',
							 'description' => __( '' )
						),
						'FC_COMPLEMENT_ORIGIN' => array(
							 'title' => __( 'Complemento' ),
							 'type' => 'text',
							 'description' => __( '' )
						),
						'FC_DISTRICT_ORIGIN' => array(
							 'title' => __( 'Bairro' ),
							 'type' => 'text',
							 'description' => __( '' ),
							 'class' => 'district-origin'
						),
						'FC_CITY_ORIGIN' => array(
							 'title' => __( 'Cidade de Origem' ),
							 'type' => 'text',
							 'description' => __( '' ),
							 'class' => 'city-origin'
						),
						'FC_STATE_ORIGIN' => array(
							 'title' => __( 'Estado de Origem' ),
							 'type' => 'select',
							 'description' => __( '' ),
							 'options' => array(
								 'AC' => 'Acre',
								 'AL' => 'Alagoas',
								 'AP' => 'Amapá',
								 'AM' => 'Amazonas',
								 'BA' => 'Bahia',
								 'CE' => 'Ceará',
								 'DF' => 'Distrito Federal',
								 'ES' => 'Espírito Santo',
								 'GO' => 'Goiás',
								 'MA' => 'Maranhão',
								 'MT' => 'Mato Grosso',
								 'MS' => 'Mato Grosso do Sul',
								 'MG' => 'Minas Gerais',
								 'PA' => 'Pará',
								 'PB' => 'Paraíba',
								 'PR' => 'Paraná',
								 'PE' => 'Pernambuco',
								 'PI' => 'Piauí',
								 'RJ' => 'Rio de Janeiro',
								 'RN' => 'Rio Grande do Norte',
								 'RS' => 'Rio Grande do Sul',
								 'RO' => 'Rondônia',
								 'RR' => 'Roraima',
								 'SC' => 'Santa Catarina',
								 'SP' => 'São Paulo',
								 'SE' => 'Sergipe',
								 'TO' => 'Tocantins'
							 ),
							 'class' => 'state-origin'
						),
						'FC_CONTRY_ORIGIN' => array(
							 'title' => __( 'Paìs de Origem' ),
							 'type' => 'text',
							 'description' => __( '' ),
							 'default' => 'Brasil',
							 'class' => 'country-origin'
						),
						'FC_API_KEY' => array(
							 'title' => __( 'Chave da API' ),
							 'type' => 'text',
							 'description' => __( '' )
						)
					);
			   }
				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package ) {
					session_start();
					$array_resp = array();
					/*Dados de origem*/
					$array_data = array(
						'city-origin' => $this->settings['FC_CITY_ORIGIN'],
						'cep-origin' => $this->settings['FC_CEP_ORIGIN'],
						'street-origin' => $this->settings['FC_STREET_ORIGIN'],
						'address-number-origin' => $this->settings['FC_NUMBER_ORIGIN'],
						'complement-origin' => strlen($this->settings['FC_COMPLEMENT_ORIGIN']) > 0 ? $this->settings['FC_COMPLEMENT_ORIGIN'] : "",
						'district-origin' => $this->settings['FC_DISTRICT_ORIGIN'],
						'state-origin' => $this->settings['FC_STATE_ORIGIN'],
						'country-origin' => $this->settings['FC_CONTRY_ORIGIN']
					);
					/*Dados do produto*/
					$_pf = new WC_Product_Factory();
					$prod_nomes = array();
					foreach($package['contents'] as $key => $item){
						$product = $_pf->get_product($item['product_id']);
						$p_data = $product->get_data();
						$array_data['product-package'][$key]['qtd'] = $item['quantity'];
						$array_data['product-package'][$key]['weight'] = number_format($p_data['weight'], 10, ',', '');
						$array_data['product-package'][$key]['height'] = number_format($p_data['height'] / 100, 10, ',', '');
						$array_data['product-package'][$key]['width'] = number_format($p_data['width'] / 100, 10, ',', '');
						$array_data['product-package'][$key]['depth'] = number_format($p_data['length'] / 100, 10, ',', '');
						array_push($prod_nomes, $p_data['name']);
					}
					$array_data['product-type'] = implode(',', array_values($prod_nomes));
					$array_data['product-total-price'] = number_format($package['cart_subtotal'], 2, ',', '.');
					/*Dados do destino*/
					$dest = $package['destination'];
					$data_cep = $this->fc_get_cep_data($dest['postcode']);

					if (!$data_cep->erro){
						$array_data['city-destination'] = $data_cep->localidade;
						$array_data['street-destination'] = preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $data_cep->logradouro);
						$array_data['district-destination'] = $data_cep->bairro;
						$array_data['state-destination'] = $data_cep->uf;
						$array_data['country-destination'] = 'Brasil';
						$array_data['complement-destination'] = $data_cep->complemento;
					}
					else{
						$array_data['city-destination'] = $dest['city'];
						$array_data['street-destination'] = preg_replace('/[^A-Z a-z]/', '', preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $dest['address']));
						$array_data['district-destination'] = $dest['address_2'];
						$array_data['state-destination'] = $dest['state'];
						$array_data['country-destination'] = $dest['country'];
						$array_data['complement-destination'] = "";
					}					
					$array_data['cep-destination'] = $dest['postcode'];
					$dest_number = preg_replace('/[^0-9]/', '', $dest['address']);
					$array_data['address-number-destination'] = strlen($dest_number) > 0 ? $dest_number : 1;
					
					/*Fazer cotação*/
					$quote_key = md5(json_encode($array_data));
					if (isset($_SESSION[$quote_key])){
						$array_resp = json_decode($_SESSION[$quote_key]);
					}
					else{
						$array_resp = $this->fc_get_quotes($array_data);
						if ($array_resp->response->data != false){
							$_SESSION[$quote_key] = json_encode($array_resp);
						}
					}
					if ($array_resp->response->data != false){
						foreach ($array_resp->response->data->quote as $key => $quote){
							$quote = (array) $quote;
							$carrier_data = array(
								'id' => $quote['quote-id'],
								'label' => $quote['carrier-alias'],
								'cost' => $quote['total'],
								'calc_tax' => 'per_item',
								'meta_data' => array(
									'Código de Rastreamento' => $quote['order-id'],
									'Nome da Transportadora' => $quote['carrier-name'],
									'Cotação' => $quote['quote-id']
								)
							);
							$this->add_rate( $carrier_data );
						}
					}
					else{
						error_log(json_encode($array_data));
						error_log(json_encode($array_resp));
					}
				}
				public function fc_get_cep_data($cep){					
					/*Obter dados viacep*/
					$_cep = curl_init();
					curl_setopt($_cep, CURLOPT_URL, 'https://viacep.com.br/ws/'.$cep.'/json/');
					curl_setopt($_cep, CURLOPT_RETURNTRANSFER, true);
					$data_cep = json_decode(curl_exec($_cep));
					curl_close($_cep);
					return $data_cep;
				}
				function fc_get_quotes($array_data){
					$array_resp = array();
					try {							
						$ch = curl_init();
						$array_data['api-key'] = $this->settings['FC_API_KEY'];
						curl_setopt($ch, CURLOPT_URL, $url_shipping_quote);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array_data));
						$resp = curl_exec($ch);
						curl_close($ch);
						$array_resp = $this->orderByPrice($this->filterJson($resp));
					} catch (Exception $ex) {
						$array_resp = array(
							'response' => array('success' => false, 'error' => $ex->getMessage())
						);
					}
					return $array_resp;
				}
				function fc_check_settings($set){
					if ($set['FC_IS_ACTIVE'] != 'yes' && isset($set['FC_IS_ACTIVE'])){
						add_action( 'admin_notices', 'fc_is_disabled' );
					}
					else if (strlen($set['FC_API_KEY']) <= 0){
						add_action( 'admin_notices', 'fc_missing_apikey' );
					}
					else if (strlen($set['FC_CEP_ORIGIN']) <= 0 || strlen($set['FC_CITY_ORIGIN']) <= 0 || strlen($set['FC_STREET_ORIGIN']) <= 0 || strlen($set['FC_NUMBER_ORIGIN']) <= 0 || strlen($set['FC_STATE_ORIGIN']) <= 0 || strlen($set['FC_CONTRY_ORIGIN']) <= 0 || strlen($set['FC_DISTRICT_ORIGIN']) <= 0){
						add_action( 'admin_notices', 'fc_missing_address' );
					}
				}				
				public function orderByPrice($arrJson)
				{
					$quotes = (array) $arrJson->response->data->quote;
					usort($quotes, function ($a, $b) {
						return $a->total > $b->total;
					});
					$arrJson->response->data->quote = $quotes;
					return $arrJson;
				}
				public function filterJson($json)
				{
					$arrJson = json_decode($json);
					if (!$arrJson) {
						$this->addError('Erro ao recuperar dados');
					}
					if ($arrJson->response->success === false) {
						if ($arrJson->response->error) {
							foreach ($arrJson->response->error as $error) {
								$this->addError($error->message);
							}
						}
						$this->addError('Erro ao recuperar dados');
					}
					return $this->getErrors() ? : $arrJson;
				}
				public function getErrors()
				{
					return $this->error ? array(
						'response' => array(
							'data' => 'false',
							'count' => 0,
							'success' => false,
							'error' => $this->error
						)
					) : false;
				}
				public function addError($error)
				{
					array_push($this->error, array(
						'code' => md5($error),
						'message' => $error
					));
					return $this->getErrors();
				}
			}
		}
	}
	add_action( 'woocommerce_shipping_init', 'fc_shipping_methods' );
	function add_fc_shipping_methods( $methods ) {
		$methods['freteclick'] = 'Fc_shipping_methods';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_fc_shipping_methods' );

	/*Hooks para status dos pedidos*/
	add_action('woocommerce_order_status_changed', 'fc_pedido_alterado', 10, 3);
}
else {
	add_action( 'admin_notices', 'fc_wc_missing_notice' );
}

function fc_add_scripts(){
	$plugin_uri = plugin_dir_url( __FILE__ );
	
	//Adicionando estilos
	wp_enqueue_script("freteclick", $plugin_uri."views/js/Freteclick.js", array( 'jquery', 'jquery-ui-autocomplete' ), "1.0", true);

};
/*Funções*/
function fc_wc_missing_notice(){
	printf("<div class='notice notice-warning'><p>O WooCommerce não está intalado, para usar o Frete Click é necessário <a href='https://br.wordpress.org/plugins/woocommerce/' target='blanck'>instalar o WooCommerce</a>.</p></div>");
};
function fc_missing_apikey(){
	global $url_freteclick_settings;
	printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, <a href='$url_freteclick_settings'>informe sua Chave de API</a></p></div>");
};
function fc_is_disabled(){
	global $url_freteclick_settings;
	printf("<div class='notice notice-warning is-dismissible'><p>O Frete Click está desabilitado. <a href='$url_freteclick_settings'>Ative</a> o Frete Click para voltar a usa-lo.</p></div>");
};
function fc_missing_address(){
	global $url_freteclick_settings;
	printf("<div class='notice notice-warning is-dismissible'><p>Por favor, para que o Frete Click funcione, informe o <a href='$url_freteclick_settings'>endereço completo</a> para a coleta dos produtos.</p></div>");
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
