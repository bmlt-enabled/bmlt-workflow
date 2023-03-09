<?php
// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of bmlt-workflow.
// 
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.


namespace bmltwf\REST;


use bmltwf\REST\Handlers\BMLTServerHandler;
use bmltwf\REST\Handlers\ServiceBodiesHandler;
use bmltwf\REST\Handlers\SubmissionsHandler;
use bmltwf\REST\Handlers\OptionsHandler;

class Controller extends \WP_REST_Controller
{

	use \bmltwf\BMLTWF_Debug;
	use \bmltwf\BMLTWF_Constants;
	
	protected $BMLTServerHandler;
	protected $ServiceBodiesHandler;
	protected $SubmissionsHandler;
	protected $OptionsHandler; 

	public function __construct($stub = null)
	{
		if(!$stub)
		{
			// $this->debug_log("Controller: Creating new BMLTServerHandler");
			$this->BMLTServerHandler = new BMLTServerHandler();
			// $this->debug_log("Controller: Creating new ServiceBodiesHandler");
			$this->ServiceBodiesHandler = new ServiceBodiesHandler();
			// $this->debug_log("Controller: Creating new SubmissionsHandler");
			$this->SubmissionsHandler = new SubmissionsHandler();
			// $this->debug_log("Controller: Creating new OptionsHandler");
			$this->OptionsHandler = new OptionsHandler();
		}
	}

	public function register_routes()
	{

		// submissions/
		register_rest_route($this->bmltwf_rest_namespace, '/' . $this->bmltwf_submissions_rest_base, array(

			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_submissions'),
				'permission_callback' => array($this, 'get_submissions_permissions_check'),
			),
			array(
				'methods'         => \WP_REST_Server::CREATABLE,
				'callback'        => array($this, 'post_submissions'),
				'permission_callback' => array($this, 'post_submissions_permissions_check'),
			),
		));

