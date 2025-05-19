# Todo List

## Housekeeping
- Remove outdated "This file is intentionally blank" comments from all relevant class files.
- Review and remove any other placeholder code or comments that are no longer applicable.

## Core Feature Implementation & Completion
- **Feed Generation:**
    - Implement full batch processing in `APFGS_Feed_Generator` for large product catalogs.
- **Filter Engine (`APFGS_Filter_Engine` & UI):**
    - Implement logic for all planned filter types (Category, Tag, Product Type, Price Range, Custom Fields, SKU/ID list).
    - Implement UI for configuring these filters on the "Edit Feed" page.
    - Implement `sanitize_filter_rules()` in `APFGS_Filter_Engine`.
- **Scheduler (`APFGS_Scheduler`):**
    - Fully implement Action Scheduler integration for recurring feed generation based on per-feed settings.
    - Implement `schedule_events()`, `clear_scheduled_events()`, and `process_feed_generation()`.
- **Attribute Mapping (`APFGS_Attribute_Mapper`):**
    - Implement full support for dynamic attributes and conditional logic.
    - Provide concrete implementations or clear UI options for mapping `g:brand`, `g:gtin`, `g:mpn`.
- **Plugin Settings (`APFGS_Settings`):**
    - Decide if a global plugin settings page is needed. If so, implement using WordPress Settings API.
    - Uncomment and complete `register_settings()` and related methods.
- **Admin UI (`class-apfgs-admin-ui.php`):**
    - Determine if this class is needed or if all UI rendering will remain in `APFGS_Admin`. If not needed, consider removing the file.

## Enhancements & Refinements
- **Manage Feeds Page:**
    - Implement `WP_List_Table` for improved UX (sorting, pagination).
- **Feed Manager (`APFGS_Feed_Manager`):**
    - Uncomment and test the feed file deletion logic in `delete_feed()`.
- **Admin Assets:**
    - Add necessary custom styles to `apfgs-admin-styles.min.css`.
    - Improve AJAX notifications in `apfgs-admin-scripts.min.js` (e.g., use WordPress notices instead of `alert()`).
- **Code Review:**
    - Review the double call to `APFGS_Scheduler::instance()->schedule_events()` in the main plugin file.
    - Ensure adherence to WordPress coding standards.
- **Error Handling & Logging:**
    - Enhance error reporting and logging throughout the plugin.

## Testing
- Conduct thorough testing with all WooCommerce product types (Simple, Variable, Variation, Grouped, External, Downloadable, Virtual).
- Test all output file formats (XML, CSV, TXT) with various configurations.
- Perform performance profiling, especially after batch processing implementation.

## Documentation
- Update inline code comments and PHPDoc blocks.
- Consider creating user documentation for setting up and using the plugin.