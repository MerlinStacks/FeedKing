# Changelog

## [0.3.0] - YYYY-MM-DD

### Added
- Initial plugin structure for Advanced Product Feed for Google Shopping.
- Custom Post Type (`apfgs_product_feed`) for managing feed configurations.
- Admin interface for managing feeds (List, Add New, Edit).
    - Basic feed settings: Feed Name, File Format (XML, CSV, TXT), Target Merchant (Google Shopping), File Name Pattern, Refresh Interval (UI only), Product Status Filter.
    - Attribute Mapping UI for core Google attributes.
    - AJAX functionality for deleting and regenerating feeds from the admin interface.
- Basic feed generation for XML, CSV, and TXT formats (single batch).
- Product data retrieval for feed items, including core product details.
- Attribute mapping logic for default values and some source types (product properties, meta, static).
- Basic product status filtering in feed generation.
- Logging utility (`APFGS_Logger`) for debugging.
- JavaScript for admin interactions (copy URL, delete/regenerate feed, attribute mapping UI).
- Main plugin file with activation/deactivation hooks (creates feed directory).

### Changed
- N/A (Initial documented version)

### Fixed
- N/A (Initial documented version)

### Removed
- N/A (Initial documented version)