		// GET submissions/<id>
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_submissions_rest_base . '/(?P<id>[\d]+)',
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
		register_rest_route($this->bmltwf_rest_namespace, '/' . $this->bmltwf_submissions_rest_base . '/(?P<id>[\d]+)/approve', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'approve_submission'),
			'permission_callback' => array($this, 'approve_submission_action_permissions_check'),
			'args'     => [
				'action_message' => [
					'required' => false,
					'type'     => 'string',
					'sanitize_callback' => function ($param, $request, $key) {
						return (sanitize_text_field($param));
					}
				],
			],
		));
		// POST submissions/<id>/reject
		register_rest_route($this->bmltwf_rest_namespace, '/' . $this->bmltwf_submissions_rest_base . '/(?P<id>[\d]+)/reject', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'reject_submission'),
			'permission_callback' => array($this, 'reject_submission_action_permissions_check'),
			'args'     => [
				'action_message' => [
					'required' => false,
					'type'     => 'string',
					'sanitize_callback' => function ($param, $request, $key) {
						return (sanitize_text_field($param));
					}
				],
			],
		));

		// GET servicebodies
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_service_bodies_rest_base,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_service_bodies'),
				'permission_callback' => array($this, 'get_service_bodies_permissions_check'),
				'args'     => [
					'detail' => [
						'required' => false,
						'type'     => 'string',
						'validate_callback' => function ($param, $request, $key) {
							return ($param === 'true' || $param === 'false');
						}
					],
				],
			),
		);

		// POST servicebodies
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_service_bodies_rest_base,

			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'post_service_bodies'),
				'permission_callback' => array($this, 'post_service_bodies_permissions_check'),
			),
		);

		// DELETE servicebodies
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_service_bodies_rest_base,

			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array($this, 'delete_service_bodies'),
				'permission_callback' => array($this, 'delete_service_bodies_permissions_check'),
				'args'     => [
					'checked' => [
						'required' => false,
						'type'     => 'string',
						'validate_callback' => function ($param, $request, $key) {
							return ($param === 'true' || $param === 'false');
						}
					],
				],
			),
		);

		// GET bmltserver
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_bmltserver_rest_base,

			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_bmltserver'),
				'permission_callback' => array($this, 'get_bmltserver_permissions_check'),
			),
		);

		// POST bmltserver
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_bmltserver_rest_base,

			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'post_bmltserver'),
				'permission_callback' => array($this, 'post_bmltserver_permissions_check'),
			),
		);

		// PATCH bmltserver
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_bmltserver_rest_base,

			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array($this, 'patch_bmltserver'),
				'permission_callback' => array($this, 'patch_bmltserver_permissions_check'),
			),
		);

		// GET bmltserver/geolocate
		register_rest_route(
			$this->bmltwf_rest_namespace,
			'/' . $this->bmltwf_bmltserver_rest_base . '/geolocate',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_bmltserver_geolocate'),
				'permission_callback' => array($this, 'get_bmltserver_geolocate_permissions_check'),
			),
		);

		// POST options/backup
		register_rest_route($this->bmltwf_rest_namespace, '/' . $this->bmltwf_options_rest_base . '/backup', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'post_bmltwf_backup'),
			'permission_callback' => array($this, 'post_bmltwf_backup_permissions_check'),
		));

		// POST options/restore
		register_rest_route($this->bmltwf_rest_namespace, '/' . $this->bmltwf_options_rest_base . '/restore', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'post_bmltwf_restore'),
			'permission_callback' => array($this, 'post_bmltwf_restore_permissions_check'),
		));
	}

	private function authorization_status_code()
	{
		$status = 401;
		if (is_user_logged_in()) {
			$status = 403;
		}
		return $status;
	}

	// permission validation for each rest call

	public function get_submissions_permissions_check($request)
	{


		$this->debug_log("get submissions current user " . get_current_user_id());
		if ((!current_user_can($this->bmltwf_capability_manage_submissions))&&(!current_user_can('manage_options'))) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot view submissions.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function get_submission_permissions_check($request)
	{

		$this->debug_log("get submissions current user " . get_current_user_id());
		if ((!current_user_can($this->bmltwf_capability_manage_submissions))&&(!current_user_can('manage_options'))) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot view a submission.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function approve_submission_action_permissions_check($request)
	{



		$this->debug_log("approve submission current user " . get_current_user_id());
		if ((!current_user_can($this->bmltwf_capability_manage_submissions))&&(!current_user_can('manage_options'))) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot approve this submission.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function reject_submission_action_permissions_check($request)
	{


		$this->debug_log("reject submission current user " . get_current_user_id());
		if ((!current_user_can($this->bmltwf_capability_manage_submissions))&&(!current_user_can('manage_options'))) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot reject this submission.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function delete_submission_permissions_check($request)
	{
		// delete submissions is limited based on bmltwf_trusted_servants_can_delete_submissions option

		$can_delete = false;

		$bmltwf_trusted_servants_can_delete_submissions = get_option('bmltwf_trusted_servants_can_delete_submissions');
		if ($bmltwf_trusted_servants_can_delete_submissions == "true") {
			$can_delete = current_user_can($this->bmltwf_capability_manage_submissions);
		} else {
			$can_delete = current_user_can('manage_options');
		}

		$this->debug_log("delete submission current user " . get_current_user_id());
		if (!$can_delete) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot delete this submission.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function patch_submission_permissions_check($request)
	{

		$this->debug_log("patch submission current user " . get_current_user_id());
		if ((!current_user_can($this->bmltwf_capability_manage_submissions))&&(!current_user_can('manage_options'))) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot patch this submission.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
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


		$this->debug_log("post_service_bodies_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot post service_area updates.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function delete_service_bodies_permissions_check($request)
	{

		$this->debug_log("post_service_bodies_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot post service_area updates.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function post_bmltserver_permissions_check($request)
	{


		$this->debug_log("post_bmltserver " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot post server updates.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function get_bmltserver_permissions_check($request)
	{


		$this->debug_log("get_bmltserver " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot post server updates.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function patch_bmltserver_permissions_check($request)
	{


		$this->debug_log("patch_bmltserver " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot patch server updates.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function get_bmltserver_geolocate_permissions_check($request)
	{


		$this->debug_log("patch_bmltserver " . get_current_user_id());
		if ((!current_user_can($this->bmltwf_capability_manage_submissions))&&(!current_user_can('manage_options'))) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot geolocate an address.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function post_bmltwf_backup_permissions_check($request)
	{


		$this->debug_log("post_bmltwf_Backup_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot call the backup API.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function post_bmltwf_restore_permissions_check($request)
	{


		$this->debug_log("post_bmltwf_restore_permissions_check " . get_current_user_id());
		if (!current_user_can('manage_options')) {
			return new \WP_Error('rest_forbidden', __('Access denied: You cannot call the restore API.','bmlt-workflow'), array('status' => $this->authorization_status_code()));
		}
		return true;
	}

	public function post_submissions_permissions_check($request)
	{
		// Anyone can post a form submission
		return true;
	}

	// handler stubs calling off to the handler objects

	public function get_submissions($request)
	{
		$result = $this->SubmissionsHandler->get_submissions_handler($request);
		return rest_ensure_response($result);
	}

	public function get_submission($request)
	{
		$result = $this->SubmissionsHandler->get_submission_handler($request);
		return rest_ensure_response($result);
	}

	public function delete_submission($request)
	{
		$result = $this->SubmissionsHandler->delete_submission_handler($request);
		return rest_ensure_response($result);
	}

	public function approve_submission($request)
	{
		$result = $this->SubmissionsHandler->approve_submission_handler($request);
		return rest_ensure_response($result);
	}

	public function reject_submission($request)
	{
		$result = $this->SubmissionsHandler->reject_submission_handler($request);
		return rest_ensure_response($result);
	}

	public function patch_submission($request)
	{
		$result = $this->SubmissionsHandler->patch_submission_handler($request);
		return rest_ensure_response($result);
	}

	public function post_submissions($request)
	{

		$resp = $this->SubmissionsHandler->meeting_update_form_handler_rest($request);
		return rest_ensure_response($resp);
	}

	public function get_service_bodies($request)
	{
		$result = $this->ServiceBodiesHandler->get_service_bodies_handler($request);
		return rest_ensure_response($result);
	}

	public function post_service_bodies($request)
	{
		$result = $this->ServiceBodiesHandler->post_service_bodies_handler($request);
		return rest_ensure_response($result);
	}

	public function delete_service_bodies($request)
	{
		$result = $this->ServiceBodiesHandler->delete_service_bodies_handler($request);
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

	public function get_bmltserver_geolocate($request)
	{
		$result = $this->BMLTServerHandler->get_bmltserver_geolocate_handler($request);
		return rest_ensure_response($result);
	}

	public function post_bmltwf_backup($request)
	{
		$result = $this->OptionsHandler->post_bmltwf_backup_handler($request);
		return rest_ensure_response($result);
	}

	public function post_bmltwf_restore($request)
	{
		$result = $this->OptionsHandler->post_bmltwf_restore_handler($request);
		return rest_ensure_response($result);
	}
}
