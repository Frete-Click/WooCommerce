<?php
/*
Plugin Name:  FreteClick
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
	if (is_admin()){
		fc_add_scripts();
	}
	
	function fc_shipping_methods() {
		/*Adicionar os métidos de entrega*/
		if ( ! class_exists( 'Fc_shipping_methods' ) ) {
			class Fc_shipping_methods extends WC_Shipping_Method {
				public $url_shipping_quote;
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					global $url_freteclick_settings;
					$this->id                 = 'freteclick';
					$this->method_title       = __( 'Frete Click' ); 
					$this->method_description = __( 'Cálculo do frete com o serviço da web Frete Click' );
					$this->title              = "Frete Click";

					$url_freteclick_settings = "admin.php?page=wc-settings&tab=shipping&section=".$this->id;
					$this->url_shipping_quote = "https://api.freteclick.com.br/sales/shipping-quote.json";

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
				public function calculate_shipping( $package ) {/*
					try {
							$product_price = number_format($data['product-total-price'], 2, ',', '.');
							$data['product-total-price'] = $product_price;
							
							$ch = curl_init();
							$data['api-key'] = Configuration::get('FC_API_KEY');
							curl_setopt($ch, CURLOPT_URL, $this->url_shipping_quote);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
							$resp = curl_exec($ch);
							curl_close($ch);
							$arrJson = $this->orderByPrice($this->filterJson($resp));
							if (!$this->cookie->fc_valorFrete){
								$this->cookie->fc_valorFrete = $arrJson->response->data->quote[0]->total;
							}
							foreach ($arrJson->response->data->quote as $key => $quote) {
								$quote_price = number_format($quote->total, 2, ',', '.');
								$arrJson->response->data->quote[$key]->raw_total = $quote->total;
								$arrJson->response->data->quote[$key]->total = "R$ {$quote_price}";
							}
							$this->cookie->write();
							return Tools::jsonEncode($arrJson);
					} catch (Exception $ex) {
						$arrRetorno = array();
						$arrRetorno = array(
							'response' => array('success' => false, 'error' => $ex->getMessage())
						);
						return Tools::jsonEncode($arrRetorno);
					}*/

					echo "<pre>";
					echo var_dump($package);
					echo "</pre>";


					$rate = array(
						'id' => $this->id,
						'label' => $this->title,
						'cost' => '10.99',
						'calc_tax' => 'per_item'
					);
					// Register the rate
					$this->add_rate( $rate );
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
			}
		}
	}
	add_action( 'woocommerce_shipping_init', 'fc_shipping_methods' );
	function add_fc_shipping_methods( $methods ) {
		$methods['freteclick'] = 'Fc_shipping_methods';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_fc_shipping_methods' );
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
?>