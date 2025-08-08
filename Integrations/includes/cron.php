<?php
defined('ABSPATH') || exit;


// Cron hooks
add_action('igm_cron_update_course_names', 'igm_check_and_update_course_names');
add_action('igm_cron_delete_removed_courses', 'igm_delete_space_groups_for_removed_courses');
add_action('igm_daily_teachable_sync', function () {
    igm_check_and_update_course_names();
    igm_delete_space_groups_for_removed_courses();
});

// Custom cron schedule
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_minute'] = [
        'interval' => 60,
        'display'  => __('Every Minute'),
    ];
    return $schedules;
});

// Deactivation cleanup
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('igm_daily_teachable_sync');
    wp_clear_scheduled_hook('igm_cron_update_course_names');
    wp_clear_scheduled_hook('igm_cron_delete_removed_courses');
});
