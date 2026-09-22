<?php
/**
 * Plugin Name:       	Frete Click
 * Plugin URI:        	https://br.wordpress.org/plugins/freteclick/
 * Description:       	Cotação de fretes com múltiplas transportadoras, prazos e preços em tempo real no checkout do WooCommerce. Simulador de frete na página do produto e contratação automática.
 * Version:           	1.1.40
 * Author:            	Frete Click
 * Requires at least: 	3.5
 * Author URI:        	https://www.freteclick.com.br/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: freteclick-shipping-gateway
 * Domain Path: languages/
 */

define( 'WOO_FRETECLICK_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if(! class_exists("WC_FreteClick_Main") ) : 

	/**
	 * Frete Click Main Class
	 */
	class WC_FreteClick_Main {

        /**
         * Instance of this class.
         *
         * @var object
         */
        protected static $instance = null;

        /**
         * Initialize the plugin
         */
		private function __construct() {

			// Checks with WooCommerce is installed.
            if ( class_exists( 'WC_Integration' ) ) {
				include_once WOO_FRETECLICK_PATH . 'vendor/autoload.php';
				include_once WOO_FRETECLICK_PATH . 'includes/class-wc-freteclick-shipping-simulator.php';
				include_once WOO_FRETECLICK_PATH . 'includes/class-wc-freteclick.php';

				add_filter( 'woocommerce_shipping_methods', array( $this, 'wcfreteclick_add_method' ) );

				add_action('woocommerce_cart_updated', array('WC_FreteClick_Main', 'fc_clear_shipping_cache'));
				add_action('woocommerce_checkout_update_order_review', array('WC_FreteClick_Main', 'fc_clear_shipping_cache'));
				add_action('woocommerce_after_calculate_totals', array('WC_FreteClick_Main', 'fc_clear_shipping_cache'));
			}else{
				// add_action( 'admin_notices', array( $this, 'wcfreteclick_woocommerce_fallback_notice' ) );
			}

		}

		/**
		 * Clear Frete Click shipping cache when cart/checkout changes.
		 */
		public static function fc_clear_shipping_cache() {
			if (WC()->session) {
				$session_data = WC()->session->get_data();
				if ($session_data) {
					foreach ($session_data as $key => $value) {
						if (strpos($key, 'freteclick_rates_') === 0) {
							WC()->session->__unset($key);
						}
					}
				}
			}
		}

		/**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance() {
            // If the single instance hasn't been set, set it now.
            if ( null === self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;
        }

		/**
         * Get main file.
         *
         * @return string
         */
        public static function get_main_file() {
            return __FILE__;
        }

        /**
         * Get plugin path.
         *
         * @return string
         */
        public static function get_plugin_path() {
            return plugin_dir_path( __FILE__ );
        }

		/**
         * Add the Frete Click to shipping methods.
         *
         * @param array $methods
         *
         * @return array
         */
        function wcfreteclick_add_method( $methods ) {
            $methods['freteclick'] = 'WC_FreteClick';

            return $methods;
        }

	}

	add_action( 'plugins_loaded', array( 'WC_FreteClick_Main', 'get_instance' ) );

endif;