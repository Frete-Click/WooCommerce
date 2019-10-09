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
	$pluginDir = plugin_dir_path(__FILE__);

	require_once("includes/variables.php");
	require_once("includes/functions.php");

	if (is_admin()){
		fc_add_scripts();
	}

	function fc_shipping_methods() {
		/*Adicionar os métidos de entrega*/
		if ( ! class_exists( 'Fc_shipping_methods' ) ) {
			class Fc_shipping_methods extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct($instance_id = 0) {
					global $pluginId, $pluginName, $pluginDescription, $pluginCountries, $pluginSupports;

					$this->instance_id = absint( $instance_id );
					$this->id = $pluginId;
					$this->title = $pluginName;
					$this->method_description = $pluginDescription;
					$this->method_title = $pluginName; 
					$this->availability = 'including';
					$this->countries = $pluginCountries;

					$this->supports = $pluginSupports;

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
					$this->instance_form_fields = array(
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
						)
					);
			   }
			  	public function is_available( $package ){
					return true;
			  	}
				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping($package = array()){
					$array_resp = fc_calculate_shipping($package);
					
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
				function fc_check_settings($set){
					if ($set['FC_IS_ACTIVE'] != 'yes' && isset($set['FC_IS_ACTIVE'])){
						add_action( 'admin_notices', 'fc_is_disabled' );
					}
					else if (strlen(get_option('FC_API_KEY')) <= 0){
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

	/*Hooks para status dos pedidos*/
	add_action('woocommerce_order_status_changed', 'fc_pedido_alterado', 10, 3);

	/* Hooks para página de configurações globais */
	add_action('admin_init', 'fc_options_register_fields');
	add_action('admin_menu', 'fc_options_page');

	/* Hook para busca frete no carrinho */
	add_action( 'woocommerce_product_meta_start', 'fc_display_product_layout', 10, 0 );
}
else {
	add_action( 'admin_notices', 'fc_wc_missing_notice' );
}
?>