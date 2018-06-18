<?php
/*
Plugin Name:  FreteClick
Plugin URI:   https://freteclick.com.br/
Description:  Cálculo do frete com o serviço da web Freteclick
Version:      1.0
Author:       Guilherme Cristino
Author URI:   http://twitter.com/guilhermeCDP7
License:      Todos os Direitos Reservados
*/
if (!defined('ABSPATH')) {
    exit;
}
if (!function_exists( 'is_woocommerce_activated' )) {
	function is_woocommerce_activated() {
		if (class_exists( 'woocommerce' )) { return true; } else { return false; }
	}
}

function fc_init(){
	if (is_woocommerce_activated()){
		
		add_filter( 'woocommerce_integrations', 'fc_include_integrations' );
		add_action( 'woocommerce_shipping_init', 'fc_add_shipping_method' );
		add_filter( 'woocommerce_shipping_methods', 'fc_include_methods' );	
		
		if (is_admin()){
			fc_add_scripts();
			add_action( 'admin_menu', 'fc_registerItemSettings' );
			add_action( 'admin_init', 'fc_registerSettings' );
		}
		else if (is_checkout()){
			fc_add_scripts();
		}
	}
	else {
		add_action( 'admin_notices', array( __CLASS__, 'woocommerce_missing_notice' ) );
	}
};

function fc_include_integrations( $integrations ) {
	
	$integrations[] = 'FreteClick';
	return $integrations;
}

function fc_include_methods( $methods ) {
    $methods['freteclick'] = 'Fc_Shipping_Method';
    return $methods;
}

function fc_add_shipping_method(){
    if ( ! class_exists( 'Fc_Shipping_Method' ) ) {
        class Fc_Shipping_Method extends WC_Shipping_Method {
		
            public function __construct() {
                $this->id                 = 'freteclick'; 
                $this->method_title       = __( 'Frete Click', 'freteclick' );  
                $this->method_description = __( 'Cálculo do frete com o serviço da web Freteclick', 'freteclick' ); 
			
				// Availability & Countries
				$this->availability = 'including';
				$this->countries = array('BR');
 
                $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Frete Click', 'freteclick' );
				$this->supports              = array(
					'shipping-zones',
					'instance-settings'
				);
                $this->init();
            }
 
            function init() {
                // Load the settings API
                $this->init_form_fields(); 
                //$this->init_settings(); 
				
				$this->enabled = $this->get_option( 'enabled' );
				$this->FC_CEP_ORIGIN = $this->get_option( 'FC_CEP_ORIGIN' );
				
				
    /*register_setting( 'freteclick', 'FC_CITY_ORIGIN');
    register_setting( 'freteclick', 'FC_CEP_ORIGIN');
    register_setting( 'freteclick', 'FC_STREET_ORIGIN');
    register_setting( 'freteclick', 'FC_NUMBER_ORIGIN');
    register_setting( 'freteclick', 'FC_COMPLEMENT_ORIGIN');
    register_setting( 'freteclick', 'FC_STATE_ORIGIN');
    register_setting( 'freteclick', 'FC_CONTRY_ORIGIN');
    register_setting( 'freteclick', 'FC_DISTRICT_ORIGIN');
    register_setting( 'freteclick', 'FC_API_KEY');
    register_setting( 'freteclick', 'FC_INFO_PROD');
    register_setting( 'freteclick', 'FC_SHOP_CART');*/
	
                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            } 
            /**
            * Define settings field for this shipping
            * @return void 
            */
            function init_form_fields() { 
                $this->instance_form_fields = array( 
					'enabled' => array(
					    'title' => __( 'Enable', 'tutsplus' ),
					    'type' => 'checkbox',
						'description' => __( 'Enable this shipping.', 'tutsplus' ),
						'default' => 'yes'
						),
					'title' => array(
						'title' => __( 'Title', 'tutsplus' ),
						'type' => 'text',
						'description' => __( 'Title to be display on site', 'tutsplus' ),
						'default' => __( 'TutsPlus Shipping', 'tutsplus' )
					),
			 
				);
            }
 
			 function admin_options() {
			 ?>
			 <h2><?php _e('You plugin name','woocommerce'); ?></h2>
			 <table class="form-table">
			 <?php $this->generate_settings_html(); ?>
			 </table> <?php
			 }
            public function calculate_shipping( $package = array() ) {
                    $rate = array(
					'id'       => $this->id,
					'label'    => "Label for the rate",
					'cost'     => '10.99',
					'calc_tax' => 'per_item'
				);
				die();

				// Register the rate
				$this->add_rate( $rate );
                
            }
        }  
	}
}

function fc_add_scripts(){
	$plugin_uri = plugin_dir_url( __FILE__ );
	
	//Adicionando estilos
	wp_enqueue_script("freteclick", $plugin_uri."views/js/Freteclick.js", array( 'jquery', 'jquery-ui-autocomplete' ), "1.0", true);

};

function fc_registerItemSettings(){
	$plugin_uri = plugin_dir_url( __FILE__ );
	
	add_menu_page('FreteClick', 'FreteClick', 'administrator', __FILE__, 'fc_DisplaySettingsPage');
	
	add_action( 'admin_init', 'fc_registerSettings' );
};

