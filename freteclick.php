<?php
/**
 * Plugin Name:       	Frete Click
 * Plugin URI:        	https://br.wordpress.org/plugins/freteclick/
 * Description:       	Plugin para cotação de fretes utilizando a API da Frete Click.
 * Version:           	1.1.18
 * Author:            	Frete Click
 * Requires at least: 	4.7
 * WC tested up to:   	5.9
 * Requires PHP: 		5.6
 * Author URI:        	https://www.freteclick.com.br/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

define( 'WOO_FRETECLICK_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$pluginDir = plugin_dir_path(__FILE__);

if(! class_exists("WC_FreteClick_Main") )
{
	class WC_FreteClick_Main{

		protected static $instance = null;

		private function __construct() {

			if (class_exists("WC_Integration")) {

				require_once WOO_FRETECLICK_PATH . 'vendor/autoload.php';

				$pluginDir = plugin_dir_path(__FILE__);
				$fc_errors = array();

				require_once WOO_FRETECLICK_PATH . 'includes/class-freteclick-shipping.php';
				include_once WOO_FRETECLICK_PATH . 'includes/class-wc-freteclick.php';

				add_filter( 'woocommerce_shipping_methods', array($this,'add_fc_shipping_methods'));

				FreteClick::init();
			}else{
				add_action( 'admin_notices', array( $this, 'wcfreteclick_woocommerce_fallback_notice' ) );
			}

		}

		public static function add_fc_shipping_methods( $methods ) {
			$methods['freteclick'] = 'WC_FreteClick';
			return $methods;
		}

		public static function get_instance() {

            if ( null === self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;
        }
	}

	add_action( 'plugins_loaded', array( 'WC_FreteClick_Main', 'get_instance' ) );
}
