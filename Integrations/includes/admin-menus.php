<?php
add_action('admin_menu', function () {
    add_menu_page('Teachable Circle Mappings', 'Integrations', 'manage_options', 'teachable-circle-mappings', 'igm_render_mappings_page', 'dashicons-groups', 25);
    add_submenu_page('teachable-circle-mappings', 'Home', 'Home', 'manage_options', 'teachable-circle-mappings', 'igm_render_mappings_page');
    add_submenu_page('teachable-circle-mappings', 'I Got Mind Settings', 'Settings', 'manage_options', 'teachable-circle-settings', 'igm_render_settings_page');
});
