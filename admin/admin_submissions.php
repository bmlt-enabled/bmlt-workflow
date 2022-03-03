<?php

if (!defined('ABSPATH')) exit; // die if being called directly

$exampleListTable = new bmaw_meeting_submissions_page();

error_log("created new bmaw_meeting_submissions_page");
$exampleListTable->prepare_items();
?>
<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Meeting Submissions</h2>
    <?php $exampleListTable->display(); ?>
</div>
<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class bmaw_meeting_submissions_page extends WP_List_Table
{
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort($data, array(&$this, 'sort_data'));

        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ));

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
    // id mediumint(9) NOT NULL AUTO_INCREMENT,
    // submission_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    // change_time datetime DEFAULT '0000-00-00 00:00:00',
    // changed_by varchar(10),
    // change_made varchar(10),
    // submitter_name tinytext NOT NULL,
    // submission_type tinytext NOT NULL,
    // submitter_email varchar(320) NOT NULL,

    public function get_columns()
    {
        $columns = array(
            'id'          => 'ID',
            'submitter_name'       => 'Submitter Name',
            'submitter_email' => 'Submitter Email',
            'submission_type'        => 'Change Type',
            'submission_time'    => 'Submission Time',
            'change_time' => 'Change Time',
            'changed_by' => 'Changed By',
            'change_made' => 'Change Made'
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        return array();
    }

    public function get_sortable_columns()
    {
        return array('submitter_name' => array('submitter_name', false));
    }

    public function column_id($item)
    {
        $edit_link = admin_url('post.php?action=edit&amp;post=' .  $item['id']);
        $view_link = get_permalink($item['id']);
        $output    = '';

        // Title.
        $output .= '<strong><a href="' . esc_url($edit_link) . '" class="row-title">' . esc_html($item['id']) . '</a></strong>';

        // Get actions.
        $actions = array(
            '1'   => '<a target="_blank" href="' . esc_url($edit_link) . '">' . esc_html__('Approve', 'my_plugin') . '</a>',
            '2'   => '<a target="_blank" href="' . esc_url($view_link) . '">' . esc_html__('Reject', 'my_plugin') . '</a>',
            '3'   => '<a target="_blank" href="' . esc_url($view_link) . '">' . esc_html__('View Detail', 'my_plugin') . '</a>',
        );

        $row_actions = array();

        foreach ($actions as $action => $link) {
            $row_actions[] = '<span class="' . esc_attr($action) . '">' . $link . '</span>';
        }

        $output .= '<div class="row-actions">' . implode(' | ', $row_actions) . '</div>';

        return $output;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
                // case 'id':
            case 'submitter_name':
            case 'submitter_email':
            case 'submission_type':
            case 'submission_date_time':
            case 'change_time':
            case 'changed_by':
            case 'change_made':
            case 'submission_time':
                return $item[$column_name];

            default:
                return print_r($item, true);
        }
    }

    private function table_data()
    {
        $request  = new WP_REST_Request( 'GET', '/bmaw-submission/v1/submissions/12' );
		$response = rest_do_request( $request );
		$result     = rest_get_server()->response_to_data( $response, true );
        var_dump($result);
        error_log(print_r($result));

		$request  = new WP_REST_Request( 'GET', '/bmaw-submission/v1/submissions' );
		$response = rest_do_request( $request );
		$result     = rest_get_server()->response_to_data( $response, true );
        return $result;
    }

    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'submitter_name';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }

        $result = strcmp($a[$orderby], $b[$orderby]);

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }
}
?>