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
		if (is_admin()){
			fc_add_scripts();
			add_action( 'admin_menu', 'fc_registerItemSettings' );
			add_action( 'admin_init', 'fc_registerSettings' );
		}
		
		if (is_product()){
			fc_add_scripts();
			add_action("woocommerce_single_product_summary", "fc_DisplayRightColumnProduct");
		}
		else if (is_cart()){
			fc_add_scripts();
			add_action("woocommerce_after_cart_table", "fc_DisplayShoppingCartFooter");
		}
		else if (is_checkout()){
			fc_add_scripts();
		}
	}
};

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
    register_setting( 'freteclick', 'FC_CITY_ORIGIN_NAME');
    register_setting( 'freteclick', 'FC_CEP_ORIGIN');
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
				<th scope="row">ID da Cidade de Origem</th>
				<td><input type="text" id="FC_CITY_ORIGIN" name="FC_CITY_ORIGIN" value="<?php echo esc_attr( get_option('FC_CITY_ORIGIN') ); ?>" readonly/></td>
			</tr>
			<tr valign="top">
				<th scope="row">Cidade de Origem</th>
				<td><input type="text" id="city-origin" name="FC_CITY_ORIGIN_NAME" class="form-control ui-autocomplete-input" data-autocomplete-ajax-url="<?php echo $plugin_uri; ?>controllers/front/cityorigin.php" data-autocomplete-hidden-result="#FC_CITY_ORIGIN" value="<?php echo esc_attr( get_option('FC_CITY_ORIGIN_NAME') ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">CEP de Origem</th>
				<td><input type="text" name="FC_CEP_ORIGIN" value="<?php echo esc_attr( get_option('FC_CEP_ORIGIN') ); ?>" /></td>
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

function fc_DisplayRightColumnProduct(){
	get_template_part("views/templates/hook/simularfrete");
};
function fc_DisplayShoppingCartFooter(){
	get_template_part("views/templates/hook/simularfrete_cart");	
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