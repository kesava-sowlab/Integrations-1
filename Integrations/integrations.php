<?php
/*
Plugin Name: Integrations
Description: Seamlessly manage the integration between Teachable and Circle to create course-based space groups. Full support for automated member syncing and real-time updates — all within your WordPress dashboard.
*/

defined('ABSPATH') || exit;
// === Load Core Files ===
foreach ([
    'teachable-hooks.php',
    'api-handler.php',
    'admin-menus.php',
    'cron.php',
    'functions.php',
    'mappings-page.php',
    'settings-page.php',
    'logs-page.php',
    'list-table.php',
] as $file) {
    require_once plugin_dir_path(__FILE__) . 'includes/' . $file;
}


register_activation_hook(__FILE__, function () {

    if (!wp_next_scheduled('igm_daily_sync')) {

        wp_schedule_event(time(), 'daily', 'igm_daily_sync');
    }

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    // Table 1: teachable_circle_mapping
    $table1 = $wpdb->prefix . 'teachable_circle_mapping';
    $sql1 = "CREATE TABLE IF NOT EXISTS $table1 (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        course_id BIGINT UNSIGNED NOT NULL UNIQUE,
        course_name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        space_group_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";
    dbDelta($sql1);

    // Table 2: teachable_circle_logs
    $table_log = $wpdb->prefix . 'teachable_circle_logs';
    $log_sql = "CREATE TABLE IF NOT EXISTS $table_log (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(255) NOT NULL,
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    dbDelta($log_sql);

    // Schedule other crons
    $update_interval = get_option('igm_update_cron_interval', 'daily');
    $delete_interval = get_option('igm_delete_cron_interval', 'daily');

    if (!wp_next_scheduled('igm_cron_update_course_names')) {
        wp_schedule_event(time(), $update_interval, 'igm_cron_update_course_names');
    }

    if (!wp_next_scheduled('igm_cron_delete_removed_courses')) {
        wp_schedule_event(time(), $delete_interval, 'igm_cron_delete_removed_courses');
    }
});
