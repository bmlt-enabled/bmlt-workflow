<?php

if (!class_exists('BMLTIntegration')) {
	require_once(BMAW_PLUGIN_DIR . 'admin/bmlt_integration.php');
}

function bmaw_submissions_controller()
{
	$controller = new bmaw_submissions_rest();
	$controller->register_routes();
}


class bmaw_submissions_rest extends WP_REST_Controller
{

	protected $namespace;
	protected $rest_base;

	public function __construct()
	{

		$this->namespace = 'bmaw-submission/v1';
		$this->rest_base = 'submissions';
		$this->bmlt_integration = new BMLTIntegration;
	}

	public function register_routes()
	{

		// submissions/
		register_rest_route($this->namespace, '/' . $this->rest_base, array(

			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_submissions'),
				'permission_callback' => array($this, 'get_submissions_permissions_check'),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array($this, 'post_submissions'),
				'permission_callback' => array($this, 'post_submissions_permissions_check'),
				'args'            => $this->get_endpoint_args_for_item_schema(false),
			),
			'schema' => null,

		));
		// submissions/<id>
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'get_submission'),
			'permission_callback' => array($this, 'get_submissions_permissions_check'),
		));
		// submissions/<id>/approve
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/approve', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'approve_submission'),
			'permission_callback' => array($this, 'post_submissions_action_permissions_check'),
		));
		// submissions/<id>/reject
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reject', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'reject_submission'),
			'permission_callback' => array($this, 'post_submissions_action_permissions_check'),
		));
	}
	/**
	 * Check permissions for submission management. These are general purpose checks for all submission editors, granular edit permission will be checked within the callback itself.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_submissions_permissions_check($request)
	{
		if (!current_user_can('manage_options')) {
			return new WP_Error('rest_forbidden', esc_html__('You cannot view the submissions resource.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function post_submissions_action_permissions_check($request)
	{
		if (!current_user_can('manage_options')) {
			return new WP_Error('rest_forbidden', esc_html__('You cannot manage the submissions resource.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	/**
	 * Check permissions for the update
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return bool|WP_Error
	 */
	public function post_submissions_permissions_check($request)
	{
		// Anyone can post a form submission
		return true;
	}
	/**
	 * Grabs all the submission list.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return mixed|WP_REST_Response
	 */

	public function get_submissions($request)
	{

		global $wpdb;
		global $bmaw_submissions_table_name;

		$result = $wpdb->get_results('SELECT * FROM ' . $bmaw_submissions_table_name, ARRAY_A);

		// Return all of our comment response data.
		return rest_ensure_response($result);
	}

	/**
	 * Returns a single submission
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return mixed|WP_REST_Response
	 */

	public function get_submission($request)
	{

		global $wpdb;
		global $bmaw_submissions_table_name;
		$sql = $wpdb->prepare('SELECT * FROM ' . $bmaw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
		$result = $wpdb->get_results($sql, ARRAY_A);

		// Return all of our comment response data.
		return rest_ensure_response($result);
	}

	private function vdump($object)
	{
		ob_start();
		var_dump($object);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	/**
	 * Approve a single submission
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return mixed|WP_REST_Response
	 */

	public function approve_submission($request)
	{
		$change_id = $request->get_param('id');

		error_log("getting changes for id ".$change_id);

		global $wpdb;
		global $bmaw_submissions_table_name;
		$sql = $wpdb->prepare('SELECT changes_requested FROM ' . $bmaw_submissions_table_name . ' where id="%d" limit 1', $request['id']);
		$result = $wpdb->get_results($sql, ARRAY_A);
		if ($result)
		{
			error_log(vdump($result));
		}
		else
		{
			error_log("no result found");
		}
		$change = unserialize($result[0]['changes_requested']);
		error_log("deserialised");
		error_log(vdump($change));

		// // bmlt_ajax_callback=1&do_meeting_search=1&sort_key=time&simple_other_fields=1&services[]=1&advanced_published=0&salt=1646289683445
		// $postargs = array(
		// 	'bmlt_ajax_callback' => 1,
		// 	'do_meeting_search' => 1,
		// 	'meeting_key' => 'id_bigint',
		// 	'meeting_key_value' => $request['id'],
		// );
		// $response = $this->bmlt_integration->postConfiguredRootServerRequest('', $postargs);

		return "{'response':'approved'}";

		$response = $this->bmlt_integration->postConfiguredRootServerRequest('local_server/server_admin/json.php', array('modify_meeting'=>'get_format_info'));
		if( is_wp_error( $response ) ) {
			wp_die("BMLT Configuration Error - Unable to retrieve meeting formats");
		}    

		return "{'response':'approved'}";
	}

	/**
	 * Form post
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */

	public function post_submissions($request)
	{
		error_log($this->vdump($request->get_body_params()));

		$resp = meeting_update_form_handler_rest($request->get_body_params());

		return rest_ensure_response($resp);
	}

	/**
	 * Sets up the proper HTTP status code for authorization.
	 *
	 * @return int
	 */
	public function authorization_status_code()
	{

		$status = 401;

		if (is_user_logged_in()) {
			$status = 403;
		}

		return $status;
	}
}
