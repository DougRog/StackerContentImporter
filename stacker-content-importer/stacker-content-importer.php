<?php
/**
 * Plugin Name: Stacker Content Importer
 * Plugin URI: https://github.com/yourusername/stacker-content-importer
 * Description: Imports content from Stacker feed and manages it as a separate content section with proper canonical URLs, tracking pixels, and image restrictions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stacker-importer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('STACKER_IMPORTER_VERSION', '1.0.0');
define('STACKER_IMPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STACKER_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Stacker Content Importer Class
 */
class Stacker_Content_Importer {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register custom post type
        add_action('init', array($this, 'register_stacker_post_type'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add canonical URL to head
        add_action('wp_head', array($this, 'add_canonical_url'), 1);

        // Add tracking pixel to content
        add_filter('the_content', array($this, 'add_tracking_pixel'));

        // Restrict image access
        add_action('template_redirect', array($this, 'restrict_image_access'));

        // Schedule automatic imports
        add_action('stacker_import_cron', array($this, 'run_import'));

        // Register AJAX handlers
        add_action('wp_ajax_stacker_manual_import', array($this, 'ajax_manual_import'));

        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Register custom post type for Stacker content
     */
    public function register_stacker_post_type() {
        $labels = array(
            'name'               => __('Stacker Content', 'stacker-importer'),
            'singular_name'      => __('Stacker Article', 'stacker-importer'),
            'menu_name'          => __('Stacker Content', 'stacker-importer'),
            'add_new'            => __('Add New', 'stacker-importer'),
            'add_new_item'       => __('Add New Article', 'stacker-importer'),
            'edit_item'          => __('Edit Article', 'stacker-importer'),
            'new_item'           => __('New Article', 'stacker-importer'),
            'view_item'          => __('View Article', 'stacker-importer'),
            'search_items'       => __('Search Articles', 'stacker-importer'),
            'not_found'          => __('No articles found', 'stacker-importer'),
            'not_found_in_trash' => __('No articles found in trash', 'stacker-importer'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'stacker-content'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-rss',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'        => true,
        );

        register_post_type('stacker_content', $args);

        // Register custom taxonomy for categories
        register_taxonomy('stacker_category', 'stacker_content', array(
            'label'        => __('Stacker Categories', 'stacker-importer'),
            'rewrite'      => array('slug' => 'stacker-category'),
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=stacker_content',
            __('Import Settings', 'stacker-importer'),
            __('Import Settings', 'stacker-importer'),
            'manage_options',
            'stacker-import-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('stacker_importer_settings', 'stacker_feed_url');
        register_setting('stacker_importer_settings', 'stacker_import_frequency');
        register_setting('stacker_importer_settings', 'stacker_tracking_pixel');
        register_setting('stacker_importer_settings', 'stacker_last_import');
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('stacker_content_page_stacker-import-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('stacker-importer-admin', STACKER_IMPORTER_PLUGIN_URL . 'assets/admin.css', array(), STACKER_IMPORTER_VERSION);
        wp_enqueue_script('stacker-importer-admin', STACKER_IMPORTER_PLUGIN_URL . 'assets/admin.js', array('jquery'), STACKER_IMPORTER_VERSION, true);

        wp_localize_script('stacker-importer-admin', 'stackerImporter', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('stacker_importer_nonce'),
        ));
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include STACKER_IMPORTER_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * AJAX handler for manual import
     */
    public function ajax_manual_import() {
        // Check nonce
        check_ajax_referer('stacker_importer_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'stacker-importer')
            ));
        }

        // Run import
        $result = $this->run_import();

        // Send JSON response
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully imported %d articles.', 'stacker-importer'), $result['imported']),
                'imported' => $result['imported']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }

    /**
     * Run import from Stacker feed
     */
    public function run_import() {
        $feed_url = get_option('stacker_feed_url', 'https://feeds.stacker.com/f19c06ca-2e45-468f-ba49-2699ee15307f/articles.xml?category=lifestyle,travel,entertainment,sports,money,science&limit=20');

        if (empty($feed_url)) {
            return array('success' => false, 'message' => __('Feed URL is not set.', 'stacker-importer'));
        }

        // Fetch feed with custom user agent
        $response = wp_remote_get($feed_url, array(
            'timeout'    => 30,
            'user-agent' => 'WordPress/Stacker Content Importer ' . STACKER_IMPORTER_VERSION,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            return array('success' => false, 'message' => __('Feed is empty.', 'stacker-importer'));
        }

        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return array('success' => false, 'message' => __('Failed to parse XML feed.', 'stacker-importer'));
        }

        $imported = 0;
        $namespaces = $xml->getNamespaces(true);

        // Handle both RSS and Atom feeds
        if (isset($xml->channel->item)) {
            // RSS feed
            foreach ($xml->channel->item as $item) {
                if ($this->import_article($item, $namespaces)) {
                    $imported++;
                }
            }
        } elseif (isset($xml->entry)) {
            // Atom feed
            foreach ($xml->entry as $entry) {
                if ($this->import_article($entry, $namespaces, 'atom')) {
                    $imported++;
                }
            }
        }

        update_option('stacker_last_import', current_time('mysql'));

        return array('success' => true, 'imported' => $imported);
    }

    /**
     * Import individual article
     */
    private function import_article($item, $namespaces, $feed_type = 'rss') {
        if ($feed_type === 'atom') {
            $title = (string) $item->title;
            $link = '';
            foreach ($item->link as $l) {
                if ((string) $l['rel'] === 'alternate' || empty($link)) {
                    $link = (string) $l['href'];
                }
            }
            $content = (string) $item->content;
            $summary = (string) $item->summary;
            $pub_date = (string) $item->published;
            $guid = (string) $item->id;
        } else {
            $title = (string) $item->title;
            $link = (string) $item->link;

            // Try different content namespaces
            $content = '';
            if (isset($namespaces['content'])) {
                $content_ns = $item->children($namespaces['content']);
                $content = (string) $content_ns->encoded;
            }

            if (empty($content)) {
                $content = (string) $item->description;
            }

            $summary = (string) $item->description;
            $pub_date = (string) $item->pubDate;
            $guid = (string) $item->guid;
        }

        if (empty($title) || empty($link)) {
            return false;
        }

        // Check if article already exists
        $existing = get_posts(array(
            'post_type'   => 'stacker_content',
            'meta_key'    => '_stacker_guid',
            'meta_value'  => $guid,
            'post_status' => 'any',
            'numberposts' => 1,
        ));

        if (!empty($existing)) {
            return false; // Already imported
        }

        // Parse publication date
        $post_date = date('Y-m-d H:i:s', strtotime($pub_date));

        // Download and process images
        $content = $this->process_images($content);

        // Extract tracking pixel if present
        $tracking_pixel = $this->extract_tracking_pixel($content);

        // Create post
        $post_data = array(
            'post_title'   => sanitize_text_field($title),
            'post_content' => wp_kses_post($content),
            'post_excerpt' => sanitize_text_field($summary),
            'post_status'  => 'publish',
            'post_type'    => 'stacker_content',
            'post_date'    => $post_date,
        );

        $post_id = wp_insert_post($post_data);

        if (!$post_id || is_wp_error($post_id)) {
            return false;
        }

        // Save metadata
        update_post_meta($post_id, '_stacker_guid', $guid);
        update_post_meta($post_id, '_stacker_canonical_url', esc_url_raw($link));

        if (!empty($tracking_pixel)) {
            update_post_meta($post_id, '_stacker_tracking_pixel', $tracking_pixel);
        }

        // Handle categories
        if ($feed_type === 'atom' && isset($item->category)) {
            $this->assign_categories($post_id, $item->category);
        } elseif (isset($item->category)) {
            $this->assign_categories($post_id, $item->category);
        }

        return true;
    }

    /**
     * Process images in content
     */
    private function process_images($content) {
        // Find all images in content
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

        if (empty($matches[1])) {
            return $content;
        }

        foreach ($matches[1] as $index => $image_url) {
            $attachment_id = $this->download_image($image_url);

            if ($attachment_id) {
                // Replace with local image
                $new_image_url = wp_get_attachment_url($attachment_id);
                $content = str_replace($image_url, $new_image_url, $content);

                // Mark image as Stacker-only
                update_post_meta($attachment_id, '_stacker_restricted', 1);
            }
        }

        return $content;
    }

    /**
     * Download image and create attachment
     */
    private function download_image($image_url) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name'     => basename($image_url),
            'tmp_name' => $tmp,
        );

        // Check for download errors
        if (is_wp_error($tmp)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        $id = media_handle_sideload($file_array, 0);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        return $id;
    }

    /**
     * Extract tracking pixel from content
     */
    private function extract_tracking_pixel($content) {
        // Look for tracking pixels (1x1 images, tracking scripts, etc.)
        $patterns = array(
            '/<img[^>]+width=["\']1["\'][^>]+height=["\']1["\'][^>]*>/i',
            '/<img[^>]+height=["\']1["\'][^>]+width=["\']1["\'][^>]*>/i',
            '/<script[^>]*>[^<]*tracking[^<]*<\/script>/i',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                return $matches[0];
            }
        }

        return '';
    }

    /**
     * Assign categories to post
     */
    private function assign_categories($post_id, $categories) {
        $term_ids = array();

        foreach ($categories as $category) {
            $cat_name = (string) $category;

            if (empty($cat_name)) {
                continue;
            }

            $term = term_exists($cat_name, 'stacker_category');

            if (!$term) {
                $term = wp_insert_term($cat_name, 'stacker_category');
            }

            if (!is_wp_error($term)) {
                $term_ids[] = (int) $term['term_id'];
            }
        }

        if (!empty($term_ids)) {
            wp_set_object_terms($post_id, $term_ids, 'stacker_category');
        }
    }

    /**
     * Add canonical URL to head
     */
    public function add_canonical_url() {
        if (!is_singular('stacker_content')) {
            return;
        }

        $canonical_url = get_post_meta(get_the_ID(), '_stacker_canonical_url', true);

        if (!empty($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        }
    }

    /**
     * Add tracking pixel to content
     */
    public function add_tracking_pixel($content) {
        if (!is_singular('stacker_content')) {
            return $content;
        }

        $tracking_pixel = get_post_meta(get_the_ID(), '_stacker_tracking_pixel', true);

        if (!empty($tracking_pixel)) {
            $content .= "\n" . $tracking_pixel;
        }

        return $content;
    }

    /**
     * Restrict image access to Stacker content only
     */
    public function restrict_image_access() {
        if (!is_attachment()) {
            return;
        }

        $attachment_id = get_the_ID();
        $is_restricted = get_post_meta($attachment_id, '_stacker_restricted', true);

        if (!$is_restricted) {
            return;
        }

        // Check if image is being accessed from a Stacker content post
        $referer = wp_get_referer();

        if (empty($referer)) {
            // If no referer, check if we're in a Stacker content context
            global $post;
            if (!$post || $post->post_type !== 'stacker_content') {
                wp_die(__('This image is restricted to Stacker content only.', 'stacker-importer'));
            }
        } else {
            // Check if referer is a Stacker content post
            $referer_post_id = url_to_postid($referer);
            if ($referer_post_id) {
                $referer_post = get_post($referer_post_id);
                if (!$referer_post || $referer_post->post_type !== 'stacker_content') {
                    wp_die(__('This image is restricted to Stacker content only.', 'stacker-importer'));
                }
            }
        }
    }

    /**
     * Activate plugin
     */
    public function activate() {
        // Register post type for flush_rewrite_rules
        $this->register_stacker_post_type();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        if (!get_option('stacker_feed_url')) {
            update_option('stacker_feed_url', 'https://feeds.stacker.com/f19c06ca-2e45-468f-ba49-2699ee15307f/articles.xml?category=lifestyle,travel,entertainment,sports,money,science&limit=20');
        }

        if (!get_option('stacker_import_frequency')) {
            update_option('stacker_import_frequency', 'hourly');
        }

        // Schedule cron job
        if (!wp_next_scheduled('stacker_import_cron')) {
            wp_schedule_event(time(), 'hourly', 'stacker_import_cron');
        }
    }

    /**
     * Deactivate plugin
     */
    public function deactivate() {
        // Clear scheduled cron
        wp_clear_scheduled_hook('stacker_import_cron');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
function stacker_content_importer() {
    return Stacker_Content_Importer::get_instance();
}

stacker_content_importer();
