<?php
// This file is intentionally blank.
// It's a placeholder for attribute mapping functionality. 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'APFGS_Attribute_Mapper' ) ) {

    class APFGS_Attribute_Mapper {

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
         * Get the mapped value for a specific merchant attribute.
         *
         * @param string $merchant_attribute The target merchant attribute (e.g., 'g:id', 'g:title').
         * @param WC_Product $product The WooCommerce product object.
         * @param array $mapping_rules The attribute mapping rules for the current feed.
         * @param array $feed_config The overall feed configuration.
         * @return mixed The mapped value, or null if not found/applicable.
         */
        public function get_mapped_value( $merchant_attribute, $product, $mapping_rules, $feed_config ) {
            // Placeholder for attribute mapping logic
            // This will be a complex method with various cases

            // Example: Get product ID for 'g:id'
            // if ( 'g:id' === $merchant_attribute ) {
            //     return $product->get_id();
            // }

            // Example: Get product title for 'g:title'
            // if ( 'g:title' === $merchant_attribute ) {
            //     return $product->get_name();
            // }

            // Logic for handling custom mapped attributes based on $mapping_rules
            // Logic for dynamic attributes and conditional logic

            // How to get a simple product attribute:
            // $value = $product->get_attribute('color');

            // How to get a custom field (post meta):
            // $value = $product->get_meta('_custom_field_key', true);

            // For variations, you might need to check the parent or the variation itself
            // if ( $product->is_type('variation') ) {
            //     $parent_product = wc_get_product( $product->get_parent_id() );
            // }

            // return null; // Default if no mapping found or rule applies
            
            // Basic direct mapping for core attributes (no $mapping_rules used yet)
            // This is a simplified initial implementation.
            // In a full version, this would check $mapping_rules first.

            if (!$product instanceof WC_Product) {
                return null;
            }

            // Check mapping rules first
            if (isset($mapping_rules[$merchant_attribute])) {
                $rule = $mapping_rules[$merchant_attribute];
                $source = isset($rule['source']) ? $rule['source'] : '';
                $static_value = isset($rule['static_value']) ? $rule['static_value'] : '';

                if ($source === 'static_value') {
                    return $static_value;
                }

                if (!empty($source)) {
                    // Handle WooCommerce product attributes (e.g., attribute:wc:pa_color)
                    if (strpos($source, 'attribute:wc:pa_') === 0) {
                        $attribute_slug = str_replace('attribute:wc:pa_', '', $source);
                        return $product->get_attribute($attribute_slug);
                    }
                    // Handle meta fields (e.g., meta:_custom_field)
                    if (strpos($source, 'meta:') === 0) {
                        $meta_key = str_replace('meta:', '', $source);
                        return $product->get_meta($meta_key, true);
                    }

                    // Handle direct product properties/methods based on the key from get_wc_attributes()
                    // This switch needs to be more robust and map keys from get_wc_attributes to actual WC_Product methods/properties
                    switch ($source) {
                        case 'id': return $product->get_id();
                        case 'title': case 'name': return $product->get_name();
                        case 'description': return $product->get_description();
                        case 'short_description': return $product->get_short_description();
                        case 'sku': return $product->get_sku();
                        case 'price': 
                            $active_price = $product->get_price(); 
                            return $active_price ? trim(sprintf('%s %s', $active_price, get_woocommerce_currency())) : null;
                        case 'regular_price': 
                            $reg_price = $product->get_regular_price();
                            return $reg_price ? trim(sprintf('%s %s', $reg_price, get_woocommerce_currency())) : null;
                        case 'sale_price': 
                            $sl_price = $product->get_sale_price();
                            return $sl_price ? trim(sprintf('%s %s', $sl_price, get_woocommerce_currency())) : null;
                        case 'product_url': case 'permalink': return $product->get_permalink();
                        case 'image_url': 
                            $img_id = $product->get_image_id(); 
                            return $img_id ? wp_get_attachment_url($img_id) : null;
                        case 'stock_status': return $product->get_stock_status();
                        case 'stock_quantity': return $product->get_stock_quantity();
                        case 'average_rating': return $product->get_average_rating();
                        case 'review_count': return $product->get_review_count();
                        case 'weight': return $product->get_weight();
                        case 'length': return $product->get_length();
                        case 'width': return $product->get_width();
                        case 'height': return $product->get_height();
                        case 'category_names': 
                            $term_list = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
                            return is_array($term_list) ? implode(', ', $term_list) : null;
                        case 'tag_names':
                            $term_list = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names'));
                            return is_array($term_list) ? implode(', ', $term_list) : null;
                        // If the source matches a merchant_attribute key directly (e.g. mapping 'g:title' to source 'title')
                        // then it implies default handling for that merchant attribute is desired if no specific rule is set for that source.
                        // This is covered by the fallback switch below.
                    }
                }
            }

            // Fallback to default direct mapping if no rule matched or rule source was empty ('-- Default --')
            switch ($merchant_attribute) {
                case 'g:id':
                    return $product->get_id(); // Or $product->get_sku() if preferred and unique
                case 'g:title':
                    return $product->get_name();
                case 'g:description':
                    return $product->get_description() ? $product->get_description() : $product->get_short_description();
                case 'g:link':
                    return $product->get_permalink();
                case 'g:image_link':
                    $image_id = $product->get_image_id();
                    return $image_id ? wp_get_attachment_url($image_id) : null;
                case 'g:availability':
                    return $product->is_in_stock() ? 'in stock' : 'out of stock';
                case 'g:price':
                    // WC_Product::get_price() returns the active price (sale or regular)
                    $price = $product->get_price(); 
                    // Google requires price with currency
                    return $price ? trim(sprintf('%s %s', $price, get_woocommerce_currency())) : null;
                case 'g:sale_price':
                     $sale_price = $product->get_sale_price();
                     return $sale_price ? trim(sprintf('%s %s', $sale_price, get_woocommerce_currency())) : null;
                case 'g:brand':
                    // Placeholder - this often comes from a custom attribute or taxonomy
                    // For example: return $product->get_attribute('pa_brand');
                    // Or a custom field: $product->get_meta('brand_meta_key', true);
                    return null; 
                case 'g:gtin':
                    // Placeholder - GTIN (UPC, EAN, ISBN) usually from SKU or custom field
                    // return $product->get_sku(); // If SKU is GTIN
                    // return $product->get_meta('gtin_meta_key', true);
                    return null;
                case 'g:mpn':
                     // Placeholder - MPN usually from SKU or custom field
                    // return $product->get_sku(); // If SKU is MPN and not GTIN
                    // return $product->get_meta('mpn_meta_key', true);
                    return null;
                case 'g:condition':
                    // Most products are 'new'. This could be mapped from a custom field if needed.
                    return 'new';
                 // Add more cases for other Google attributes as needed
            }
            
            // Fallback to checking mapping_rules if we expand this (not done here yet)

            return null; // Default if no direct mapping or rule applies
        }

        /**
         * Retrieves default WooCommerce product attributes for mapping UI.
         *
         * @return array
         */
        public function get_wc_attributes() {
            $attributes = array(
                'id' => __( 'Product ID', 'apfgs' ),
                'title' => __( 'Product Title', 'apfgs' ),
                'name' => __( 'Product Name (same as Title)', 'apfgs' ),
                'description' => __( 'Product Description', 'apfgs' ),
                'short_description' => __( 'Product Short Description', 'apfgs' ),
                'sku' => __( 'SKU', 'apfgs' ),
                'price' => __( 'Price (active)', 'apfgs' ),
                'regular_price' => __( 'Regular Price', 'apfgs' ),
                'sale_price' => __( 'Sale Price', 'apfgs' ),
                'product_url' => __( 'Product URL', 'apfgs' ),
                'permalink' => __( 'Permalink (same as Product URL)', 'apfgs' ),
                'image_url' => __( 'Main Image URL', 'apfgs' ),
                'stock_status' => __( 'Stock Status (e.g., instock, outofstock)', 'apfgs' ),
                'stock_quantity' => __( 'Stock Quantity', 'apfgs' ),
                'average_rating' => __( 'Average Rating', 'apfgs' ),
                'review_count' => __( 'Review Count', 'apfgs' ),
                'weight' => __( 'Weight', 'apfgs' ),
                'length' => __( 'Length', 'apfgs' ),
                'width' => __( 'Width', 'apfgs' ),
                'height' => __( 'Height', 'apfgs' ),
                'category_names' => __( 'Category Names (comma separated)', 'apfgs' ),
                'tag_names' => __( 'Tag Names (comma separated)', 'apfgs' ),
                // Add more standard fields
            );

            // Add WooCommerce product attributes (e.g., color, size)
            if ( class_exists('WooCommerce') ) {
                $wc_product_attributes = wc_get_attribute_taxonomies();
                if ( $wc_product_attributes ) {
                    foreach ( $wc_product_attributes as $tax_attribute ) {
                        $attributes['attribute:wc:pa_' . $tax_attribute->attribute_name] = sprintf(__( 'Product Attribute: %s', 'apfgs' ), $tax_attribute->attribute_label);
                    }
                }
            }

            // Add common post meta keys (this could be extensive, so keep it curated or add a searcher later)
            $common_meta = array(
                'meta:_edit_last' => __( 'Meta: Last Editor ID', 'apfgs' ), // Example
                'meta:total_sales' => __( 'Meta: Total Sales', 'apfgs' ), 
                // Add more common/useful meta keys
            );
            $attributes = array_merge($attributes, $common_meta);

            // Option for static value
            $attributes['static_value'] = __( '-- Enter Static Value --', 'apfgs' );
            // Option for dynamic/conditional logic (to be implemented later)
            // $attributes['conditional_logic'] = __( '-- Use Conditional Logic --', 'apfgs' );

            return apply_filters( 'apfgs_wc_attributes_for_mapping', $attributes );
        }

        /**
         * Sanitizes attribute mapping rules.
         *
         * @param array $rules Raw mapping rules.
         * @return array Sanitized rules.
         */
        public function sanitize_mapping_rules( array $rules ) {
            $sanitized_rules = array();
            $valid_wc_sources = array_keys( $this->get_wc_attributes() );
            // Allow an empty string as a valid source, representing default behavior
            $valid_wc_sources[] = '';

            if ( ! is_array( $rules ) ) {
                return $sanitized_rules; // Return empty if input is not an array
            }

            foreach ( $rules as $merchant_key_input => $rule_details_input ) {
                // Sanitize the merchant attribute key (e.g., 'g:id')
                $clean_merchant_key = sanitize_text_field( wp_unslash( $merchant_key_input ) );

                if ( ! is_array( $rule_details_input ) ) {
                    // If rule details are not an array, set defaults for this key
                    $sanitized_rules[$clean_merchant_key] = array(
                        'source'       => '',
                        'static_value' => '',
                    );
                    continue;
                }

                $sanitized_rule_detail = array(
                    'source'       => '', // Default to empty string (plugin default/direct mapping)
                    'static_value' => '', // Default to empty string
                );

                // Sanitize and validate the 'source'
                if ( isset( $rule_details_input['source'] ) ) {
                    $source_input = sanitize_text_field( wp_unslash( $rule_details_input['source'] ) );
                    if ( in_array( $source_input, $valid_wc_sources, true ) ) {
                        $sanitized_rule_detail['source'] = $source_input;
                    }
                    // If $source_input is not in $valid_wc_sources, it remains the default ''
                }

                // Sanitize the 'static_value'
                if ( isset( $rule_details_input['static_value'] ) ) {
                    $sanitized_rule_detail['static_value'] = sanitize_text_field( wp_unslash( $rule_details_input['static_value'] ) );
                }

                $sanitized_rules[$clean_merchant_key] = $sanitized_rule_detail;
            }

            return $sanitized_rules;
        }
    }
} 