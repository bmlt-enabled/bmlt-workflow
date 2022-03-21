<?php

if (!defined('ABSPATH')) exit; // die if being called directly

if (!class_exists('BMLTIntegration')) {
	require_once(BMAW_PLUGIN_DIR . 'admin/bmlt_integration.php');
}

if (!class_exists('bmaw_submissions_rest_handlers')) {
	require_once(BMAW_PLUGIN_DIR . 'admin/admin_rest_handlers.php');
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
		$this->submissions_rest_base = 'submissions';
		$this->service_areas_rest_base = 'serviceareas';
		$this->bmlt_integration = new BMLTIntegration;
		$this->handlers = new bmaw_submissions_rest_handlers();
	}

	public function register_routes()
	{

		// submissions/
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base, array(

			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_submissions'),
				'permission_callback' => array($this, 'get_submissions_permissions_check'),
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array($this, 'post_submissions'),
				'permission_callback' => array($this, 'post_submissions_permissions_check'),
				'args'            => $this->get_endpoint_args_for_item_schema(false),
			),
			'schema' => null,

		));
		// GET submissions/<id>
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base . '/(?P<id>[\d]+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'get_submission'),
			'permission_callback' => array($this, 'get_submissions_permissions_check'),
		));
		// DELETE submissions/<id>
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base . '/(?P<id>[\d]+)', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array($this, 'delete_submission'),
			'permission_callback' => array($this, 'delete_submission_permissions_check'),
		));
		// POST submissions/<id>/approve
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base . '/(?P<id>[\d]+)/approve', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'approve_submission'),
			'permission_callback' => array($this, 'approve_submission_action_permissions_check'),
		));
		// POST submissions/<id>/reject
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base . '/(?P<id>[\d]+)/reject', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'reject_submission'),
			'permission_callback' => array($this, 'reject_submission_action_permissions_check'),
			'args'     => [
				'message' => [
					'required' => false,
					'type'     => 'string',
				],
			],
		));

		// GET serviceareas
		register_rest_route(
			$this->namespace,
			'/' . $this->service_areas_rest_base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_service_areas'),
				'permission_callback' => array($this, 'get_service_areas_permissions_check'),
			),
		);
		// GET serviceareas/detail
		register_rest_route(
			$this->namespace,
			'/' . $this->service_areas_rest_base . '/detail',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_service_areas_detail'),
				'permission_callback' => array($this, 'get_service_areas_detail_permissions_check'),
			),
		);

		// POST serviceareas
		register_rest_route(
			$this->namespace,
			'/' . $this->service_areas_rest_base,

			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'post_service_areas'),
				'permission_callback' => array($this, 'post_service_areas_permissions_check'),
			),
		);
				// POST serviceareas
				register_rest_route(
					$this->namespace,
					'/' . $this->service_areas_rest_base . '/detail',
		
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array($this, 'post_service_areas_detail'),
						'permission_callback' => array($this, 'post_service_areas_detail_permissions_check'),
					),
				);
		
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
		global $bmaw_capability_manage_submissions;

		error_log("get submissions current user " . get_current_user_id());
		if (!current_user_can($bmaw_capability_manage_submissions)) {
			return new WP_Error('rest_forbidden', esc_html__('Access denied: You cannot view submissions.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function approve_submission_action_permissions_check($request)
	{
		global $bmaw_capability_manage_submissions;

		error_log("approve submission current user " . get_current_user_id());
		if (!current_user_can($bmaw_capability_manage_submissions)) {
			return new WP_Error('rest_forbidden', esc_html__('Access denied: You cannot approve this submission.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function reject_submission_action_permissions_check($request)
	{
		global $bmaw_capability_manage_submissions;

		error_log("reject submission current user " . get_current_user_id());
		// if (!current_user_can($bmaw_capability_manage_submissions)) {
		// 	return new WP_Error('rest_forbidden', esc_html__('Access denied: You cannot reject this submission.'), array('status' => $this->authorization_status_code()));
		// }
		return true;
	}

	public function delete_submission_permissions_check($request)
	{
		// delete submissions is limited to admin

		error_log("delete submission current user " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new WP_Error('rest_forbidden', esc_html__('Access denied: You cannot delete this submission.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	/**
	 * Check permissions for user management.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_service_areas_permissions_check($request)
	{
		// get service areas is unauthenticated as it is also used by the end-user form 

		return true;
	}

	public function get_service_areas_detail_permissions_check($request)
	{
		error_log("get_service_areas_detail_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new WP_Error('rest_forbidden', esc_html__('Access denied: You cannot view service areas detail.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	/**
	 * Check permissions for user management.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return bool|WP_Error
	 */
	public function post_service_areas_permissions_check($request)
	{
		error_log("post_service_areas_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new WP_Error('rest_forbidden', esc_html__('Access denied: You cannot post service_area updates.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function post_service_areas_detail_permissions_check($request)
	{
		error_log("post_service_areas_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new WP_Error('rest_forbidden', esc_html__('Access denied: You cannot post service_area detail updates.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	/**
	 * Check permissions for form post
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

	public function get_submissions($request)
	{
		$result = $this->handlers->get_submissions_handler($request);
		return rest_ensure_response($result);
	}

	public function get_submission($request)
	{
		$result = $this->handlers->get_submission_handler($request);
		return rest_ensure_response($result);
	}

	public function delete_submission($request)
	{
		$result = $this->handlers->delete_submission_handler($request);
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

	public function approve_submission($request)
	{
		$result = $this->handlers->approve_submission_handler($request);
		return rest_ensure_response($result);
	}

	public function reject_submission($request)
	{
		$result = $this->handlers->reject_submission_handler($request);
		return rest_ensure_response($result);
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

	public function post_service_areas($request)
	{
		$result = $this->handlers->post_service_areas($request);
		return rest_ensure_response($result);
	}

	public function post_service_areas_detail($request)
	{
		$result = $this->handlers->post_service_areas_detail($request);
		return rest_ensure_response($result);
	}

	public function get_service_areas($request)
	{
		$result = $this->handlers->get_service_areas_handler($request);
		return rest_ensure_response($result);
	}

	public function get_service_areas_detail($request)
	{
		$result = $this->handlers->get_service_areas_detail_handler($request);
		return rest_ensure_response($result);
	}

	public function authorization_status_code()
	{
		$status = 401;
		if (is_user_logged_in()) {
			$status = 403;
		}
		return $status;
	}
}
