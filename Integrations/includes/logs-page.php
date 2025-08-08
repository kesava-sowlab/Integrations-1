<?php
require_once plugin_dir_path(__FILE__) . 'logs-table.php';

function igm_render_logs_page() {
    echo '<div class="wrap"><h1 class="wp-heading-inline">Integration Logs</h1>';

    $table = new IGM_Logs_List_Table();
    $table->prepare_items();

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="teachable-circle-logs" />';
    $table->display();
    echo '</form>';
    echo '</div>';
}

add_filter('set-screen-option', function ($status, $option, $value) {
    if ($option === 'logs_per_page') return (int) $value;
    return $status;
}, 10, 3);

add_action('admin_menu', function () {
    $hook = add_submenu_page('teachable-circle-mappings', 'Logs', 'Logs', 'manage_options', 'teachable-circle-logs', 'igm_render_logs_page');

    add_action("load-$hook", function () {
        add_screen_option('per_page', [
            'label'   => 'Number of items per page: ',
            'default' => 25,
            'option'  => 'logs_per_page'
        ]);
    });
});