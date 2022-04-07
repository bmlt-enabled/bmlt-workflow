<?php
namespace wbw\REST;

if (!defined('ABSPATH')) exit; // die if being called directly

use wbw\Debug;
use wbw\REST\Handlers\BMLTServerHandler;
use wbw\REST\Handlers\ServiceBodiesHandler;
use wbw\REST\Handlers\SubmissionsHandler;

class Controller extends \WP_REST_Controller
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
		// $this->handlers = new Handlers();
		$this->BMLTServerHandler = new BMLTServerHandler();
		$this->ServiceBodiesHandler = new ServiceBodiesHandler();
		$this->SubmissionsHandler = new SubmissionsHandler();
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

		// GET bmltserver
		register_rest_route(
			$this->namespace,
			'/' . $this->server_rest_base,

			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_bmltserver'),
				'permission_callback' => array($this, 'get_bmltserver_permissions_check'),
			),
		);
		
		// POST bmltserver
		register_rest_route(
			$this->namespace,
			'/' . $this->server_rest_base,

			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'post_bmltserver'),
				'permission_callback' => array($this, 'post_bmltserver_permissions_check'),
			),
		);

		// PATCH bmltserver
		register_rest_route(
			$this->namespace,
			'/' . $this->server_rest_base,

			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array($this, 'patch_bmltserver'),
				'permission_callback' => array($this, 'patch_bmltserver_permissions_check'),
			),
		);
		
	}

	private function authorization_status_code()
	{
		$status = 401;
		if (is_user_logged_in()) {
			$status = 403;
		}
		return $status;
	}

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

	public function get_service_bodies_permissions_check($request)
	{
		// get service areas is unauthenticated as it is also used by the end-user form 

		return true;
	}


	public function post_service_bodies_permissions_check($request)
	{
		global $wbw_dbg;

		$wbw_dbg->debug_log("post_service_bodies_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot post service_area updates.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function post_bmltserver_permissions_check($request)
	{
		global $wbw_dbg;

		$wbw_dbg->debug_log("post_bmltserver " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot post server updates.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function get_bmltserver_permissions_check($request)
	{
		global $wbw_dbg;

		$wbw_dbg->debug_log("get_bmltserver " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot post server updates.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function patch_bmltserver_permissions_check($request)
	{
		global $wbw_dbg;

		$wbw_dbg->debug_log("patch_bmltserver " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', esc_html__('Access denied: You cannot patch server updates.'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

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

	public function post_submissions($request)
	{

		$resp = $this->handlers->meeting_update_form_handler_rest($request->get_body_params());
		return rest_ensure_response($resp);
	}

	public function get_service_bodies($request)
	{
		$result = $this->handlers->get_service_bodies_handler($request);
		return rest_ensure_response($result);
	}

	public function post_service_bodies($request)
	{
		$result = $this->handlers->post_service_bodies_handler($request);
		return rest_ensure_response($result);
	}

	public function get_bmltserver($request)
	{
		$result = $this->BMLTServerHandler->get_bmltserver_handler($request);
		return rest_ensure_response($result);
	}

	public function post_bmltserver($request)
	{
		$result = $this->BMLTServerHandler->post_bmltserver_handler($request);
		return rest_ensure_response($result);
	}

	public function patch_bmltserver($request)
	{
		$result = $this->BMLTServerHandler->patch_bmltserver_handler($request);
		return rest_ensure_response($result);
	}



}
