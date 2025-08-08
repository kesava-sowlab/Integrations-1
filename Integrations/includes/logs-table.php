<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class IGM_Logs_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'Log',
            'plural'   => 'Logs',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'action'     => 'Action',
            'message'    => 'Message',
            'created_at' => 'Time',
        ];
    }

    public function get_sortable_columns() {
        return [
            'created_at' => ['created_at', true],
        ];
    }

    public function column_default($item, $column_name) {
        return esc_html($item[$column_name]);
    }

    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'teachable_circle_logs';

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page     = $this->get_items_per_page('logs_per_page', 25);
        $current_page = $this->get_pagenum();
        $offset       = ($current_page - 1) * $per_page;

        $orderby = isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $sortable) ? $_GET['orderby'] : 'created_at';
        $order   = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

        $query = $wpdb->prepare("SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset);
        $this->items = $wpdb->get_results($query, ARRAY_A);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }
}
