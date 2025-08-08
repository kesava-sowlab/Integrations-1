<?php

function igm_fetch_circle_communities() {
    $token = get_option('igm_circle_api_token_v1');
    if (!$token) return [];

    $response = wp_remote_get('https://app.circle.so/api/v1/communities', [
        'headers' => ['Authorization' => 'Token ' . $token],
    ]);

    if (is_wp_error($response)) return [];

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!is_array($data) || (isset($data['status']) && $data['status'] === 'unauthorized')) {
        return [];
    }

    $communities = [];
    foreach ($data as $community) {
        if (is_array($community) && isset($community['id'], $community['name'])) {
            $communities[$community['id']] = $community['name'];
        }
    }

    return $communities;
}