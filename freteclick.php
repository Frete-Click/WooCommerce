<?php
/**
 * Plugin Name:       	Frete Click
 * Plugin URI:        	https://br.wordpress.org/plugins/freteclick/
 * Description:       	Plugin para cotação de fretes utilizando a API da Frete Click.
 * Version:           	1.1.32
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
			}else{
				// add_action( 'admin_notices', array( $this, 'wcfreteclick_woocommerce_fallback_notice' ) );
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