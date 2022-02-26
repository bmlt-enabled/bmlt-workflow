<?php

if (is_admin()) {
   
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
}

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

    public function get_columns()
    {
        $columns = array(
            'id'          => 'ID',
            'submitter_name'       => 'Submitter Name',
            'submitter_email' => 'Submitter Email',
            'submission_type'        => 'Change Type',
            'submission_date_time'    => 'Submission Time',
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

    private function table_data()
    {
        $data = array();

        $data[] = array(
            'id'          => 1,
            'submitter_name'       => 'First1 Last1',
            'submitter_email' => 'email1@mail1.com.',
            'submission_type'        => 'update',
            'submission_date_time'    => '26 Feb 2022, 10:34am'
        );

        $data[] = array(
            'id'          => 2,
            'submitter_name'       => 'First2 Last2',
            'submitter_email' => 'email2@mail2.com.',
            'submission_type'        => 'update',
            'submission_date_time'    => '26 Feb 2022, 10:34am'
        );

        $data[] = array(
            'id'          => 3,
            'submitter_name'       => 'First3 Last3',
            'submitter_email' => 'email3@mail3.com.',
            'submission_type'        => 'update',
            'submission_date_time'    => '26 Feb 2022, 10:34am'
        );

        $data[] = array(
            'id'          => 4,
            'submitter_name'       => 'First4 Last4',
            'submitter_email' => 'email4@mail4.com.',
            'submission_type'        => 'update',
            'submission_date_time'    => '26 Feb 2022, 10:34am'
        );

        $data[] = array(
            'id'          => 5,
            'submitter_name'       => 'First5 Last5',
            'submitter_email' => 'email5@mail5.com.',
            'submission_type'        => 'update',
            'submission_date_time'    => '26 Feb 2022, 10:34am'
        );

        $data[] = array(
            'id'          => 6,
            'submitter_name'       => 'First6 Last6',
            'submitter_email' => 'email6@mail6.com.',
            'submission_type'        => 'update',
            'submission_date_time'    => '26 Feb 2022, 10:34am'
        );


        return $data;
    }

    public function column_id(  $item ) {
        $edit_link = admin_url( 'post.php?action=edit&amp;post=' .  $item->id  );
        $view_link = get_permalink( $item->id ); 
        $output    = '';
 
        // Title.
        $output .= '<strong><a href="' . esc_url( $edit_link ) . '" class="row-title">' . esc_html(  $item->id   ) . '</a></strong>';
 
        // Get actions.
        $actions = array(
            'edit'   => '<a target="_blank" href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'my_plugin' ) . '</a>',
            'view'   => '<a target="_blank" href="' . esc_url( $view_link ) . '">' . esc_html__( 'View', 'my_plugin' ) . '</a>',
        );
 
        $row_actions = array();
 
        foreach ( $actions as $action => $link ) {
            $row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
        }
 
        $output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';
 
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
                return $item[$column_name];

            default:
                return print_r($item, true);
        }
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