function fc_registerSettings(){
    register_setting( 'freteclick', 'FC_CITY_ORIGIN');
    register_setting( 'freteclick', 'FC_CEP_ORIGIN');
    register_setting( 'freteclick', 'FC_STREET_ORIGIN');
    register_setting( 'freteclick', 'FC_NUMBER_ORIGIN');
    register_setting( 'freteclick', 'FC_COMPLEMENT_ORIGIN');
    register_setting( 'freteclick', 'FC_STATE_ORIGIN');
    register_setting( 'freteclick', 'FC_CONTRY_ORIGIN');
    register_setting( 'freteclick', 'FC_DISTRICT_ORIGIN');
    register_setting( 'freteclick', 'FC_API_KEY');
    register_setting( 'freteclick', 'FC_INFO_PROD');
    register_setting( 'freteclick', 'FC_SHOP_CART');
};

function fc_DisplaySettingsPage(){
	$plugin_uri = plugin_dir_url( __FILE__ );
	?>
<div class="wrap" id="module_form">
	<h1>FreteClick</h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'freteclick' ); ?>
		<?php do_settings_sections( 'freteclick' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">CEP de Origem</th>
				<td><input type="text" id="cep-origin" name="FC_CEP_ORIGIN" class="fc-input-cep" value="<?php echo esc_attr( get_option('FC_CEP_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Rua</th>
				<td><input type="text" id="street-origin" name="FC_STREET_ORIGIN" class="form-control" value="<?php echo esc_attr( get_option('FC_STREET_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Número</th>
				<td><input type="text" id="number-origin" name="FC_NUMBER_ORIGIN" class="form-control" value="<?php echo esc_attr( get_option('FC_NUMBER_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Complemento</th>
				<td><input type="text" id="complement-origin" name="FC_COMPLEMENT_ORIGIN" class="form-control" value="<?php echo esc_attr( get_option('FC_COMPLEMENT_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Bairro</th>
				<td><input type="text" id="district-origin" name="FC_DISTRICT_ORIGIN" class="form-control" value="<?php echo esc_attr( get_option('FC_DISTRICT_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Cidade de Origem</th>
				<td><input type="text" id="city-origin" name="FC_CITY_ORIGIN" class="form-control" value="<?php echo esc_attr( get_option('FC_CITY_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Estado de Origem</th>
				<td><input type="text" id="state-origin" name="FC_STATE_ORIGIN" class="form-control" value="<?php echo esc_attr( get_option('FC_STATE_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Paìs de Origem</th>
				<td><input type="text" id="country-origin" name="FC_CONTRY_ORIGIN" class="form-control" value="<?php echo esc_attr( get_option('FC_CONTRY_ORIGIN') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Chave da API</th>
				<td><input type="text" name="FC_API_KEY" value="<?php echo esc_attr( get_option('FC_API_KEY') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<td>
				<?php fc_create_section_for_radio(array("name" => "Exibir box na página do produto?",
				"id" => "FC_INFO_PROD",
				"type" => "radio",
				"desc" => "Exibe uma caixa de cotação de envio na tela de descrição do produto.",
				"options" => array("sim" => "Sim", "nao" => "Não"),
				"std" => get_option('FC_INFO_PROD'))); ?>
				</td>
			</tr>
			<tr valign="top">
				<td>
				<?php fc_create_section_for_radio(array("name" => "Exibir box no rodapé do carrinho?",
				"id" => "FC_SHOP_CART",
				"type" => "radio",
				"desc" => "Exibe uma caixa de cotação de envio na tela do carrinho.",
				"options" => array("sim" => "Sim", "nao" => "Não"),
				"std" => get_option('FC_SHOP_CART'))); ?>
				</td>
			</tr>
		</table>
		
		<?php submit_button(); ?>

	</form>
</div>
	<?php
};


//Outras Funções
function fc_create_opening_tag($value) { 
	$group_class = "";
	if (isset($value['grouping'])) {
		$group_class = "suf-grouping-rhs";
	}
	echo '<div class="suf-section fix">'."\n";
	if ($group_class != "") {
		echo "<div class='$group_class fix'>\n";
	}
	if (isset($value['name'])) {
		echo "<h3>" . $value['name'] . "</h3>\n";
	}
	if (isset($value['desc']) && !(isset($value['type']) && $value['type'] == 'checkbox')) {
		echo $value['desc']."<br />";
	}
	if (isset($value['note'])) {
		echo "<span class=\"note\">".$value['note']."</span><br />";
	}
}
function fc_create_section_for_radio($value) { 
	fc_create_opening_tag($value);
	foreach ($value['options'] as $option_value => $option_text) {
		$checked = ' ';
		if (get_option($value['id']) == $option_value) {
			$checked = ' checked="checked" ';
		}
		else if (get_option($value['id']) === FALSE && $value['std'] == $option_value){
			$checked = ' checked="checked" ';
		}
		else {
			$checked = ' ';
		}
		echo '<div class="mnt-radio"><input type="radio" name="'.$value['id'].'" value="'.
			$option_value.'" '.$checked."/>".$option_text."</div>\n";
	}
	fc_create_opening_tag($value);
};

add_action("init", "fc_init");
?>