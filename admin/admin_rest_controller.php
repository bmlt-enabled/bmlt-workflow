<?php

function bmaw_submissions_controller() {
	$controller = new bmaw_submissions_rest();
	$controller->register_routes();
}

/**
 * Add rest api endpoint for category listing
 */

/**
 * Class Category_List_Rest
 */
class bmaw_submissions_rest extends WP_REST_Controller {
	/**
	 * The namespace.
	 *
	 * @var string
	 */
	protected $namespace;
	/**
	 * Rest base for the current object.
	 *
	 * @var string
	 */
	protected $rest_base;

	/**
	 * Category_List_Rest constructor.
	 */
	public function __construct() {

		$this->namespace = 'bmaw-submission/v1';
		$this->rest_base = 'submission';
	}
	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(

			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				 'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
			// array(
			// 	'methods'         => WP_REST_Server::EDITABLE,
			// 	'callback'        => array( $this, 'update_item' ),
			// 	'permission_callback' => array( $this, 'update_item_permissions_check' ),
			// 	'args'            => $this->get_endpoint_args_for_item_schema( false ),
			// ),
			'schema' => null,

		) );

	}
	/**
	 * Check permissions for the read.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
        $data = get_userdata( get_current_user_id() );
 
if ( is_object( $data) ) {
    $current_user_caps = $data->allcaps;
     
    // print it to the screen
    error_log(print_r( $current_user_caps, true ));
}
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the submission resource.' ), array( 'status' => $this->authorization_status_code() ) );
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
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot update the submission resource.' ), array( 'status' => $this->authorization_status_code() ) );
		}
		return true;
	}
	/**
	 * Grabs all the submission list.
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function get_items( $request ) {

        global $wpdb;
        global $bmaw_submissions_table_name;
        
        $result = $wpdb->get_results('SELECT * FROM '.$bmaw_submissions_table_name, ARRAY_A);

		// Return all of our comment response data.
		return rest_ensure_response( $result );
	}

	/**
	 * Update category order
	 *
	 * @param WP_REST_Request $request get data from request.
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
	// public function update_item( $request ) {

        
	// 	$data = [];
	// 	if ( ! isset( $request['order'] ) ) {
	// 		return new WP_Error( 'invalid_data', __( 'Cannot update category order.' ), array( 'status' => 400 ) );
	// 	}
	// 	$res = update_option( 'category_order', $request['order'] );

	// 	if ( $res ) {
	// 		$data['msg'] = __( 'Category order updated', '' );
	// 	} else {
	// 		return new WP_Error( 'cant update', __( 'Please provide proper data' ), array( 'status' => 400 ) );
	// 	}

	// 	return rest_ensure_response( $data );
	// }
	/**
	 * Sets up the proper HTTP status code for authorization.
	 *
	 * @return int
	 */
	public function authorization_status_code() {

		$status = 401;

		if ( is_user_logged_in() ) {
			$status = 403;
		}

		return $status;
	}
}
