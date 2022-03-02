
<?php
if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

_display_bmaw_admin_submissions_page();

/**
 *
 * As Charlie suggested in its plugin https://github.com/Askelon/Custom-AJAX-List-Table-Example
 * it's better to set the error_reporting in order to hiding notices to avoid AJAX errors
 *
 */

error_reporting( ~E_NOTICE );

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
		$request  = new WP_REST_Request( 'GET', '/wp-json/v2/posts' );
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

function _display_bmaw_admin_submissions_page() {

    $exampleListTable = new bmaw_meeting_submissions_page();

    error_log("created new bmaw_meeting_submissions_page");
    $exampleListTable->prepare_items();
    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"></div>
        <h2>Meeting Submissions</h2>
?>

	<div class="wrap">

		<h2>WP List Table Ajax Sample</h2>

		<form id="email-sent-list" method="get">

			<div id="ts-history-table" style="">
				<?php
				wp_nonce_field( 'wp_rest', '_wpnonce' );
				?>
                        <?php $exampleListTable->display(); ?>

			</div>

		</form>

	</div>

<?php

}

/**
 * fetch_ts_script function based from Charlie's original function
 */

function fetch_ts_script() {
	$screen = get_current_screen();
    $myajaxurl = "http://54.153.167.239/flop/wp-json/bmaw-submission/v1/submission";

	/**
	 * For testing purpose, finding Screen ID
	 */

	?>

	<script type="text/javascript">console.log("<?php echo $screen->id; ?>")</script>

	<?php

	if ( $screen->id != "bmaw_page_bmaw-submissions" )
		return;

	?>

	<script type="text/javascript">
		(function ($) {
            var myajaxurl="<?php echo $myajaxurl; ?>"

			list = {

				/** added method display
				 * for getting first sets of data
				 **/

				display: function() {

					$.ajax({

						url: myajaxurl,
						dataType: 'json',
						data: {
							_wpnonce: $('#_wpnonce').val()
						},
						success: function (response) {

							$("#ts-history-table").html(response.display);

							$("tbody").on("click", ".toggle-row", function(e) {
								e.preventDefault();
								$(this).closest("tr").toggleClass("is-expanded")
							});

							list.init();
						}
					});

				},

				init: function () {

					var timer;
					var delay = 500;

					$('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function (e) {
						e.preventDefault();
						var query = this.search.substring(1);

						var data = {
							paged: list.__query( query, 'paged' ) || '1',
							order: list.__query( query, 'order' ) || 'asc',
							orderby: list.__query( query, 'orderby' ) || 'title'
						};
						list.update(data);
					});

					$('input[name=paged]').on('keyup', function (e) {

						if (13 == e.which)
							e.preventDefault();

						var data = {
							paged: parseInt($('input[name=paged]').val()) || '1',
							order: $('input[name=order]').val() || 'asc',
							orderby: $('input[name=orderby]').val() || 'title'
						};

						window.clearTimeout(timer);
						timer = window.setTimeout(function () {
							list.update(data);
						}, delay);
					});

					$('#email-sent-list').on('submit', function(e){

						e.preventDefault();

					});

				},

				/** AJAX call
				 *
				 * Send the call and replace table parts with updated version!
				 *
				 * @param    object    data The data to pass through AJAX
				 */
				update: function (data) {

					$.ajax({
                        dataType: 'json',
						url: myajaxurl,
						data: $.extend(
							{
								_wpnonce: $('#_wpnonce').val()
							},
							data
						),
						success: function (response) {

							var response = $.parseJSON(response);

							if (response.rows.length)
								$('#the-list').html(response.rows);
							if (response.column_headers.length)
								$('thead tr, tfoot tr').html(response.column_headers);
							if (response.pagination.bottom.length)
								$('.tablenav.top .tablenav-pages').html($(response.pagination.top).html());
							if (response.pagination.top.length)
								$('.tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());

							list.init();
						}
					});
				},

				/**
				 * Filter the URL Query to extract variables
				 *
				 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
				 *
				 * @param    string    query The URL query part containing the variables
				 * @param    string    variable Name of the variable we want to get
				 *
				 * @return   string|boolean The variable value if available, false else.
				 */
				__query: function (query, variable) {

					var vars = query.split("&");
					for (var i = 0; i < vars.length; i++) {
						var pair = vars[i].split("=");
						if (pair[0] == variable)
							return pair[1];
					}
					return false;
				},
			}

			list.display();

		})(jQuery);

	</script>
	<?php
}

add_action('admin_footer', 'fetch_ts_script');