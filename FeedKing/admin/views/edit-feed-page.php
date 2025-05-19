<!-- Edit Feed Page --> 
<div class="wrap">
    <?php
    // Determine if we are editing an existing feed or adding a new one
    $feed_id = isset( $_GET['feed_id'] ) ? intval( $_GET['feed_id'] ) : 0;
    $is_editing = $feed_id > 0;
    $feed_name = '';
    $current_settings = array(
        'file_format' => 'xml', // Default value
        'merchant_type' => 'google_shopping', // Default value
        // Add other default settings here
    );

    if ( $is_editing ) {
        $loaded_feed_data = APFGS_Feed_Manager::instance()->get_feed( $feed_id );
        if ( ! $loaded_feed_data ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Feed not found. You will be creating a new feed if you save.', 'apfgs' ) . '</p></div>';
            $is_editing = false; // Treat as new if not found
            $feed_id = 0; // Reset feed_id
        } else {
            $feed_name = $loaded_feed_data['feed_name'];
            if (isset($loaded_feed_data['feed_settings']) && is_array($loaded_feed_data['feed_settings'])) {
                 // Merge loaded settings with defaults, loaded taking precedence
                $current_settings = array_merge($current_settings, $loaded_feed_data['feed_settings']);
            }
        }
    } else {
        // For new feeds, $feed_name is empty and $current_settings uses defaults
    }
    ?>

    <h1 class="wp-heading-inline">
        <?php echo $is_editing ? esc_html__( 'Edit Feed', 'apfgs' ) : esc_html__( 'Add New Feed', 'apfgs' ); ?>
    </h1>

    <?php if ( ! $is_editing ) : ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=apfgs-manage-feeds' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Back to Manage Feeds', 'apfgs' ); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php
    // Display admin notices
    if ( isset( $_GET['apfgs_message'] ) ) {
        $message_type = sanitize_text_field( $_GET['apfgs_message'] );
        $notice_class = 'notice-success';
        $message_text = '';
        if ( $message_type === 'created' ) {
            $message_text = __( 'Feed created successfully.', 'apfgs' );
        } elseif ( $message_type === 'updated' ) {
            $message_text = __( 'Feed updated successfully.', 'apfgs' );
        }
        if ( $message_text ) {
            echo '<div class="notice is-dismissible ' . esc_attr( $notice_class ) . '"><p>' . esc_html( $message_text ) . '</p></div>';
        }
    }
    if ( isset( $_GET['apfgs_error'] ) ) {
        $error_type = sanitize_text_field( $_GET['apfgs_error'] );
        $error_text = '';
        if ( $error_type === 'empty_name' ) {
            $error_text = __( 'Feed name cannot be empty.', 'apfgs' );
        } elseif ( $error_type === 'save_failed' ) {
            $custom_message = isset( $_GET['message'] ) ? urldecode( sanitize_text_field( $_GET['message'] ) ) : 'An unknown error occurred.';
            $error_text = sprintf(__( 'Failed to save feed: %s', 'apfgs' ), esc_html($custom_message));
        }
        if ( $error_text ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post( $error_text ) . '</p></div>';
        }
    }

    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'Advanced Product Feed for Google Shopping requires WooCommerce to be active.', 'apfgs' );
        echo '</p></div>';
        return; // Stop further processing if WooCommerce is not active
    }
    ?>

    <form method="post" id="apfgs-feed-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'apfgs_save_feed_settings', 'apfgs_feed_nonce' ); ?>
        <input type="hidden" name="feed_id" value="<?php echo esc_attr( $feed_id ); ?>">
        <input type="hidden" name="action" value="apfgs_save_feed">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e( 'Enter feed name here', 'apfgs' ); ?></label>
                            <input type="text" name="feed_name" size="30" value="<?php echo esc_attr( $feed_name ); ?>" id="title" spellcheck="true" autocomplete="off" placeholder="<?php esc_attr_e( 'Enter feed name here', 'apfgs' ); ?>" required/>
                        </div>
                    </div>
                    
                    <?php // More form fields for feed settings will go here (e.g., using metaboxes or directly) ?>
                    <div class="apfgs-settings-section">
                        <h2><?php esc_html_e( 'Basic Configuration', 'apfgs' ); ?></h2>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="apfgs-file-format"><?php esc_html_e( 'File Format', 'apfgs' ); ?></label>
                                    </th>
                                    <td>
                                        <select name="feed_settings[file_format]" id="apfgs-file-format">
                                            <option value="xml" <?php selected( $current_settings['file_format'], 'xml' ); ?>>XML</option>
                                            <option value="csv" <?php selected( $current_settings['file_format'], 'csv' ); ?>>CSV</option>
                                            <option value="txt" <?php selected( $current_settings['file_format'], 'txt' ); ?>>TXT</option>
                                        </select>
                                    </td>
                                </tr>
                                 <tr>
                                    <th scope="row">
                                        <label for="apfgs-merchant-type"><?php esc_html_e( 'Target Merchant', 'apfgs' ); ?></label>
                                    </th>
                                    <td>
                                        <select name="feed_settings[merchant_type]" id="apfgs-merchant-type">
                                            <option value="google_shopping" <?php selected( $current_settings['merchant_type'], 'google_shopping' ); ?>>Google Shopping</option>
                                            <?php // Add other merchant types as needed ?>
                                        </select>
                                        <p class="description"><?php esc_html_e('The generated feed will be formatted for this merchant.', 'apfgs');?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apfgs-file-name-pattern"><?php esc_html_e( 'File Name Pattern', 'apfgs' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="feed_settings[file_name_pattern]" id="apfgs-file-name-pattern" value="<?php echo esc_attr( isset($current_settings['file_name_pattern']) ? $current_settings['file_name_pattern'] : 'google-shopping-{date}.xml' ); ?>" class="regular-text"/>
                                        <p class="description"><?php esc_html_e( 'Pattern for the generated file name. Use {date} for a timestamp (YYYY-MM-DD-HHMMSS). Ex: my-feed-{date}.xml', 'apfgs' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apfgs-refresh-interval"><?php esc_html_e( 'Refresh Interval', 'apfgs' ); ?></label>
                                    </th>
                                    <td>
                                        <select name="feed_settings[refresh_interval]" id="apfgs-refresh-interval">
                                            <?php
                                            $intervals = array(
                                                '' => __( 'Manual Only', 'apfgs' ), // No automatic refresh
                                                'hourly' => __( 'Hourly', 'apfgs' ),
                                                'twicedaily' => __( 'Twice Daily (every 12 hours)', 'apfgs' ),
                                                'daily' => __( 'Daily', 'apfgs' ),
                                                // 'custom' => __( 'Custom (via Action Scheduler hook)', 'apfgs' ) // More advanced
                                            );
                                            $current_interval = isset($current_settings['refresh_interval']) ? $current_settings['refresh_interval'] : '';
                                            foreach ($intervals as $value => $label) {
                                                echo '<option value="' . esc_attr($value) . '" ' . selected($current_interval, $value, false) . '>' . esc_html($label) . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <p class="description"><?php esc_html_e( 'How often the feed should be automatically regenerated. Uses Action Scheduler.', 'apfgs' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="apfgs-product-status-filter"><?php esc_html_e( 'Product Status Filter', 'apfgs' ); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        $product_statuses = array(
                                            'publish' => __( 'Published Only', 'apfgs' ),
                                            'private' => __( 'Private Only', 'apfgs' ),
                                            'all_except_draft' => __( 'All Except Drafts (Published + Private)', 'apfgs' ),
                                            'all'     => __( 'All Statuses (including drafts - not recommended for live feeds)', 'apfgs' ),
                                        );
                                        $current_status_filter = isset($current_settings['product_status_filter']) ? $current_settings['product_status_filter'] : 'publish';
                                        ?>
                                        <select name="feed_settings[product_status_filter]" id="apfgs-product-status-filter">
                                        <?php
                                        foreach ($product_statuses as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '" ' . selected($current_status_filter, $value, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                        </select>
                                        <p class="description"><?php esc_html_e( 'Filter products based on their status.', 'apfgs' ); ?></p>
                                    </td>
                                </tr>
                                <?php // Add more basic settings fields here, populating them with $current_settings values ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="apfgs-settings-section">
                         <h2><?php esc_html_e( 'Attribute Mapping', 'apfgs' ); ?></h2>
                         <p class="description"><?php esc_html_e( 'Configure how product attributes map to merchant attributes.', 'apfgs' ); ?></p>
                         <?php // Placeholder for attribute mapping UI ?>
                         <table class="form-table apfgs-attribute-mapping-table widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Merchant Attribute (Google)', 'apfgs' ); ?></th>
                                    <th><?php esc_html_e( 'WooCommerce Source / Value', 'apfgs' ); ?></th>
                                    <th><?php esc_html_e( 'Static Value', 'apfgs' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $merchant_attributes_to_map = array(
                                    'g:id' => __( 'Product ID', 'apfgs' ),
                                    'g:title' => __( 'Title', 'apfgs' ),
                                    'g:description' => __( 'Description', 'apfgs' ),
                                    'g:link' => __( 'Link', 'apfgs' ),
                                    'g:image_link' => __( 'Image Link', 'apfgs' ),
                                    'g:availability' => __( 'Availability', 'apfgs' ),
                                    'g:price' => __( 'Price', 'apfgs' ),
                                    'g:sale_price' => __( 'Sale Price', 'apfgs' ),
                                    'g:condition' => __( 'Condition', 'apfgs' ),
                                    'g:brand' => __( 'Brand', 'apfgs' ),
                                    'g:gtin' => __( 'GTIN (UPC/EAN/ISBN)', 'apfgs' ),
                                    'g:mpn' => __( 'MPN', 'apfgs' ),
                                    // Add more Google attributes as the plugin evolves
                                );

                                $available_wc_sources = APFGS_Attribute_Mapper::instance()->get_wc_attributes();
                                $current_mapping_rules = isset($current_settings['mapping_rules']) && is_array($current_settings['mapping_rules']) ? $current_settings['mapping_rules'] : array();

                                foreach ($merchant_attributes_to_map as $g_attr_key => $g_attr_label) :
                                    $mapped_source = isset($current_mapping_rules[$g_attr_key]['source']) ? $current_mapping_rules[$g_attr_key]['source'] : '';
                                    $static_value = isset($current_mapping_rules[$g_attr_key]['static_value']) ? $current_mapping_rules[$g_attr_key]['static_value'] : '';
                                ?>
                                <tr>
                                    <td>
                                        <label for="mapping-<?php echo esc_attr($g_attr_key); ?>">
                                            <?php echo esc_html($g_attr_label); ?> (<code><?php echo esc_html($g_attr_key); ?></code>)
                                        </label>
                                    </td>
                                    <td>
                                        <select name="feed_settings[mapping_rules][<?php echo esc_attr($g_attr_key); ?>][source]" id="mapping-<?php echo esc_attr($g_attr_key); ?>" class="apfgs-source-select regular-text">
                                            <option value=""><?php esc_html_e( '-- Default (from product) --', 'apfgs' ); ?></option>
                                            <?php foreach ($available_wc_sources as $source_key => $source_label) : ?>
                                                <option value="<?php echo esc_attr($source_key); ?>" <?php selected($mapped_source, $source_key); ?>>
                                                    <?php echo esc_html($source_label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="feed_settings[mapping_rules][<?php echo esc_attr($g_attr_key); ?>][static_value]" 
                                               value="<?php echo esc_attr($static_value); ?>" 
                                               class="apfgs-static-value-input regular-text" 
                                               style="display: <?php echo ($mapped_source === 'static_value') ? 'block' : 'none'; ?>;"/>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                         </table>
                         <p class="description">
                            <?php esc_html_e('Select a WooCommerce source for each merchant attribute. If "-- Default --" is chosen, the plugin will try to use the most common product field. If "-- Enter Static Value --" is chosen, the text box next to it will be used.', 'apfgs'); ?>
                         </p>
                    </div>

                     <div class="apfgs-settings-section">
                         <h2><?php esc_html_e( 'Filtering Rules', 'apfgs' ); ?></h2>
                         <p class="description"><?php esc_html_e( 'Set conditions to include/exclude products from the feed.', 'apfgs' ); ?></p>
                         <?php // Placeholder for filtering UI ?>
                    </div>

                </div><!-- /post-body-content -->

                <div id="postbox-container-1" class="postbox-container">
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div id="submitdiv" class="postbox">
                            <button type="button" class="handlediv button-link" aria-expanded="true">
                                <span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Publish', 'apfgs' ); ?></span>
                                <span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Publish', 'apfgs' ); ?></span></h2>
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div id="major-publishing-actions">
                                        <div id="delete-action">
                                            <?php if ( $is_editing ) : ?>
                                                <a class="submitdelete deletion apfgs-delete-feed-button" href="#">
                                                    <?php esc_html_e( 'Delete Feed', 'apfgs' ); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <div id="publishing-action">
                                            <span class="spinner"></span>
                                            <input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php echo $is_editing ? esc_attr__( 'Update Feed', 'apfgs' ) : esc_attr__( 'Save Feed', 'apfgs' ); ?>">
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                    <?php if ( $is_editing ) : ?>
                                    <div id="minor-publishing-actions">
                                        <div id="preview-action">
                                            <a href="#" class="button apfgs-regenerate-feed-button" data-feed-id="<?php echo esc_attr( $feed_id ); ?>">
                                                <?php esc_html_e( 'Regenerate Feed', 'apfgs' ); ?>
                                            </a>
                                             <span class="spinner apfgs-regenerate-spinner" style="float: none;"></span>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                     <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div id="feedstatusdiv" class="postbox">
                             <h2 class="hndle"><span><?php esc_html_e( 'Feed Status & URL', 'apfgs' ); ?></span></h2>
                             <div class="inside">
                                <?php if ( $is_editing && !empty($current_settings['generated_file_name']) ): ?>
                                    <?php
                                    $upload_dir = wp_upload_dir();
                                    $feed_file_url = trailingslashit( $upload_dir['baseurl'] ) . 'apfgs-feeds/' . $current_settings['generated_file_name'];
                                    $last_generated_time = !empty($current_settings['last_generated']) ? 
                                                           date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $current_settings['last_generated'] ) :
                                                           __('Never', 'apfgs');
                                    ?>
                                    <p>
                                        <strong><?php esc_html_e( 'File URL:', 'apfgs' ); ?></strong> 
                                        <a href="<?php echo esc_url( $feed_file_url ); ?>" target="_blank" id="apfgs-feed-file-url-display-link">
                                            <?php echo esc_html( $current_settings['generated_file_name'] ); ?>
                                        </a>
                                        <button type="button" class="button button-small apfgs-copy-url-button" data-url="<?php echo esc_url( $feed_file_url ); ?>">
                                           <?php esc_html_e( 'Copy URL', 'apfgs' ); ?>
                                       </button>
                                    </p>
                                    <p><strong><?php esc_html_e( 'Last Generated:', 'apfgs' ); ?></strong> <span id="apfgs-feed-last-generated-display"><?php echo esc_html( $last_generated_time ); ?></span></p>
                                <?php elseif ($is_editing): ?>
                                     <p><?php esc_html_e( 'Feed has not been generated yet. Save and then regenerate.', 'apfgs' ); ?></p>
                                <?php else: ?>
                                    <p><?php esc_html_e( 'Save the feed to generate its URL and see status.', 'apfgs' ); ?></p>
                                <?php endif; ?>
                             </div>
                        </div>

                    </div>
                </div><!-- /postbox-container-1 -->
            </div><!-- /post-body -->
            <br class="clear">
        </div><!-- /poststuff -->
    </form>
</div> 