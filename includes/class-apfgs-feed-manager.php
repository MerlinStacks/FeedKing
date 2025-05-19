<?php
// This file is intentionally blank.
// It's a placeholder for feed management functionality. 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'APFGS_Feed_Manager' ) ) {

    class APFGS_Feed_Manager {

        private static $_instance = null;
        public $post_type = 'apfgs_product_feed';

        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function __construct() {
            add_action( 'init', array( $this, 'register_feed_cpt' ), 10 ); // Priority 10 to ensure it registers
            // Potentially add hooks for managing meta boxes for the CPT if not handled by a UI class
        }

        public function register_feed_cpt() {
            $labels = array(
                'name'                  => _x( 'Product Feeds', 'Post type general name', 'apfgs' ),
                'singular_name'         => _x( 'Product Feed', 'Post type singular name', 'apfgs' ),
                'menu_name'             => _x( 'Product Feeds', 'Admin Menu text', 'apfgs' ),
                'name_admin_bar'        => _x( 'Product Feed', 'Add New on Toolbar', 'apfgs' ),
                'add_new'               => __( 'Add New', 'apfgs' ),
                'add_new_item'          => __( 'Add New Product Feed', 'apfgs' ),
                'new_item'              => __( 'New Product Feed', 'apfgs' ),
                'edit_item'             => __( 'Edit Product Feed', 'apfgs' ),
                'view_item'             => __( 'View Product Feed', 'apfgs' ),
                'all_items'             => __( 'All Product Feeds', 'apfgs' ),
                'search_items'          => __( 'Search Product Feeds', 'apfgs' ),
                'parent_item_colon'     => __( 'Parent Product Feeds:', 'apfgs' ),
                'not_found'             => __( 'No product feeds found.', 'apfgs' ),
                'not_found_in_trash'    => __( 'No product feeds found in Trash.', 'apfgs' ),
                'featured_image'        => _x( 'Product Feed Cover Image', 'Overrides the "Featured Image" phrase for this post type. Added in 4.3', 'apfgs' ),
                'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase for this post type. Added in 4.3', 'apfgs' ),
                'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase for this post type. Added in 4.3', 'apfgs' ),
                'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase for this post type. Added in 4.3', 'apfgs' ),
                'archives'              => _x( 'Product Feed archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'apfgs' ),
                'insert_into_item'      => _x( 'Insert into product feed', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post). Added in 4.4', 'apfgs' ),
                'uploaded_to_this_item' => _x( 'Uploaded to this product feed', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase (used when viewing media attached to a post). Added in 4.4', 'apfgs' ),
                'filter_items_list'     => _x( 'Filter product feeds list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list"/"Filter pages list". Added in 4.4', 'apfgs' ),
                'items_list_navigation' => _x( 'Product Feeds list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation"/"Pages list navigation". Added in 4.4', 'apfgs' ),
                'items_list'            => _x( 'Product Feeds list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list"/"Pages list". Added in 4.4', 'apfgs' ),
            );

            $args = array(
                'labels'             => $labels,
                'public'             => false, // Not publicly queryable, managed within admin
                'publicly_queryable' => false,
                'show_ui'            => false, // Will be managed by our own admin pages, not default WP UI for CPTs
                'show_in_menu'       => false, // Handled by APFGS_Admin custom menu
                'query_var'          => false,
                'rewrite'            => false,
                'capability_type'    => 'post',
                'has_archive'        => false,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => array( 'title' ), // We mainly need a title, other data is meta.
                'show_in_rest'       => true, // Useful for potential future headless or block editor integrations
            );

            register_post_type( $this->post_type, $args );
        }

        public function get_feed( $feed_id ) {
            // Placeholder: Get feed configuration (post meta) by ID
            // $feed_post = get_post( $feed_id );
            // if ( ! $feed_post || $feed_post->post_type !== $this->post_type ) return null;
            // $feed_data = get_post_meta( $feed_id ); // Get all meta
            // return $feed_data; // Or format it as needed
            // return null;
            if ( ! $feed_id ) {
                return null;
            }

            $feed_post = get_post( intval( $feed_id ) );

            if ( ! $feed_post || $feed_post->post_type !== $this->post_type ) {
                APFGS_Logger::error( sprintf( 'Attempted to load invalid feed ID: %s or wrong post type.', $feed_id ) );
                return null;
            }

            $feed_data = array(
                'id'          => $feed_post->ID,
                'feed_id'     => $feed_post->ID, // for consistency with save_feed
                'feed_name'   => $feed_post->post_title, // Use post_title as the primary name
                'post_status' => $feed_post->post_status,
                'feed_settings' => array(),
                 // Add other CPT fields if necessary
            );

            // Load all post meta starting with _apfgs_
            $all_meta = get_post_meta( $feed_post->ID );
            $settings_loaded = array();
            if ( $all_meta ) {
                foreach ( $all_meta as $meta_key => $meta_value ) {
                    if ( strpos( $meta_key, '_apfgs_' ) === 0 ) {
                        // Remove the _apfgs_ prefix to get the original setting key
                        $setting_key = substr( $meta_key, strlen( '_apfgs_' ) );
                        // get_post_meta returns an array of values, we usually want the first one (single value)
                        $settings_loaded[$setting_key] = maybe_unserialize( $meta_value[0] );
                    }
                }
            }
            $feed_data['feed_settings'] = $settings_loaded;
            
            // Ensure essential settings have defaults if not found in meta, though save_feed should handle this.
            // For example:
            // $feed_data['feed_settings']['file_format'] = isset($settings_loaded['file_format']) ? $settings_loaded['file_format'] : 'xml';
            // $feed_data['feed_settings']['merchant_type'] = isset($settings_loaded['merchant_type']) ? $settings_loaded['merchant_type'] : 'google_shopping';


            return $feed_data;
        }

        public function get_all_feeds( $args = array() ) {
            // Placeholder: Get all feed configurations
            // $args = array(
            // 'post_type' => $this->post_type,
            // 'posts_per_page' => -1,
            // 'post_status' => 'publish', // Or any status we use for active feeds
            // );
            // $feeds = get_posts( $args );
            // return $feeds; // Array of WP_Post objects, meta needs to be fetched for each
            // return array();

            $default_args = array(
                'post_type'      => $this->post_type,
                'posts_per_page' => -1, // Get all feeds
                'post_status'    => 'publish', // Consider if other statuses should be included
                'orderby'        => 'title',
                'order'          => 'ASC',
            );

            $query_args = wp_parse_args( $args, $default_args );
            $feed_posts = get_posts( $query_args );

            $feeds_data = array();
            if ( ! empty( $feed_posts ) ) {
                foreach ( $feed_posts as $feed_post ) {
                    // Optionally, we can use the get_feed() method to enrich each feed object
                    // with its settings, but for a list table, we might only need a few key metas.
                    $feed_detail = array(
                        'ID'         => $feed_post->ID,
                        'feed_name'  => $feed_post->post_title, // Use post_title
                        'post_status'=> $feed_post->post_status,
                        // For performance, only load essential meta for the list view here
                        // Full settings can be loaded when editing a specific feed.
                        'file_name' => get_post_meta( $feed_post->ID, '_apfgs_file_name_pattern', true ), // Assuming you save file name pattern like this
                        'file_format' => get_post_meta( $feed_post->ID, '_apfgs_file_format', true ),
                        'last_generated' => get_post_meta( $feed_post->ID, '_apfgs_last_generated', true ),
                        'feed_url' => 'N/A' // This will be constructed dynamically
                    );
                    
                    // Construct the feed URL
                    $upload_dir = wp_upload_dir();
                    $feed_dir_baseurl = trailingslashit( $upload_dir['baseurl'] ) . 'apfgs-feeds';
                    $file_name_meta = get_post_meta( $feed_post->ID, '_apfgs_generated_file_name', true ); // Actual generated file name

                    if ( $file_name_meta ) {
                        $feed_detail['feed_url'] = trailingslashit( $feed_dir_baseurl ) . $file_name_meta;
                    } else {
                        // Fallback or indicate not generated yet
                        $pattern = $feed_detail['file_name'] ? $feed_detail['file_name'] : 'feed-'.$feed_post->ID.'.'.($feed_detail['file_format'] ? $feed_detail['file_format'] : 'xml');
                        $feed_detail['feed_url_placeholder'] = trailingslashit( $feed_dir_baseurl ) . str_replace('{date}', ' YYYY-MM-DD-HHMMSS ', $pattern);
                    }

                    $feeds_data[] = (object) $feed_detail; // Cast to object for easier property access in view
                }
            }
            return $feeds_data;
        }

        public function save_feed( $feed_data ) {
            // Placeholder: Create or update a feed configuration
            // $feed_id = isset( $feed_data['id'] ) ? intval( $feed_data['id'] ) : 0;
            // $post_data = array(
            //     'post_title' => sanitize_text_field( $feed_data['name'] ),
            //     'post_type' => $this->post_type,
            //     'post_status' => 'publish', // Or a custom status
            // );
            // if ( $feed_id > 0 ) $post_data['ID'] = $feed_id;
            // $new_feed_id = wp_insert_post( $post_data );
            // if ( is_wp_error( $new_feed_id ) ) return $new_feed_id;
            // Update post meta with other settings from $feed_data
            // update_post_meta( $new_feed_id, '_setting_x', $feed_data['setting_x'] );
            // return $new_feed_id;
            // return null;

            $feed_id = isset( $feed_data['feed_id'] ) ? intval( $feed_data['feed_id'] ) : 0;
            $feed_name = isset( $feed_data['feed_name'] ) ? sanitize_text_field( $feed_data['feed_name'] ) : 'Untitled Feed';

            $post_arr = array(
                'post_title'   => $feed_name,
                'post_type'    => $this->post_type,
                'post_status'  => 'publish', // Or handle other statuses like 'draft'
            );

            if ( $feed_id > 0 ) {
                $post_arr['ID'] = $feed_id;
            }

            $result = wp_insert_post( $post_arr, true ); // true to return WP_Error on failure

            if ( is_wp_error( $result ) ) {
                APFGS_Logger::error( 'Error saving feed CPT: ' . $result->get_error_message() );
                return $result;
            }

            $new_feed_id = $result;

            // Save other settings as post meta
            if ( isset( $feed_data['feed_settings'] ) && is_array( $feed_data['feed_settings'] ) ) {
                foreach ( $feed_data['feed_settings'] as $key => $value ) {
                    // Sanitize each setting appropriately before saving
                    $sanitized_value = null;
                    switch ($key) {
                        case 'file_format':
                        case 'merchant_type':
                        case 'file_name_pattern': // Added from project.md scope
                            $sanitized_value = sanitize_text_field( $value );
                            break;
                        case 'refresh_interval': // Added from project.md scope
                            $sanitized_value = sanitize_text_field( $value ); // Or intval if it's a numeric value like seconds
                            break;
                        case 'product_status_filter': // Added from project.md scope
                             if (is_array($value)) {
                                $sanitized_value = array_map('sanitize_text_field', $value);
                            } else {
                                $sanitized_value = sanitize_text_field($value);
                            }
                            break;
                        case 'generated_file_name': // Actual name of the generated file
                            $sanitized_value = sanitize_file_name( $value );
                            break;
                        case 'last_generated': // Timestamp of last generation
                            $sanitized_value = intval( $value );
                            break;
                        // Add more cases for other settings like filter_rules, attribute_mapping, category_mapping
                        // For arrays/objects, you might need more complex sanitization
                        default:
                            // For unknown keys, or complex structures like mapping/filter rules, 
                            // you might apply a general sanitization or skip, depending on policy.
                            // For now, we assume they are handled/sanitized before this method or are simple enough.
                            if (is_string($value)) {
                                $sanitized_value = sanitize_text_field($value);
                            } else if (is_array($value)) {
                                // This is a shallow sanitization, deeper structures need recursive sanitization
                                $sanitized_value = array_map('sanitize_text_field', $value); 
                            } else {
                                $sanitized_value = $value; // Or skip: continue 2;
                            }
                            break;
                    }
                    if ($sanitized_value !== null) {
                         update_post_meta( $new_feed_id, '_apfgs_' . $key, $sanitized_value );
                    }
                }
            }
            
            // Store the original feed name separately if it might differ from post_title due to sanitization/uniqueness
            update_post_meta( $new_feed_id, '_apfgs_feed_name_original', $feed_name );

            return $new_feed_id;
        }

        public function delete_feed( $feed_id ) {
            // Placeholder: Delete a feed configuration
            // return wp_delete_post( $feed_id, true ); // true to force delete, false to trash
            // return false;
            if ( ! $feed_id ) {
                return false;
            }

            $feed_id = intval( $feed_id );
            $post = get_post( $feed_id );

            // Check if the post exists and is of the correct CPT
            if ( ! $post || $post->post_type !== $this->post_type ) {
                APFGS_Logger::error( sprintf( 'Attempted to delete invalid or non-feed post ID: %s', $feed_id ) );
                return false;
            }

            // Also delete the associated feed file from wp-content/uploads/apfgs-feeds/
            // $generated_file_name = get_post_meta( $feed_id, '_apfgs_generated_file_name', true );
            // if ( $generated_file_name ) {
            //     $upload_dir = wp_upload_dir();
            //     $feed_file_path = trailingslashit( $upload_dir['basedir'] ) . 'apfgs-feeds/' . $generated_file_name;
            //     if ( file_exists( $feed_file_path ) ) {
            //         wp_delete_file( $feed_file_path );
            //     }
            // }
            // Note: wp_delete_attachment($attachment_id, true) also deletes the file, but these are not attachments.

            $result = wp_delete_post( $feed_id, true ); // true to bypass trash and force delete

            if ( $result === false || is_wp_error( $result ) ) {
                APFGS_Logger::error( sprintf( 'Failed to delete feed CPT ID: %s', $feed_id ) );
                return false;
            }

            // Consider deleting related meta or scheduler tasks if any were specifically tied and not cleaned up by CPT deletion
            // For instance, if Action Scheduler tasks were stored with post_id references not directly part of the CPT.

            return true; // Successfully deleted
        }
    }
} 