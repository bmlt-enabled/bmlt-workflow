<?php

if (!defined('ABSPATH')) exit; // die if being called directly

$submissionsListTable = new bmaw_meeting_submissions_page();

wp_nonce_field('wp_rest', '_wprestnonce');

?>
// Approve dialog
<div id="bmaw_submission_approve_dialog" class="hidden" style="max-width:800px">
  <label class='dialog_label' for="bmaw_submission_approve_dialog_textarea">Approval note:</label>
  <textarea class='dialog_textarea' id="bmaw_submission_approve_dialog_textarea" rows=5 cols=60 placeholder='Add a note to this approval for the submitter'></textarea>
<p>You can use the quickedit function to make any extra changes before approval.</p>
<p>Are you sure you would like to approve the submission?</p>
</div>

// Delete dialog
<div id="bmaw_submission_delete_dialog" class="hidden" style="max-width:800px">
  <p>This change cannot be undone. Use this to remove an entirely unwanted submission from the list.</p>
  <p>Are you sure you would like to delete the submission completely?</p>
</div>

// Reject dialog
<div id="bmaw_submission_reject_dialog" class="hidden" style="max-width:800px">
  <label class='dialog_label' for="bmaw_submission_reject_dialog_textarea">Rejection note:</label>
  <textarea class='dialog_textarea' id="bmaw_submission_reject_dialog_textarea" rows=5 cols=60 placeholder='Add a note to this rejection for the submitter'></textarea>
<p>Are you sure you would like to reject this submission?</p>
</div>

<div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <h2>Meeting Submissions</h2>
    <hr class="wp-header-end">
    <?php 
    $submissionsListTable->views();
    $submissionsListTable->prepare_items();
    $submissionsListTable->display(); 
    ?>
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
        error_log("TABLE OUTPUT");
        error_log(vdump($data));

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
            // 'submission_type'        => 'Change Type',
            'change_summary' => 'Change Summary',
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
        return array(
            'id' => array('id', 'num'),
            'submission_time' => array('submission_time', 'asc'),
            'submitter_name' => array('submitter_name', 'asc'),
            'submitter_email' => array('submitter_email', 'asc'),
    );
    }

    public function column_id($item)
    {
        $output    = '';
        // error_log(vdump($item));
        $row_actions = array();

        // Title.
        $output .= '<strong><a href="#" class="row-title">' . esc_html($item['id']) . '</a></strong>';

        $actions = array();
        // if($item['change_made'] != 'Approved')
        // {
            $actions['bmaw_span_approve'] = '<a class="bmaw_submission_approve" id="bmaw_submission_approve_id_'.$item['id'].'" target="_blank" href="#!">' . esc_html__('Approve') . '</a>';
        // }
        if($item['change_made'] != 'Rejected')
        {
            $actions['bmaw_span_reject'] = '<a class="bmaw_submission_reject" id="bmaw_submission_reject_id_'.$item['id'].'" target="_blank" href="#!">' . esc_html__('Reject') . '</a>';
        }
        // if($item['change_made'] = '')
        // {
            $actions['bmaw_span_quickedit'] = '<a class="bmaw_submission_quickedit" id="bmaw_submission_quickedit_id_'.$item['id'].'" target="_blank" href="#!">' . esc_html__('Quick Edit') . '</a>';
        // }
        $actions['bmaw_span_delete'] = '<a class="bmaw_submission_delete" id="bmaw_submission_delete_id_'.$item['id'].'" target="_blank" href="#!">' . esc_html__('Delete') . '</a>';

        $row_actions = array();

        foreach ($actions as $action => $link) {
            $row_actions[] = '<span class="' . esc_attr($action) . '">' . $link . '</span>';
        }
        // error_log(vdump($row_actions));
        $output .= '<div class="row-actions">' . implode(' | ', $row_actions) . '</div>';

        return $output;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
                // case 'id':
            case 'submitter_name':
            case 'submitter_email':
            // case 'submission_type':
            case 'submission_date_time':
            case 'change_summary':
            case 'change_time':
            case 'changed_by':
            case 'change_made':
            case 'submission_time':
                return $item[$column_name];

            default:
                return;
        }
    }

    private function vdump($object)
    {
        ob_start();
        var_dump($object);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    protected function get_views() { 
        $status_links = array(
            "all"       => __("<a href='#'>All</a>",'bmaw-submissions'),
            "not approved" => __("<a href='#'>Not Approved</a>",'bmaw-submissions')
            // add class='current'
            // add total post numbers

        );
        return $status_links;
    }

    private function table_data()
    {
        $request  = new WP_REST_Request('GET', '/bmaw-submission/v1/submissions');
        $response = rest_do_request($request);
        $result     = rest_get_server()->response_to_data($response, true);
        // format the changes requested
        foreach ($result as $key => $value)
        {
            $change = unserialize($value['changes_requested']);
            // error_log("deserialised");
            // error_log(vdump($change));
            $summary = '<b>Change Type: ' . $value['submission_type'] . '</b><br><br>';
            foreach ($change as $key2 => $value2)
            {
                // skip meeting_id as it is always required
                if($key2 != 'meeting_id')
                {
                    $summary .= $key2 . ' <span class="dashicons dashicons-arrow-right-alt"></span> <b>' . $value2 . '</b><br>' ;
                }
            }
            // chop trailing <br>
            $result[$key]['change_summary'] = substr($summary, 0, -4); ;
        }

        return $result;
    }

    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'id';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }


        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}
?>