<?php
// This file is intentionally blank.
// It's a placeholder for feed generation functionality. 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'APFGS_Feed_Generator' ) ) {

    class APFGS_Feed_Generator {

        private static $_instance = null;

        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function __construct() {
            // Initialization if needed
        }

        /**
         * Generate a product feed by its CPT ID.
         *
         * @param int $feed_id The ID of the apfgs_product_feed CPT.
         * @return array|WP_Error Array with feed details on success, WP_Error on failure.
         */
        public function generate_feed_by_id( $feed_id ) {
            APFGS_Logger::log( sprintf( 'Attempting to generate feed by ID: %d', $feed_id ) );
            $feed_config_manager = APFGS_Feed_Manager::instance();
            $feed_config = $feed_config_manager->get_feed( $feed_id );

            if ( ! $feed_config || !is_array($feed_config) ) {
                APFGS_Logger::error( sprintf( 'Feed configuration not found or invalid for ID: %s when trying to generate by ID.', $feed_id ) );
                return new WP_Error('feed_config_not_found', __( 'Feed configuration not found.', 'apfgs' ) );
            }
            
            // Merge CPT title (feed_name) into feed_settings if not already there, for generator's use
            if ( !isset($feed_config['feed_settings']['feed_name']) && isset($feed_config['feed_name']) ) {
                 $feed_config['feed_settings']['feed_name'] = $feed_config['feed_name'];   
            }

            // The generate_feed method expects the full config array (which get_feed now returns, including 'feed_settings')
            $generation_result = $this->generate_feed( $feed_config ); // generate_feed returns file path or false

            if ( $generation_result && !is_wp_error($generation_result) && is_string($generation_result) ) {
                $file_path = $generation_result;
                $file_name = basename( $file_path );
                $timestamp = time();

                update_post_meta( $feed_id, '_apfgs_generated_file_name', $file_name );
                update_post_meta( $feed_id, '_apfgs_last_generated', $timestamp );
                
                // Also ensure the file_name_pattern used for this generation is stored if it wasn't (should be by save)
                if (empty(get_post_meta($feed_id, '_apfgs_file_name_pattern', true)) && isset($feed_config['feed_settings']['file_name_pattern'])) {
                    update_post_meta($feed_id, '_apfgs_file_name_pattern', sanitize_text_field($feed_config['feed_settings']['file_name_pattern']));
                }


                $upload_dir = wp_upload_dir();
                $feed_file_url = trailingslashit( $upload_dir['baseurl'] ) . 'apfgs-feeds/' . $file_name;
                
                APFGS_Logger::log( sprintf( 'Feed ID %d generated successfully. File: %s', $feed_id, $file_name ) );

                return array(
                    'file_path' => $file_path,
                    'file_name' => $file_name,
                    'file_url'  => $feed_file_url,
                    'last_generated_timestamp' => $timestamp,
                );
            } else {
                $error_message = __( 'Feed generation failed.', 'apfgs' );
                if(is_wp_error($generation_result)) {
                    $error_message = $generation_result->get_error_message();
                }
                APFGS_Logger::error( sprintf( 'Feed generation failed for ID %d. Reason: %s', $feed_id, $error_message ) );
                return new WP_Error('feed_generation_failed', $error_message );
            }
        }

        /**
         * Generate a product feed based on configuration.
         *
         * @param int|array $feed_config Either a feed ID (to be loaded by Feed_Manager) or a full feed configuration array.
         * @return bool|string Path to the generated file on success, false on failure.
         */
        public function generate_feed( $feed_config ) {
            $settings = isset($feed_config['feed_settings']) ? $feed_config['feed_settings'] : array();
            $feed_id_from_config = isset($feed_config['id']) ? $feed_config['id'] : (isset($feed_config['feed_id']) ? $feed_config['feed_id'] : 0);

            $file_format = isset( $settings['file_format'] ) ? $settings['file_format'] : 'xml';
            // Filter rules will be passed to get_products_for_feed from $settings directly
            $mapping_rules = isset( $settings['mapping_rules'] ) ? $settings['mapping_rules'] : array(); 
            $feed_name_display = isset( $settings['feed_name'] ) ? $settings['feed_name'] : (	extisset($feed_config['feed_name']) ? $feed_config['feed_name'] : 'Unknown Feed');

            APFGS_Logger::log( sprintf( 'Starting feed generation process for: %s (ID: %d), Format: %s', $feed_name_display, $feed_id_from_config, $file_format ) );

            $file_path = $this->get_feed_file_path( $settings, $feed_id_from_config );
            if (is_wp_error($file_path)) {
                return $file_path; 
            }

            // Pass $settings to get_products_for_feed, which contains product_status_filter and other potential rules
            $products = $this->get_products_for_feed( $settings, $feed_config ); 

            $xml_writer = null; // Initialize
            $handle = null; // Initialize

            // Define the order of attributes for CSV/TXT headers and rows
            // This should align with what prepare_feed_item provides.
            $ordered_attributes_for_file = array(
                'g:id', 'g:title', 'g:description', 'g:link', 'g:image_link', 
                'g:availability', 'g:price', 'g:sale_price', 
                'g:condition', 'g:brand', 'g:gtin', 'g:mpn'
                // Add more here as prepare_feed_item is expanded
            );

            if ( 'xml' === $file_format ) {
                $xml_writer = new XMLWriter();
                if ( ! $xml_writer->openURI( $file_path ) ) {
                    APFGS_Logger::error( sprintf( 'Could not open XMLWriter for file: %s', $file_path ) );
                    return new WP_Error('file_open_error', __('Could not open file for writing.', 'apfgs'));
                }
                $xml_writer->setIndent( true );
                $xml_writer->startDocument( '1.0', 'UTF-8' );
                $xml_writer->startElement( 'rss' );
                $xml_writer->writeAttribute( 'version', '2.0' );
                $xml_writer->writeAttributeNS( 'xmlns', 'g', null, 'http://base.google.com/ns/1.0' );
                $xml_writer->startElement( 'channel' );
                $xml_writer->writeElement( 'title', $feed_name_display );
                $xml_writer->writeElement( 'link', home_url() ); 
                $xml_writer->writeElement( 'description', __( 'Product feed generated by Advanced Product Feed for Google Shopping plugin.', 'apfgs' ) );
            } else {
                $handle = @fopen( $file_path, 'w' );
                if ( ! $handle ) {
                    APFGS_Logger::error( sprintf( 'Could not open file for writing: %s', $file_path ) );
                    return new WP_Error('file_open_error', __('Could not open file for writing.', 'apfgs'));
                }
                // CSV/TXT header writing would go here
            }
            
            if ( 'csv' === $file_format && $handle) {
                // Filter $ordered_attributes_for_file to only include keys that might actually exist based on a sample item or full list
                // For now, use the predefined list. Header keys should not have g: prefix for typical CSVs.
                $header_row = array_map(function($key){ return str_replace('g:', '', $key); }, $ordered_attributes_for_file);
                fputcsv($handle, $header_row);
            } elseif ( 'txt' === $file_format && $handle) {
                $header_row = array_map(function($key){ return str_replace('g:', '', $key); }, $ordered_attributes_for_file);
                fwrite($handle, implode("\t", $header_row) . "\r\n");
            }

            if ( ! empty( $products ) ) {
                APFGS_Logger::log( sprintf( 'Processing %d products for feed: %s', count($products), $feed_name_display ) );
                foreach ( $products as $product ) {
                    if ( ! $product instanceof WC_Product ) continue;
                    $feed_item_data = $this->prepare_feed_item( $product, $mapping_rules, $settings );
                    if ( $feed_item_data ) {
                        if ( 'xml' === $file_format && $xml_writer) {
                            $xml_writer->startElement('item');
                            foreach($feed_item_data as $key => $value) {
                                if ($value !== null && $value !== '') {
                                    $xml_writer->writeElement( $key, $value ); // Key already includes g:
                                }
                            }
                            $xml_writer->endElement(); // item
                        } elseif ( ($file_format === 'csv' || $file_format === 'txt') && $handle ) {
                            $row_data = array();
                            foreach($ordered_attributes_for_file as $g_key) {
                                // $key_without_prefix = str_replace('g:', '', $g_key); // Mapper returns with g:
                                $row_data[] = isset($feed_item_data[$g_key]) ? $feed_item_data[$g_key] : '';
                            }
                            if ('csv' === $file_format) {
                                fputcsv($handle, $row_data);
                            } else { // txt
                                fwrite($handle, implode("\t", $row_data) . "\r\n");
                            }
                        } 
                        // Add CSV/TXT item writing here later
                    }
                }
            } else {
                APFGS_Logger::log( sprintf( 'No products found for feed: %s based on current filters.', $feed_name_display ) );
            }

            if ( 'xml' === $file_format && $xml_writer ) {
                $xml_writer->endElement(); // channel
                $xml_writer->endElement(); // rss
                $xml_writer->endDocument();
                $xml_writer->flush();
            } else if ($handle) {
                fclose( $handle );
            }

            APFGS_Logger::log( sprintf( 'Feed generation process complete for: %s. File: %s', $feed_name_display, $file_path ) );
            return $file_path;
        }

        /**
         * Prepares a single item for the feed.
         */
        private function prepare_feed_item( $product, $mapping_rules, $feed_config_settings ) {
            $item_data = array();
            $google_attributes = [
                'g:id', 'g:title', 'g:description', 'g:link', 'g:image_link', 
                'g:availability', 'g:price', 
                // 'g:sale_price', // Add if product is on sale
                'g:condition', 'g:brand', 'g:gtin', 'g:mpn' // Added new attributes
            ];

            if ($product->is_on_sale() && $product->get_sale_price() !== '') {
                $google_attributes[] = 'g:sale_price';
            }

            foreach ( $google_attributes as $g_attr ) {
                $value = APFGS_Attribute_Mapper::instance()->get_mapped_value( $g_attr, $product, $mapping_rules, $feed_config_settings );
                if ( $value !== null && $value !== '' ) { 
                    $item_data[$g_attr] = $value; // Attribute mapper now returns keys with g: prefix
                }
            }
            return $item_data;
        }

        /**
         * Get products for the feed using filter engine.
         * This should support batching for large datasets.
         */
        private function get_products_for_feed( $filter_rules_from_settings, $feed_config_overall, $batch_number = 1, $batch_size = 100 ) {
            $query_args = array(
                'limit' => $batch_size, 
                'page'  => $batch_number,
                'paginate' => true, 
                'return'   => 'objects',
            );
            
            // $filter_rules_from_settings already contains product_status_filter etc.
            $query_args = APFGS_Filter_Engine::instance()->apply_filters( $query_args, $filter_rules_from_settings );
            
            APFGS_Logger::log(sprintf('Product query args for feed %s (batch %d): %s', isset($feed_config_overall['feed_name']) ? $feed_config_overall['feed_name'] : 'N/A', $batch_number, print_r($query_args, true)));

            $results = wc_get_products( $query_args );
            
            if ($results && $results->products) {
                 APFGS_Logger::log(sprintf('Found %d products for batch %d. Total query results: %d', count($results->products), $batch_number, $results->total));
                 return $results->products;
            } else {
                APFGS_Logger::log(sprintf('No products returned from wc_get_products for batch %d for feed %s.', $batch_number, isset($feed_config_overall['feed_name']) ? $feed_config_overall['feed_name'] : 'N/A'));
                return array();
            }
        }

        // --- Methods for specific file formats (XML, CSV, TXT) ---
        // Example for XML (using XMLWriter for efficiency):
        // private function initialize_xml_writer( $file_path ) { ... }
        // private function write_xml_item( XMLWriter $xml_writer, $item_data ) { ... }
        // private function finalize_xml_writer( XMLWriter $xml_writer ) { ... }

        // Example for CSV:
        // private function write_csv_header( $handle, $mapping_rules, $feed_config ) { ... }
        // private function write_csv_item( $handle, $item_data ) { fputcsv( $handle, $item_data ); }

        /**
         * Determines the full path for the feed file.
         */
        private function get_feed_file_path( $feed_config_settings, $feed_id ) {
            $upload_dir = wp_upload_dir();
            $feed_dir_path = trailingslashit( $upload_dir['basedir'] ) . 'apfgs-feeds';
            // Ensure the directory exists (it should from plugin activation)
            if ( ! file_exists( $feed_dir_path ) ) {
                wp_mkdir_p( $feed_dir_path );
            }

            $file_name_pattern = isset($feed_config_settings['file_name_pattern']) ? $feed_config_settings['file_name_pattern'] : 'feed-'.$feed_id.'.xml';
            $file_format = isset($feed_config_settings['file_format']) ? $feed_config_settings['file_format'] : 'xml';
            
            // Replace {date} with current timestamp
            // WordPress default timezone should be used here implicitly by date()
            $date_string = date('Y-m-d-His');
            $file_name = str_replace('{date}', $date_string, $file_name_pattern);
            
            // Ensure the file name has the correct extension based on format if not in pattern
            $current_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            if (strtolower($current_extension) !== strtolower($file_format)) {
                if (!empty($current_extension)) { // if an extension exists but is wrong
                    $file_name = substr($file_name, 0, -(strlen($current_extension) + 1));
                }
                $file_name .= '.' . strtolower($file_format);
            }

            $file_name = sanitize_file_name( $file_name );
            
            if (empty($file_name)) {
                return new WP_Error('filename_error', __('Generated file name is empty after sanitization.', 'apfgs'));
            }

            return trailingslashit( $feed_dir_path ) . $file_name;
            // return trailingslashit( $feed_dir_path ) . 'temp_feed.xml'; // Placeholder
        }
    }
} 