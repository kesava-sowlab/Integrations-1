<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . '/list-table.php';        // Must be before mappings-page


/**
 * Renders the Mappings admin page
 */
function igm_render_mappings_page() {
    echo '<div class="wrap">
        <h1>Home</h1>';

    $table = new IGM_Mapping_List_Table();
    $table->prepare_items();

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="teachable-circle-mappings" />';
    $table->display();
    echo '</form>';
    echo '</div>';
}

// Allow saving screen option value for items per page
add_filter('set-screen-option', function($status, $option, $value) {
    return ($option === 'mappings_per_page') ? (int) $value : $status;
}, 10, 3);

// Register the screen option when viewing this page
add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_teachable-circle-mappings') {
        add_screen_option('per_page', [
            'label'   => 'Number of items per page: ',
            'default' => 25,
            'option'  => 'mappings_per_page',
        ]);
    }
});
