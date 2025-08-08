<?php
defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
    register_rest_route('teachable/v1', '/enrollment', [
        'methods' => 'POST',
        'callback' => 'handle_teachable_enrollment',
        'permission_callback' => '__return_true',
    ]);
});

function handle_teachable_enrollment($request) {
    $body = $request->get_json_params();

    if (
        !is_array($body) ||
        empty($body['object']['course']['id']) ||
        empty($body['object']['course']['name']) ||
        empty($body['object']['user']['email'])
    ) {
        return new WP_REST_Response(['error' => 'Invalid payload structure'], 400);
    }

    $course_id = sanitize_text_field($body['object']['course']['id']);
    $course_name = sanitize_text_field($body['object']['course']['name']);
    $user_name = sanitize_text_field($body['object']['user']['name']);
    $slug = sanitize_title($course_name);
    $email = sanitize_email($body['object']['user']['email']);

    $stored_space_group_data = igm_get_circle_space_group_by_course_id($course_id);
    $space_group_id = $stored_space_group_data->space_group_id ?? null;

    $community_id    = get_option('igm_circle_community_id');
    $circle_token_v1    = get_option('igm_circle_api_token_v1');
    $circle_token_v2    = get_option('igm_circle_api_token_v2');

    if (empty($community_id)) {
        return;
    }
    if (empty($circle_token_v1)) {
        return;
    }

    if (empty($circle_token_v2)) {
        return;
    }
    // Step 1: Create space if not exists
    if (!$space_group_id) {
        $url = 'https://app.circle.so/api/v1/space_groups?community_id=' . $community_id;

        $circle_response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Token ' . $circle_token_v1,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'name' => $course_name,
                'slug' => $slug,
                'add_members_to_space_group_on_space_join' => false,
                'allow_members_to_create_spaces' => false,
                'automatically_add_members_to_new_spaces' => false,
                'hide_non_member_spaces_from_sidebar' => true,
                'is_hidden_from_non_members' => true,
                'space_order_array' => [],
                'position' => 1
            ]),
        ]);

        if (is_wp_error($circle_response)) {
            return new WP_REST_Response(['error' => 'Circle API request failed'], 500);
        }

        $response_body = json_decode(wp_remote_retrieve_body($circle_response), true);

        $space_group_id = $response_body['space_group']['id'] ?? null;

        if ($space_group_id) {
            igm_log_action('space_group_created', "Created Circle space group {$space_group_id} for course {$course_id} ({$course_name})");
            igm_save_circle_space_group($course_id, $space_group_id, $course_name, $slug);
        } else {
            return new WP_REST_Response(['error' => 'Space group creation failed'], 500);
        }
    }

    // Step 2: Invite user to community and assign space group
    $query_params = http_build_query([
    'email' => $email,
    'community_id' => $community_id,
    'space_group_id' => $space_group_id,
    ]);
    $url = 'https://app.circle.so/api/v1/space_group_members' .
       '?email=' . $email .
       '&space_group_id=' . $space_group_id .
       '&community_id=' . $community_id;
       

        $invite_response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Token ' . $circle_token_v1,
            'Content-Type'  => 'application/json',
        ],
    ]);


    $response_code = wp_remote_retrieve_response_code($invite_response);
    $response_body = wp_remote_retrieve_body($invite_response);

    if (is_wp_error($invite_response) || $response_code >= 400) {
        $error_message = is_wp_error($invite_response) ? $invite_response->get_error_message() : $response_body;
        return new WP_REST_Response(['error' => 'Failed to invite user'], 500);
    }
    igm_log_action('user_invited', "Invited {$email} to Circle space group{$space_group_id}");

    return new WP_REST_Response([
        'message' => 'User invited successfully',
        'space_group_id' => $space_group_id
    ], 200);
}