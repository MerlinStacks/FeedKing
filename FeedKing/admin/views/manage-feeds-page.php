<!-- Manage Feeds Page --> 
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Manage Product Feeds', 'apfgs' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=apfgs-edit-feed' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Add New Feed', 'apfgs' ); ?>
    </a>

    <hr class="wp-header-end">

    <?php
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'Advanced Product Feed for Google Shopping requires WooCommerce to be active.', 'apfgs' );
        echo '</p></div>';
        return; // Stop further processing if WooCommerce is not active
    }
    ?>

    <form method="get">
        <input type="hidden" name="page" value="apfgs-manage-feeds" />
        <?php
        // Placeholder for a WP_List_Table implementation
        // For now, let's simulate fetching feeds from APFGS_Feed_Manager
        $feed_manager = APFGS_Feed_Manager::instance();
        $feeds = $feed_manager->get_all_feeds(); // This method needs to be implemented in APFGS_Feed_Manager

        // Dummy data for now:
        // $feeds = array(
        //    // (object) array( 'ID' => 1, 'post_title' => 'My First Google Feed', 'post_status' => 'publish' ),
        //    // (object) array( 'ID' => 2, 'post_title' => 'Facebook Catalog Feed', 'post_status' => 'publish' ),
        // );

        if ( empty( $feeds ) ) {
            echo '<div class="no-items"><p>' . esc_html__( 'No feeds found. Get started by creating one!', 'apfgs' ) . '</p></div>';
        } else {
            ?>
            <table class="wp-list-table widefat fixed striped table-view-list posts">
                <thead>
                    <tr>
                        <th scope="col" id="title" class="manage-column column-title column-primary">
                            <?php esc_html_e( 'Feed Name', 'apfgs' ); ?>
                        </th>
                        <th scope="col" id="status" class="manage-column column-status">
                            <?php esc_html_e( 'Status', 'apfgs' ); ?>
                        </th>
                        <th scope="col" id="file_url" class="manage-column column-file_url">
                            <?php esc_html_e( 'Feed URL', 'apfgs' ); ?>
                        </th>
                        <th scope="col" id="last_generated" class="manage-column column-last_generated">
                            <?php esc_html_e( 'Last Generated', 'apfgs' ); ?>
                        </th>
                         <th scope="col" id="actions" class="manage-column column-actions">
                            <?php esc_html_e( 'Actions', 'apfgs' ); ?>
                        </th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php foreach ( $feeds as $feed ) : ?>
                        <?php 
                        // In a real scenario, you'd get these details from post meta or feed object properties
                        $feed_id = $feed->ID;
                        $feed_title = $feed->feed_name; // Using the property from get_all_feeds
                        $feed_status_label = ucfirst( $feed->post_status ); // Example
                        $feed_url = $feed->feed_url;
                        $feed_url_placeholder = isset($feed->feed_url_placeholder) ? $feed->feed_url_placeholder : null;
                        $last_generated_timestamp = $feed->last_generated;
                        $last_generated = $last_generated_timestamp ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_generated_timestamp ) : 'N/A';
                        
                        // Dummy values for display since $feeds is empty or dummy
                        // $feed_id = isset($feed->ID) ? $feed->ID : rand(1,100);
                        // $feed_title = isset($feed->post_title) ? $feed->post_title : 'Sample Feed ' . $feed_id;
                        // $feed_status_label = 'Published';
                        // $feed_url = 'path/to/feed-'.$feed_id.'.xml';
                        // $last_generated = 'Never';
                        ?>
                        <tr>
                            <td class="title column-title has-row-actions column-primary" data-colname="Feed Name">
                                <strong>
                                    <a class="row-title" href="<?php echo esc_url( admin_url( 'admin.php?page=apfgs-edit-feed&feed_id=' . $feed_id ) ); ?>">
                                        <?php echo esc_html( $feed_title ); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=apfgs-edit-feed&feed_id=' . $feed_id ) ); ?>"><?php esc_html_e( 'Edit', 'apfgs' ); ?></a> | </span>
                                    <span class="trash"><a href="#" class="submitdelete" data-feed-id="<?php echo esc_attr( $feed_id ); ?>"><?php esc_html_e( 'Delete', 'apfgs' ); ?></a> | </span>
                                    <span class="view"><a href="#" class="regenerate-feed" data-feed-id="<?php echo esc_attr( $feed_id ); ?>"><?php esc_html_e( 'Regenerate', 'apfgs' ); ?></a></span>
                                </div>
                            </td>
                            <td class="status column-status" data-colname="Status">
                                <?php echo esc_html( $feed_status_label ); ?>
                            </td>
                            <td class="file_url column-file_url" data-colname="Feed URL">
                                <?php if ($feed_url !== 'N/A') : ?>
                                    <a href="<?php echo esc_url( $feed_url ); ?>" target="_blank"><?php echo esc_html( basename($feed_url) ); ?></a>
                                    <button type="button" class="button button-small apfgs-copy-url-button" data-url="<?php echo esc_url( $feed_url ); ?>"><?php esc_html_e( 'Copy URL', 'apfgs' ); ?></button>
                                <?php elseif ($feed_url_placeholder): ?>
                                    <?php echo esc_html( basename($feed_url_placeholder) ); ?> (<?php esc_html_e('Not generated yet', 'apfgs'); ?>)
                                <?php else : ?>
                                    <?php echo esc_html( $feed_url ); ?>
                                <?php endif; ?>
                            </td>
                            <td class="last_generated column-last_generated" data-colname="Last Generated">
                                <?php echo esc_html( $last_generated ); ?>
                            </td>
                            <td class="actions column-actions" data-colname="Actions">
                                <button type="button" class="button button-secondary regenerate-feed" data-feed-id="<?php echo esc_attr( $feed_id ); ?>">
                                    <?php esc_html_e( 'Regenerate', 'apfgs' ); ?>
                                </button>
                                <!-- Add more actions like duplicate, etc. -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Feed Name', 'apfgs' ); ?></th>
                        <th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'apfgs' ); ?></th>
                        <th scope="col" class="manage-column column-file_url"><?php esc_html_e( 'Feed URL', 'apfgs' ); ?></th>
                        <th scope="col" class="manage-column column-last_generated"><?php esc_html_e( 'Last Generated', 'apfgs' ); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'apfgs' ); ?></th>
                    </tr>
                </tfoot>
            </table>
            <?php
        }
        ?>
    </form>
    <div id="apfgs-ajax-response"></div>
</div> 