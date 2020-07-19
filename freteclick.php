<?php
/*
Plugin Name:  FreteClick
Plugin URI:     https://freteclick.com.br/
Description:     Cálculo do frete com o serviço da web Frete Click
Version:           1.0.3
Author:            Frete Click
Author URI:    https://www.freteclick.com.br
License:           Todos os Direitos Reservados
*/

require_once("includes/variables.php");
require_once("includes/FreteClick.class.php");


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	FreteClick::init();
} else {
	add_action( 'admin_notices', array('FreteClick','fc_wc_missing_notice') );
}
