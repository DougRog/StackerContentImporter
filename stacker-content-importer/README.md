# Stacker Content Importer

A WordPress plugin that imports content from Stacker feeds and manages it as a separate content section with proper canonical URLs, tracking pixel preservation, and image restrictions.

## Features

- **Separate Content Section**: Creates a custom post type (`stacker_content`) to keep Stacker articles separate from regular posts
- **Automatic Import**: Scheduled automatic imports from Stacker RSS/Atom feeds
- **Manual Import**: One-click manual import from the admin panel
- **Canonical URL Management**: Automatically adds proper canonical URLs to imported content
- **Tracking Pixel Preservation**: Preserves and displays tracking pixels from the original content
- **Image Management**: Downloads images locally and restricts their use to Stacker content only
- **Category Support**: Imports and assigns categories from the feed
- **Duplicate Prevention**: Checks for existing content to avoid duplicates
- **REST API Support**: Full support for WordPress REST API

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `stacker-content-importer` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to 'Stacker Content' → 'Import Settings' to configure the feed URL

### Configuration

1. Navigate to **Stacker Content → Import Settings**
2. Enter your Stacker feed URL (default is pre-configured)
3. Select the import frequency (hourly, twice daily, or daily)
4. Click **Save Settings**

## Usage

### Manual Import

1. Go to **Stacker Content → Import Settings**
2. Click the **Import Now** button
3. The plugin will fetch and import new articles from the feed

### Automatic Import

The plugin automatically imports content based on the configured frequency. The import runs in the background via WordPress cron.

### Viewing Imported Content

1. Go to **Stacker Content** in the WordPress admin menu
2. View, edit, or delete imported articles
3. Articles are published automatically upon import

## Technical Details

### Custom Post Type

- **Post Type**: `stacker_content`
- **Slug**: `stacker-content`
- **Archive**: Yes
- **REST API**: Enabled

### Custom Taxonomy

- **Taxonomy**: `stacker_category`
- **Hierarchical**: Yes
- **REST API**: Enabled

### Metadata

Each imported article stores the following metadata:

- `_stacker_guid`: Unique identifier from the feed
- `_stacker_canonical_url`: Original article URL
- `_stacker_tracking_pixel`: Tracking pixel HTML (if present)

### Image Restrictions

Images imported from Stacker feeds are marked with:

- `_stacker_restricted`: Flag indicating restricted usage

The plugin enforces that these images can only be displayed within Stacker content posts.

### Canonical URLs

The plugin automatically adds canonical link tags to the `<head>` section of single Stacker content pages:

```html
<link rel="canonical" href="https://stacker.com/original-article-url" />
```

### Tracking Pixels

Tracking pixels are:
- Automatically detected during import
- Stored as post metadata
- Appended to the content when displayed
- Preserved in their original format

## Feed Format Support

The plugin supports both RSS 2.0 and Atom feed formats, including:

- RSS 2.0 with content:encoded namespace
- Atom feeds with content and summary elements
- Custom namespaces for extended metadata

## Hooks and Filters

### Actions

- `stacker_import_cron`: Triggered during automatic imports
- Custom actions can be added before/after import (future versions)

### Filters

- `the_content`: Modified to add tracking pixels
- Custom filters can be added for content processing (future versions)

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- SimpleXML extension enabled

## Frequently Asked Questions

### How do I change the feed URL?

Go to **Stacker Content → Import Settings** and update the Feed URL field.

### Can I import from multiple feeds?

Currently, the plugin supports one feed at a time. This may be extended in future versions.

### What happens if an article already exists?

The plugin checks the GUID (unique identifier) of each article and skips articles that have already been imported.

### How are images handled?

Images are downloaded to your WordPress media library and restricted to use only within Stacker content. The plugin replaces external image URLs with local URLs.

### Can I customize the import frequency?

Yes, you can choose from hourly, twice daily, or daily imports in the settings page.

### Will this affect my site's performance?

The plugin uses WordPress's built-in cron system for scheduled imports, which runs during normal site operation. Image downloads happen during import and are processed efficiently.

## Troubleshooting

### Import fails with "Failed to parse XML feed"

- Verify the feed URL is correct and accessible
- Check that the feed returns valid XML
- Ensure your server can make outbound HTTP requests

### Images not displaying

- Check that the WordPress uploads directory is writable
- Verify that allow_url_fopen is enabled in PHP
- Check for any firewall rules blocking image downloads

### Canonical URLs not appearing

- Ensure you're viewing a single Stacker content post
- Check your theme's header.php file isn't overriding wp_head()
- Clear any caching plugins

## Changelog

### 1.0.0
- Initial release
- RSS and Atom feed support
- Custom post type for Stacker content
- Canonical URL management
- Tracking pixel preservation
- Image restriction functionality
- Automatic and manual import
- Admin settings page

## License

GPL v2 or later

## Support

For issues, questions, or feature requests, please create an issue in the plugin repository.

## Credits

Developed for importing and managing Stacker content in WordPress.
