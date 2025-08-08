<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class IGM_Mapping_List_Table extends WP_List_Table {
    private $data;

    public function __construct() {
        parent::__construct([
            'singular' => 'Mapping',
            'plural'   => 'Mappings',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'course_id'   => 'Teachable Course ID',
            'space_group_id'    => 'Circle Space Group ID',
            'course_name' => 'Course Name',
            'created_at'  => 'Created',
        ];
    }

    public function get_sortable_columns() {
        return [
            'course_id'   => ['course_id', false],
            'course_name' => ['course_name', false],
            'space_group_id'    => ['space_group_id', false],
            'created_at'  => ['created_at', true], // ✅ default sort
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'teachable_circle_mapping';

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page     = $this->get_items_per_page('mappings_per_page', 25);
        $current_page = $this->get_pagenum();
        $offset       = ($current_page - 1) * $per_page;

        // Get orderby/order safely
        $orderby = $_GET['orderby'] ?? 'created_at';
        $order   = strtolower($_GET['order'] ?? 'desc');

        // Validate and sanitize
        $allowed_orderby = ['course_id', 'course_name', 'space_group_id', 'created_at'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'created_at';
        }
        $order = ($order === 'asc') ? 'ASC' : 'DESC';

        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

        $query = $wpdb->prepare("SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset);
        $this->items = $wpdb->get_results($query, ARRAY_A);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    public function column_default($item, $column_name) {
        return esc_html($item[$column_name]);
    }
}
