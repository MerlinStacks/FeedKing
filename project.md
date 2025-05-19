Building a Modern, High-Performance WooCommerce Google Shopping Feed Plugin (CTX Feed Pro Inspired)
This guide outlines the structure and key components for developing a modern, fast-loading WooCommerce plugin to generate Google Shopping product feeds. It emphasizes local asset management (no CDNs or external loading), handling all product types, offering product status filters, and providing multiple output formats (XML, CSV, TXT). It incorporates many features found in advanced plugins like CTX Feed Pro.
Project Scope: This is a complex project. CTX Feed Pro has numerous features developed over time. This guide will help you build a performant foundation and understand the requirements for advanced functionalities with a focus on modern WordPress development practices.
1. Core Plugin Structure
Your plugin will follow the standard WordPress plugin architecture.
a. Main Plugin File (your-plugin-name.php)
This file will contain the plugin header, initialize the main plugin class, and handle activation/deactivation hooks. It's crucial for enqueuing local assets correctly.
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
define( 'APFGS_VERSION', '0.3.0' ); // Updated version

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
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-admin.php'; // Handles admin menu, page loading, and asset enqueuing
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-feed-manager.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-feed-generator.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-attribute-mapper.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-filter-engine.php';
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-scheduler.php'; // Renamed from class-apfgs-cron.php for clarity with Action Scheduler
            require_once APFGS_PLUGIN_DIR . 'includes/class-apfgs-settings.php';
            require_once APFGS_PLUGIN_DIR . 'includes/utils/class-apfgs-logger.php'; // For logging
            // Add more includes for settings, utilities, etc.
        }

        private function init_hooks() {
            add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

            // Initialize admin functionalities (including asset enqueuing)
            if ( is_admin() ) {
                APFGS_Admin::instance(); // APFGS_Admin will handle its own hooks for enqueuing scripts/styles conditionally
            }

            // Initialize scheduler functionalities
            APFGS_Scheduler::instance();
        }

        public function load_plugin_textdomain() {
            load_plugin_textdomain( 'apfgs', false, dirname( plugin_basename( APFGS_PLUGIN_FILE ) ) . '/languages/' );
        }

        public function activate() {
            $upload_dir = wp_upload_dir();
            $feed_dir_path = trailingslashit( $upload_dir['basedir'] ) . 'apfgs-feeds';
            if ( ! file_exists( $feed_dir_path ) ) {
                wp_mkdir_p( $feed_dir_path );
            }
            // Create an index.html file in the feed directory to prevent directory listing
            if ( ! file_exists( $feed_dir_path . '/index.html' ) ) {
                 // @codingStandardsIgnoreStart
                @file_put_contents( $feed_dir_path . '/index.html', '' );
                 // @codingStandardsIgnoreEnd
            }
            // Create .htaccess to deny direct access to XML/CSV/TXT if needed (more secure)
            if ( ! file_exists( $feed_dir_path . '/.htaccess' ) ) {
                 $htaccess_content = "Options -Indexes\ndeny from all";
                  // @codingStandardsIgnoreStart
                 @file_put_contents( $feed_dir_path . '/.htaccess', $htaccess_content );
                 // @codingStandardsIgnoreEnd
            }


            APFGS_Scheduler::instance()->schedule_events(); // Schedule events with Action Scheduler
            // Set default options if any
            // flush_rewrite_rules(); // Only if CPTs or custom rewrite rules are added/removed
        }

        public function deactivate() {
            APFGS_Scheduler::instance()->clear_scheduled_events();
            // flush_rewrite_rules();
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


b. Directory Structure (Example)
your-plugin-name/
├── your-plugin-name.php        (Main plugin file)
├── includes/
│   ├── class-apfgs-admin.php         (Handles admin registration, page loading, asset enqueuing)
│   ├── class-apfgs-feed-manager.php
│   ├── class-apfgs-feed-generator.php
│   ├── class-apfgs-attribute-mapper.php
│   ├── class-apfgs-filter-engine.php
│   ├── class-apfgs-scheduler.php     (Manages Action Scheduler tasks)
│   ├── class-apfgs-settings.php
│   └── utils/                        (Utility classes)
│       └── class-apfgs-logger.php
├── admin/
│   ├── class-apfgs-admin-ui.php    (Builds the admin interface pages - could be part of class-apfgs-admin.php)
│   ├── views/                      (PHP templates for admin pages, minimal HTML/PHP)
│   │   ├── manage-feeds-page.php
│   │   └── edit-feed-page.php
│   └── assets/                     (ALL local assets go here)
│       ├── css/
│       │   └── apfgs-admin-styles.min.css (Minified)
│       ├── js/
│       │   └── apfgs-admin-scripts.min.js (Minified, Vanilla JS or small self-contained)
│       └── images/
│           └── icon.png
├── languages/                      (Translation files .pot, .po, .mo)
└── templates/                      (Feed templates, e.g., xml-template.php - if using PHP for templating feeds)


2. Key Features to Implement (Inspired by CTX Feed Pro)
a. Feed Configuration & Management (class-apfgs-admin.php, class-apfgs-feed-manager.php)
Admin UI:
Create admin pages using WordPress standard UI elements where possible to minimize custom CSS/JS.
For dynamic interactions, use Vanilla JavaScript or WordPress's built-in libraries.
All CSS and JS for the admin UI must be loaded locally from the admin/assets/ directory and enqueued conditionally only on your plugin's pages.
Utilize the WordPress AJAX API for background operations, ensuring they are efficient.
Storage: Custom Post Types (CPTs) remain recommended for flexibility.
Feed Settings (per feed):
Feed Name
Target Merchant (e.g., Google Shopping)
File Format (XML, CSV, TXT)
Refresh Interval (for Action Scheduler)
File Name Pattern (e.g., google-shopping-{date}.xml)
Product Status Filter (e.g., All Products, Published Only, Private/Hidden Only)
Filtering rules (as defined in Filter Engine)
Attribute mapping rules
Category mapping rules
b. Product Data Retrieval (class-apfgs-feed-generator.php)
Use wc_get_products() or WP_Query with highly optimized arguments, including post_status based on feed settings.
Handling All WooCommerce Product Types:
Simple Products: Retrieve standard product data (ID, title, description, price, SKU, images, etc.).
Variable Products & Variations:
Option to include parent product, variations only, or both (using item_group_id for variations, linking them to the parent).
Fetch variation-specific attributes (color, size), SKU, price, image.
Grouped Products:
Option to include child products as individual items in the feed (recommended for Google Shopping).
Alternatively, represent the grouped product itself (less common for feeds requiring individual SKUs).
External/Affiliate Products:
Include the external product URL (get_product_url()) as the link.
The price and other details are as entered in WooCommerce.
Be mindful of Google's policies regarding affiliate links in Shopping ads.
Downloadable & Virtual Products:
Include as regular products.
The g:shipping attribute may not be applicable or require specific handling (e.g., no shipping cost).
Ensure compliance with Google's policies for digital goods if applicable.
Performance Considerations for Data Retrieval:
Selective Loading: Fetch only the product data fields absolutely necessary for the feed.
Efficient Queries: Carefully construct query arguments to be as specific as possible.
Caching: Implement WordPress Transients API to cache parts of product data or entire generated feeds.
Avoid N+1 Queries: When fetching related data (meta, terms) for multiple products, do it in bulk.
c. Attribute Mapping (class-apfgs-attribute-mapper.php)
(As previously defined, ensure UI is performant and uses local assets). Logic must be able to pull data correctly based on product type (e.g., variation attributes vs. parent attributes).
d. Dynamic Attributes & Conditional Logic (class-apfgs-attribute-mapper.php, class-apfgs-filter-engine.php)
(As previously defined, ensure UI is performant and uses local assets. Logic should be optimized and adaptable to different product types).
e. Filtering Engine (class-apfgs-filter-engine.php)
UI: Allow users to set conditions to include/exclude products from the feed.
Filter Types:
By Category, Tag.
By Stock Status (e.g., only 'instock').
By Product Type (simple, variable, variation, grouped, external).
By Product Status/Visibility:
publish (Published)
private (Hidden from general view, accessible with direct link if permissions allow)
draft (Unpublished drafts)
Option for "All except drafts" or specific combinations.
The query for products will need to adjust its post_status argument accordingly.
By Price Range.
By Custom Field values.
By Product ID/SKU list (include/exclude).
Logic: Apply these filters during the initial product query (wc_get_products arguments) for maximum efficiency.
f. Handling Product Variations (class-apfgs-feed-generator.php)
(Covered under "Handling All WooCommerce Product Types", ensuring robust options for variation export).
g. Feed Generation (class-apfgs-feed-generator.php)
Output Formats (XML, CSV, TXT):
The core data retrieval and attribute mapping logic will be largely the same.
The final output stage will differ based on the selected format.
XML: Use DOMDocument or XMLWriter for robust and efficient XML creation, adhering to Google's schema.
CSV: Use fputcsv() for standard CSV generation. Include a header row based on mapped Google attributes.
TXT (Tab-Delimited): Manually build rows with tab separators. Include a header row.
Batch Processing: This is critical for performance and reliability with large stores.
Process products in manageable chunks (e.g., 50-200 at a time) during feed generation.
Managed via successive AJAX calls (manual generation) or chained Action Scheduler tasks (background generation).
File Output: Save generated files to the wp-content/uploads/apfgs-feeds/ directory.
h. Scheduling (Using Action Scheduler - class-apfgs-scheduler.php)
Utilize Action Scheduler (bundled with WooCommerce Core).
Schedule feed generation tasks using as_schedule_recurring_action() or as_schedule_single_action().
Allow users to set update frequency per feed.
Implement robust error handling and logging for scheduled tasks (e.g., using APFGS_Logger).
i. Advanced Features (Considerations for future development)
(As previously defined, always considering performance and local asset management).
Category Mapping: For Google's taxonomy, parse the local text file provided by Google.
j. UI/UX for Admin Panel (class-apfgs-admin-ui.php or integrated into class-apfgs-admin.php)
Clarity and Ease of Use: (As previously defined).
WordPress Look and Feel: (As previously defined).
Asset Management & Performance (CRITICAL):
Local Assets Only: ALL CSS, JavaScript, images, and any fonts must be bundled with the plugin. No CDNs or external URLs.
Minification: Minify all CSS and JavaScript files.
Conditional Loading: Enqueue scripts and styles only on your plugin's specific admin pages.
Fonts: Rely on system fonts or fonts already available in the WordPress admin dashboard.
JavaScript: Prefer Vanilla JavaScript for simplicity and performance.
Error Reporting: (As previously defined).
3. Google Shopping Feed Specification Overview
(This section remains largely the same as it's about Google's requirements, not plugin architecture. However, remember that specific attributes like g:shipping or g:is_bundle might need different handling based on the WooCommerce product type.)
Your plugin must generate feeds compliant with Google's Product data specification. Key attributes include:
(List of attributes as previously defined)
Refer to the official Google Merchant Center product data specification for the complete and up-to-date list.
4. Development Workflow & Considerations
Performance First: Prioritize efficient code, optimized database queries, and minimal asset loading.
Local Assets Only: Strictly adhere to the "no CDN / no external loading" policy.
Start Simple & Iterate: (As previously defined).
Modularity: (As previously defined).
WooCommerce & WordPress Best Practices: (As previously defined).
Security: (As previously defined).
Performance Profiling: Regularly use tools like Query Monitor.
Minimize Dependencies: (As previously defined).
Error Handling & Logging: Implement a robust logging mechanism.
Testing: Test thoroughly with all WooCommerce product types (Simple, Variable, Variation, Grouped, External, Downloadable, Virtual), different visibility/status settings (published, private, draft), and all output file formats (XML, CSV, TXT).
Code Standards: Adhere to WordPress coding standards.
5. Conclusion
Building a modern, high-performance WooCommerce product feed plugin that comprehensively covers all product types, offers flexible filtering, and supports multiple output formats requires careful planning and execution. By prioritizing local assets, optimized code, and leveraging tools like Action Scheduler, you can create a fast, reliable, and feature-rich plugin. Good luck!
