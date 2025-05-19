<?php
// This file is intentionally blank.
// It's a placeholder for admin related functionality. 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'APFGS_Admin' ) ) {

    class APFGS_Admin {

        private static $_instance = null;
        private $main_menu_slug = 'apfgs-manage-feeds';

        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function __construct() {
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_action( 'admin_post_apfgs_save_feed', array( $this, 'handle_save_feed' ) );
            add_action( 'wp_ajax_apfgs_delete_feed', array( $this, 'handle_ajax_delete_feed' ) );
            add_action( 'wp_ajax_apfgs_regenerate_feed', array( $this, 'handle_ajax_regenerate_feed' ) );
        }

        public function admin_menu() {
            add_menu_page(
                __( 'Advanced Product Feed', 'apfgs' ),
                __( 'Product Feed', 'apfgs' ),
                'manage_woocommerce', // Capability needed to access
                $this->main_menu_slug,
                array( $this, 'render_manage_feeds_page' ),
                'dashicons-rss', // Icon
                56 // Position
            );

            add_submenu_page(
                $this->main_menu_slug,
                __( 'Manage Feeds', 'apfgs' ),
                __( 'Manage Feeds', 'apfgs' ),
                'manage_woocommerce',
                $this->main_menu_slug, // Same slug as parent to make it the default page
                array( $this, 'render_manage_feeds_page' )
            );

            add_submenu_page(
                $this->main_menu_slug,
                __( 'Add New Feed', 'apfgs' ),
                __( 'Add New', 'apfgs' ),
                'manage_woocommerce',
                'apfgs-edit-feed', // Slug for the edit/add new page
                array( $this, 'render_edit_feed_page' )
            );
            
            // Potentially add a settings submenu if needed later
            // add_submenu_page(
            //     $this->main_menu_slug,
            //     __( 'Settings', 'apfgs' ),
            //     __( 'Settings', 'apfgs' ),
            //     'manage_options', // Higher capability for settings
            //     'apfgs-settings',
            //     array( $this, 'render_settings_page' )
            // );
        }

        public function enqueue_assets( $hook_suffix ) {
            // Check if we are on one of our plugin's admin pages
            $plugin_pages = array(
                'toplevel_page_' . $this->main_menu_slug, // Manage Feeds (main page)
                'product-feed_page_apfgs-edit-feed',    // Add New/Edit Feed page
                // 'product-feed_page_apfgs-settings' // If settings page is added
            );

            if ( in_array( $hook_suffix, $plugin_pages ) ) {
                wp_enqueue_style( 'apfgs-admin-styles', APFGS_PLUGIN_URL . 'admin/assets/css/apfgs-admin-styles.min.css', array(), APFGS_VERSION );
                wp_enqueue_script( 'apfgs-admin-scripts', APFGS_PLUGIN_URL . 'admin/assets/js/apfgs-admin-scripts.min.js', array( 'jquery' ), APFGS_VERSION, true );

                // Pass data to JS if needed
                wp_localize_script( 'apfgs-admin-scripts', 'apfgs_admin_params', array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'apfgs_ajax_nonce' ), // Nonce for AJAX actions
                    'manage_feeds_url' => admin_url( 'admin.php?page=apfgs-manage-feeds' ),
                    'text_file_url' => __( 'File URL:', 'apfgs' ),
                    'text_copy_url' => __( 'Copy URL', 'apfgs' ),
                    'text_last_generated' => __( 'Last Generated:', 'apfgs' ),
                ) );
            }
        }

        public function render_manage_feeds_page() {
            // This will eventually list feeds from APFGS_Feed_Manager
            // For now, just include the view file.
            require_once APFGS_PLUGIN_DIR . 'admin/views/manage-feeds-page.php';
        }

        public function render_edit_feed_page() {
            // This will handle both creating new feeds and editing existing ones.
            // The view will need to adapt based on whether a feed ID is present.
            require_once APFGS_PLUGIN_DIR . 'admin/views/edit-feed-page.php';
        }
        
        // public function render_settings_page() {
        //    // Placeholder for settings page rendering
        //    // require_once APFGS_PLUGIN_DIR . 'admin/views/settings-page.php';
        // }

        public function handle_save_feed() {
            // Verify nonce
            if ( ! isset( $_POST['apfgs_feed_nonce'] ) || ! wp_verify_nonce( $_POST['apfgs_feed_nonce'], 'apfgs_save_feed_settings' ) ) {
                wp_die( __( 'Security check failed. Please try again.', 'apfgs' ) );
            }

            // Check capabilities
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_die( __( 'You do not have sufficient permissions to perform this action.', 'apfgs' ) );
            }

            $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
            $feed_name = isset( $_POST['feed_name'] ) ? sanitize_text_field( $_POST['feed_name'] ) : '';
            $feed_settings = isset( $_POST['feed_settings'] ) && is_array( $_POST['feed_settings'] ) ? $_POST['feed_settings'] : array();

            if ( empty( $feed_name ) ) {
                // Redirect back with an error message if feed name is empty
                $redirect_url = add_query_arg( array(
                    'page' => 'apfgs-edit-feed',
                    'feed_id' => $feed_id, // Keep existing feed_id if editing
                    'apfgs_error' => 'empty_name'
                ), admin_url( 'admin.php' ) );
                wp_redirect( $redirect_url );
                exit;
            }

            $data_to_save = array(
                'feed_id'       => $feed_id,
                'feed_name'     => $feed_name,
                'feed_settings' => $feed_settings, // Sanitization will occur in APFGS_Feed_Manager::save_feed
            );

            $result = APFGS_Feed_Manager::instance()->save_feed( $data_to_save );

            if ( is_wp_error( $result ) ) {
                // Redirect back with an error message
                $redirect_url = add_query_arg( array(
                    'page' => 'apfgs-edit-feed',
                    'feed_id' => $feed_id,
                    'apfgs_error' => 'save_failed',
                    'message' => urlencode( $result->get_error_message() )
                ), admin_url( 'admin.php' ) );
            } else {
                // Success, redirect to manage feeds page or back to edit page with a success message
                $new_feed_id = $result;
                $redirect_url = add_query_arg( array(
                    'page' => 'apfgs-edit-feed', // Or 'apfgs-manage-feeds'
                    'feed_id' => $new_feed_id,
                    'apfgs_message' => $feed_id > 0 ? 'updated' : 'created'
                ), admin_url( 'admin.php' ) );
            }

            wp_redirect( $redirect_url );
            exit;
        }

        public function handle_ajax_delete_feed() {
            check_ajax_referer( 'apfgs_ajax_nonce', '_ajax_nonce' );

            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'apfgs' ) ) );
            }

            $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;

            if ( ! $feed_id ) {
                wp_send_json_error( array( 'message' => __( 'Invalid Feed ID.', 'apfgs' ) ) );
            }

            $result = APFGS_Feed_Manager::instance()->delete_feed( $feed_id );

            if ( $result ) {
                wp_send_json_success( array( 'message' => __( 'Feed deleted successfully.', 'apfgs' ) ) );
            } else {
                // delete_feed in manager should ideally return WP_Error or more specific error
                wp_send_json_error( array( 'message' => __( 'Failed to delete feed. It might have already been deleted or an error occurred.', 'apfgs' ) ) );
            }
        }

        public function handle_ajax_regenerate_feed() {
            check_ajax_referer( 'apfgs_ajax_nonce', '_ajax_nonce' );

            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions.', 'apfgs' ) ) );
            }

            $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
            if ( ! $feed_id ) {
                wp_send_json_error( array( 'message' => __( 'Invalid Feed ID for regeneration.', 'apfgs' ) ) );
            }

            // Placeholder: Call the generator
            // $generation_result = APFGS_Feed_Generator::instance()->generate_feed_by_id( $feed_id ); 
            // For now, simulate a successful generation:
            // $simulated_file_name = 'simulated-feed-' . $feed_id . '-' . time() . '.xml';
            // $simulated_timestamp = time();

            // Simulate updating meta - In reality, APFGS_Feed_Generator would do this on success
            // update_post_meta( $feed_id, '_apfgs_generated_file_name', $simulated_file_name );
            // update_post_meta( $feed_id, '_apfgs_last_generated', $simulated_timestamp );
            // Ensure the main file_name_pattern is also saved if it was missing, or update it
            // if (!get_post_meta($feed_id, '_apfgs_file_name_pattern', true)) {
            //    update_post_meta( $feed_id, '_apfgs_file_name_pattern', 'default-pattern-{date}.xml');
            // }

            // $upload_dir = wp_upload_dir();
            // $feed_file_url = trailingslashit( $upload_dir['baseurl'] ) . 'apfgs-feeds/' . $simulated_file_name;
            // $last_generated_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $simulated_timestamp );

            // wp_send_json_success( array(
            //     'message' => __( 'Feed regenerated successfully (simulated).', 'apfgs' ),
            //     'feed_url' => $feed_file_url,
            //     'file_name' => $simulated_file_name,
            //     'last_generated_formatted' => $last_generated_formatted,
            //     'last_generated_timestamp' => $simulated_timestamp
            // ) );

            // Example of actual call (once generator is ready):
            /*
            $result = APFGS_Feed_Generator::instance()->generate_feed_by_id( $feed_id );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            } elseif ( $result === false ) { // Should not happen if WP_Error is returned consistently
                wp_send_json_error( array( 'message' => __( 'Feed generation failed for an unknown reason.', 'apfgs' ) ) );
            } else {
                // $result should be an array with file_name, file_url, last_generated_timestamp etc.
                wp_send_json_success( array(
                    'message' => __( 'Feed regenerated successfully.', 'apfgs' ),
                    'feed_url' => $result['file_url'],
                    'file_name' => $result['file_name'],
                    'last_generated_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $result['last_generated_timestamp'] ),
                    'last_generated_timestamp' => $result['last_generated_timestamp']
                ) );
            }
            */
            $result = APFGS_Feed_Generator::instance()->generate_feed_by_id( $feed_id );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            } elseif ( $result && is_array($result) ) { // Check if $result is a valid array
                wp_send_json_success( array(
                    'message' => __( 'Feed regenerated successfully.', 'apfgs' ),
                    'feed_url' => $result['file_url'],
                    'file_name' => $result['file_name'],
                    'last_generated_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $result['last_generated_timestamp'] ),
                    'last_generated_timestamp' => $result['last_generated_timestamp']
                ) );
            } else {
                 wp_send_json_error( array( 'message' => __( 'Feed generation failed for an unknown reason or returned unexpected data.', 'apfgs' ) ) );
            }
        }

    }

} 