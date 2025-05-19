<?php
/**
 * Plugin Name:         Advanced Product Feed for Google Shopping
 * Plugin URI:          https://yourwebsite.com/plugin
 * Description:         Generates highly customizable, fast-loading product feeds for Google Shopping from WooCommerce. All assets are local. Supports all product types and status filtering.
 * Version:             0.3.0
 * Author:              Your Name
 * Author URI:          https://yourwebsite.com
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         apfgs
 * Domain Path:         /languages
 * Requires at least:   5.8
 * Requires PHP:        7.4
 * WC requires at least:6.0
 * WC tested up to:     8.x
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define constants
define( 'APFGS_PLUGIN_FILE', __FILE__ );
define( 'APFGS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APFGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APFGS_VERSION', '0.3.0' );

if ( ! class_exists( 'Advanced_Product_Feed_Google_Shopping' ) ) {

    final class Advanced_Product_Feed_Google_Shopping {

        private static $_instance = null;

        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function __construct() {
            $this->includes();
            $this->init_hooks();

            register_activation_hook( APFGS_PLUGIN_FILE, array( $this, 'activate' ) );
            register_deactivation_hook( APFGS_PLUGIN_FILE, array( $this, 'deactivate' ) );
        }

        private function includes() {
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-admin.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-feed-manager.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-feed-generator.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-attribute-mapper.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-filter-engine.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-scheduler.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-settings.php';
            require_once APFGS_PLUGIN_DIR . 'includes/utils/class-apfgs-logger.php';
        }

        private function init_hooks() {
            add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

            if ( is_admin() ) {
                APFGS_Admin::instance();
            }

            APFGS_Scheduler::instance();
        }

        public function load_plugin_textdomain() {
            load_plugin_textdomain( 'apfgs', false, dirname( plugin_basename( APFGS_PLUGIN_FILE ) ) . '/languages/' );

            APFGS_Scheduler::instance()->schedule_events();
        }

        public function activate() {
            $upload_dir = wp_upload_dir();
            $feed_dir_path = trailingslashit( $upload_dir['basedir'] ) . 'apfgs-feeds';
            if ( ! file_exists( $feed_dir_path ) ) {
                wp_mkdir_p( $feed_dir_path );
            }
            if ( ! file_exists( $feed_dir_path . '/index.html' ) ) {
                 // @codingStandardsIgnoreStart
                @file_put_contents( $feed_dir_path . '/index.html', '' );
                 // @codingStandardsIgnoreEnd
            }
            if ( ! file_exists( $feed_dir_path . '/.htaccess' ) ) {
                 $htaccess_content = "Options -Indexes\ndeny from all";
                  // @codingStandardsIgnoreStart
                 @file_put_contents( $feed_dir_path . '/.htaccess', $htaccess_content );
                 // @codingStandardsIgnoreEnd
            }

            APFGS_Scheduler::instance()->schedule_events();
        }

        public function deactivate() {
            APFGS_Scheduler::instance()->clear_scheduled_events();
        }

        public function plugin_url() {
            return untrailingslashit( plugins_url( '/', APFGS_PLUGIN_FILE ) );
        }

        public function plugin_path() {
            return untrailingslashit( plugin_dir_path( APFGS_PLUGIN_FILE ) );
        }
    }
}

function apfgs_run_plugin() {
    return Advanced_Product_Feed_Google_Shopping::instance();
}
apfgs_run_plugin(); 