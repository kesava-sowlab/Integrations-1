<?php
defined('ABSPATH') || exit;

// Get group by course ID
function igm_get_circle_space_group_by_course_id($course_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'teachable_circle_mapping';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE course_id = %s", $course_id));
}

// Save mapping
function igm_save_circle_space_group($course_id, $space_group_id, $course_name, $slug) {
    global $wpdb;
    $table = $wpdb->prefix . 'teachable_circle_mapping';

    $existing = igm_get_circle_space_group_by_course_id($course_id);
    if ($existing) {
        $wpdb->update($table, [
            'space_group_id' => $space_group_id,
            'slug' => $slug,
            'course_name' => $course_name
        ], ['course_id' => $course_id]);
    } else {
        $wpdb->insert($table, [
            'course_id' => $course_id,
            'space_group_id' => $space_group_id,
            'slug' => $slug,
            'course_name' => $course_name
        ]);
    }
}

// Logging
function igm_log_action($action, $message) {
    global $wpdb;
    $table = $wpdb->prefix . 'teachable_circle_logs';
    $wpdb->insert($table, [
        'action' => $action,
        'message' => maybe_serialize($message),
        'created_at' => current_time('mysql')
    ]);
}
function igm_check_and_update_course_names() {

        $update_interval = get_option('igm_update_cron_interval', 'daily');
    global $wpdb;
    $table = $wpdb->prefix . 'teachable_circle_mapping';
    $teachable_key   = get_option('igm_teachable_api_key');
    $circle_token_v1    = get_option('igm_circle_api_token_v1');

    if (empty($teachable_key)) {
    return;
    }
    
    if (empty($circle_token_v1)) {
        return;
    }
    
        $response = wp_remote_get('https://developers.teachable.com/v1/courses', [
        'headers' => ['apiKey' => $teachable_key],
    ]);
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code === 401) {
        return;
    } elseif ($status_code === 404) {
        return;
    } elseif ($status_code !== 200) {
        return;
    }elseif ($status_code === 200){
    
        if (is_wp_error($response)) {
            return;
        }
    
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $courses = $data['courses'] ?? [];
    
        foreach ($courses as $course) {
            $course_id = sanitize_text_field($course['id']);
            $current_name = sanitize_text_field($course['name']);
            $current_slug = sanitize_title($current_name);
        
            $stored = igm_get_circle_space_group_by_course_id($course_id);
            if (!$stored || !isset($stored->space_group_id)) continue;

            if ($stored->course_name !== $current_name) {
                $update = wp_remote_request("https://app.circle.so/api/v1/space_groups/{$stored->space_group_id}", [
                    'method'  => 'PUT',
                    'headers' => [
                        'Authorization' => 'Token ' . $circle_token_v1,
                        'Content-Type'  => 'application/json',
                    ],
                    'body' => json_encode([
                        'name' => $current_name,
                        'slug' => $current_slug
                    ]),
                ]);
            
                $update_code = wp_remote_retrieve_response_code($update);
                if ($update_code === 200 || $update_code === 201) {
                igm_log_action('space_group_updated', "Updated Circle space group {$stored->space_group_id} to '{$current_name}'");
                
                    igm_save_circle_space_group($course_id, $stored->space_group_id, $current_name, $current_slug);
                }
            }
        }
    }
}
function igm_delete_space_groups_for_removed_courses() {

    global $wpdb;
    $table = $wpdb->prefix . 'teachable_circle_mapping';
    $teachable_key   = get_option('igm_teachable_api_key');
    $circle_token_v1    = get_option('igm_circle_api_token_v1');
    $community_id = get_option('igm_circle_community_id');

    
    if (empty($teachable_key)) {
    return;
    }
    
    if (empty($circle_token_v1)) {
        return;
    }
    
        $response = wp_remote_get('https://developers.teachable.com/v1/courses', [
            'headers' => ['apiKey' => $teachable_key],
        ]);
    
        if (is_wp_error($response)) {
            return;
        }
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code === 401) {
        return;
    } elseif ($status_code === 404) {
        return;
    } elseif ($status_code !== 200) {
        return;
    }elseif ($status_code === 200){
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $existing_ids = array_map(fn($course) => (string)$course['id'], $data['courses'] ?? []);
    
        $mappings = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
    
        foreach ($mappings as $row) {
            if (!in_array((string)$row['course_id'], $existing_ids)) {
                $space_group_id = $row['space_group_id'];
                $delete_response = wp_remote_request("https://app.circle.so/api/v1/space_groups/{$space_group_id}?community_id={$community_id}", [
                    'method'  => 'DELETE',
                    'headers' => [
                        'Authorization' => 'Token ' . $circle_token_v1,
                        'Content-Type'  => 'application/json',
                    ],
                ]);
            
                $code = wp_remote_retrieve_response_code($delete_response);
                if ($code === 204 || $code === 200) {
                    igm_log_action('space_group_deleted', "Deleted Circle space group{$space_group_id} for removed course {$row['course_id']}");
                    $wpdb->delete($table, ['course_id' => $row['course_id']]);
                }
            }
        }
    }
}