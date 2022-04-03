<?php
namespace wbw\REST\Controller;

if (!defined('ABSPATH')) exit; // die if being called directly

use wbw\Debug;
use wbw\BMLT\Integration;
use wbw\REST\Handlers;

function wbw_submissions_controller()
{
	$controller = new wbw_submissions_rest();
	$controller->register_routes();
}

class wbw_submissions_rest extends \WP_REST_Controller
{

	protected $namespace;
	protected $rest_base;

	public function __construct()
	{
		global $wbw_rest_namespace;
		$this->namespace = $wbw_rest_namespace;
		$this->submissions_rest_base = 'submissions';
		$this->service_bodies_rest_base = 'servicebodies';
		$this->server_rest_base = 'bmltserver';
		$this->bmlt_integration = new Integration\BMLTIntegration;
		$this->handlers = new Handlers\wbw_rest_handlers();
	}

	public function register_routes()
	{

		// submissions/
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base, array(

			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_submissions'),
				'permission_callback' => array($this, 'get_submissions_permissions_check'),
			),
			array(
				'methods'         => \WP_REST_Server::CREATABLE,
				'callback'        => array($this, 'post_submissions'),
				'permission_callback' => array($this, 'post_submissions_permissions_check'),
				'args'            => $this->get_endpoint_args_for_item_schema(false),
			),
			'schema' => null,

		));
		// GET submissions/<id>
		register_rest_route(
			$this->namespace,
			'/' . $this->submissions_rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_submission'),
					'permission_callback' => array($this, 'get_submission_permissions_check')
				),

				// DELETE submissions/<id>
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array($this, 'delete_submission'),
					'permission_callback' => array($this, 'delete_submission_permissions_check')
				),

				// PUT submissions/<id>
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array($this, 'patch_submission'),
					'permission_callback' => array($this, 'patch_submission_permissions_check')
				),
			)
		);

		// POST submissions/<id>/approve
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base . '/(?P<id>[\d]+)/approve', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'approve_submission'),
			'permission_callback' => array($this, 'approve_submission_action_permissions_check'),
			'args'     => [
				'action_message' => [
					'required' => false,
					'type'     => 'string',
				],
			],
		));
		// POST submissions/<id>/reject
		register_rest_route($this->namespace, '/' . $this->submissions_rest_base . '/(?P<id>[\d]+)/reject', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'reject_submission'),
			'permission_callback' => array($this, 'reject_submission_action_permissions_check'),
			'args'     => [
				'action_message' => [
					'required' => false,
					'type'     => 'string',
				],
			],
		));

		// GET servicebodies
		register_rest_route(
			$this->namespace,
			'/' . $this->service_bodies_rest_base,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_service_bodies'),
				'permission_callback' => array($this, 'get_service_bodies_permissions_check'),
			),
		);

		// POST servicebodies
		register_rest_route(
			$this->namespace,
			'/' . $this->service_bodies_rest_base,

			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'post_service_bodies'),
				'permission_callback' => array($this, 'post_service_bodies_permissions_check'),
			),
		);

		// POST server
		register_rest_route(
			$this->namespace,
			'/' . $this->server_rest_base,

			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'post_server'),
				'permission_callback' => array($this, 'post_server_permissions_check'),
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
		global $wbw_capability_manage_submissions;
		global $wbw_dbg;

		$wbw_dbg->debug_log("get submissions current user " . get_current_user_id());
		if (!current_user_can($wbw_capability_manage_submissions)) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot view submissions.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function get_submission_permissions_check($request)
	{
		global $wbw_capability_manage_submissions;
		global $wbw_dbg;
		$wbw_dbg->debug_log("get submissions current user " . get_current_user_id());
		if (!current_user_can($wbw_capability_manage_submissions)) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot view a submission.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function approve_submission_action_permissions_check($request)
	{
		global $wbw_capability_manage_submissions;
		global $wbw_dbg;

		$wbw_dbg->debug_log("approve submission current user " . get_current_user_id());
		if (!current_user_can($wbw_capability_manage_submissions)) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot approve this submission.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function reject_submission_action_permissions_check($request)
	{
		global $wbw_capability_manage_submissions;
		global $wbw_dbg;

		$wbw_dbg->debug_log("reject submission current user " . get_current_user_id());
		if (!current_user_can($wbw_capability_manage_submissions)) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot reject this submission.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function delete_submission_permissions_check($request)
	{
		// delete submissions is limited to admin
		global $wbw_dbg;

		$wbw_dbg->debug_log("delete submission current user " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot delete this submission.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function patch_submission_permissions_check($request)
	{
		global $wbw_capability_manage_submissions;
		global $wbw_dbg;

		$wbw_dbg->debug_log("patch submission current user " . get_current_user_id());
		if (!current_user_can($wbw_capability_manage_submissions)) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot patch this submission.'), array('status' => $this->authorization_status_code()));
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
	public function get_service_bodies_permissions_check($request)
	{
		// get service areas is unauthenticated as it is also used by the end-user form 

		return true;
	}

	/**
	 * Check permissions for user management.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return bool|WP_Error
	 */
	public function post_service_bodies_permissions_check($request)
	{
		global $wbw_dbg;

		$wbw_dbg->debug_log("post_service_bodies_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot post service_area updates.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	/**
	 * Check permissions for server configuration.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return bool|WP_Error
	 */
	public function post_server_permissions_check($request)
	{
		global $wbw_dbg;

		$wbw_dbg->debug_log("post_server " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot post server updates.'), array('status' => $this->authorization_status_code()));
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

	public function patch_submission($request)
	{
		$result = $this->handlers->patch_submission_handler($request);
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
		global $wbw_dbg;
		$wbw_dbg->debug_log($this->vdump($request->get_body_params()));

		$resp = meeting_update_form_handler_rest($request->get_body_params());

		return rest_ensure_response($resp);
	}

	public function post_service_bodies($request)
	{
		$result = $this->handlers->post_service_bodies_handler($request);
		return rest_ensure_response($result);
	}

	// public function post_service_bodies_detail($request)
	// {
	// 	$result = $this->handlers->post_service_bodies_detail_handler($request);
	// 	return rest_ensure_response($result);
	// }

	public function post_server($request)
	{
		$result = $this->handlers->post_server_handler($request);
		return rest_ensure_response($result);
	}

	public function get_service_bodies($request)
	{
		$result = $this->handlers->get_service_bodies_handler($request);
		return rest_ensure_response($result);
	}

	// public function get_service_bodies_detail($request)
	// {
	// 	$result = $this->handlers->get_service_bodies_detail_handler($request);
	// 	return rest_ensure_response($result);
	// }

	public function authorization_status_code()
	{
		$status = 401;
		if (is_user_logged_in()) {
			$status = 403;
		}
		return $status;
	}
}
