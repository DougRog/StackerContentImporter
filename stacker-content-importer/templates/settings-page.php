<?php
/**
 * Admin Settings Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$feed_url = get_option('stacker_feed_url', '');
$import_frequency = get_option('stacker_import_frequency', 'hourly');
$last_import = get_option('stacker_last_import', '');
?>

<div class="wrap stacker-importer-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="stacker-admin-container">
        <div class="stacker-main-content">
            <form method="post" action="options.php">
                <?php settings_fields('stacker_importer_settings'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="stacker_feed_url"><?php _e('Feed URL', 'stacker-importer'); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="stacker_feed_url"
                                   name="stacker_feed_url"
                                   value="<?php echo esc_attr($feed_url); ?>"
                                   class="regular-text"
                                   required>
                            <p class="description">
                                <?php _e('Enter the Stacker feed URL to import content from.', 'stacker-importer'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="stacker_import_frequency"><?php _e('Import Frequency', 'stacker-importer'); ?></label>
                        </th>
                        <td>
                            <select id="stacker_import_frequency" name="stacker_import_frequency">
                                <option value="hourly" <?php selected($import_frequency, 'hourly'); ?>>
                                    <?php _e('Hourly', 'stacker-importer'); ?>
                                </option>
                                <option value="twicedaily" <?php selected($import_frequency, 'twicedaily'); ?>>
                                    <?php _e('Twice Daily', 'stacker-importer'); ?>
                                </option>
                                <option value="daily" <?php selected($import_frequency, 'daily'); ?>>
                                    <?php _e('Daily', 'stacker-importer'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('How often should the plugin automatically import new content?', 'stacker-importer'); ?>
                            </p>
                        </td>
                    </tr>

                    <?php if ($last_import): ?>
                    <tr>
                        <th scope="row">
                            <?php _e('Last Import', 'stacker-importer'); ?>
                        </th>
                        <td>
                            <p><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_import))); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php submit_button(__('Save Settings', 'stacker-importer')); ?>
            </form>

            <hr>

            <h2><?php _e('Manual Import', 'stacker-importer'); ?></h2>
            <p><?php _e('Click the button below to manually import content from the Stacker feed.', 'stacker-importer'); ?></p>

            <form method="post">
                <?php wp_nonce_field('stacker_manual_import'); ?>
                <p>
                    <button type="submit" name="stacker_manual_import" class="button button-primary button-large">
                        <?php _e('Import Now', 'stacker-importer'); ?>
                    </button>
                </p>
            </form>
        </div>

        <div class="stacker-sidebar">
            <div class="stacker-info-box">
                <h3><?php _e('About Stacker Content', 'stacker-importer'); ?></h3>
                <p><?php _e('This plugin imports content from Stacker feeds and manages it separately from your regular posts.', 'stacker-importer'); ?></p>

                <h4><?php _e('Features:', 'stacker-importer'); ?></h4>
                <ul>
                    <li><?php _e('Separate custom post type for Stacker content', 'stacker-importer'); ?></li>
                    <li><?php _e('Automatic canonical URL insertion', 'stacker-importer'); ?></li>
                    <li><?php _e('Tracking pixel preservation', 'stacker-importer'); ?></li>
                    <li><?php _e('Restricted image usage (Stacker content only)', 'stacker-importer'); ?></li>
                    <li><?php _e('Automatic scheduled imports', 'stacker-importer'); ?></li>
                </ul>
            </div>

            <div class="stacker-info-box">
                <h3><?php _e('Statistics', 'stacker-importer'); ?></h3>
                <?php
                $total_articles = wp_count_posts('stacker_content');
                $published = isset($total_articles->publish) ? $total_articles->publish : 0;
                ?>
                <p>
                    <strong><?php _e('Total Articles:', 'stacker-importer'); ?></strong>
                    <?php echo esc_html($published); ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=stacker_content'); ?>" class="button">
                        <?php _e('View All Articles', 'stacker-importer'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
