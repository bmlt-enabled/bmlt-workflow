<?php

/**
 * Plugin Name: BMLT Meeting Admin Workflow
 * Plugin URI: 
 * Description: BMLT Meeting Admin Workflow
 * Version: 1.0
 * Author: @nb-bmlt
 * Author URI: 
 **/


function dbg($logmsg)
{
    $log = plugin_dir_path(__FILE__) . 'debug.log';
    error_log($logmsg . PHP_EOL, 3, $log);
}
function meeting_update_form()
{
    dbg("outputting the meeting update form");
    include_once('templates/meeting_update.php');
}

add_shortcode('bmaw-meeting-update-form', 'meeting_update_form');
add_action('admin_post_nopriv_meeting_update_form_response', 'the_form_response');
add_action('admin_post_meeting_update_form_response', 'the_form_response');

function enqueue_form_deps()
{
    wp_register_style('select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all');
    wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '1.0', true);
    wp_enqueue_style('select2css');
    wp_enqueue_script('select2');
    wp_enqueue_script('bmawjs', plugin_dir_url(__FILE__) . 'js/script_includes.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/script_includes.js'), true);
    wp_enqueue_script('bmaw-meetingupdatejs', plugin_dir_url(__FILE__) . 'js/meeting_update.js', array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'js/meeting_update.js'), true);
}
add_action('wp_enqueue_scripts', 'enqueue_form_deps');

add_action( 'admin_menu', 'misha_options_page' );

function misha_options_page() {

	add_options_page(
		'BMAW Settings', // page <title>Title</title>
		'BMAW Settings', // menu link text
		'manage_options', // capability to access the page
		'bmaw-settings', // page URL slug
		'display_admin_options_page', // callback function with content
		2 // priority
	);

}

add_action( 'admin_init',  'bmaw_register_setting' );

function bmaw_register_setting(){

	register_setting(
		'bmaw-settings-group', // settings group name
		'homepage_text', // option name
		'sanitize_text_field' // sanitization function
	);

	add_settings_section(
		'some_settings_section_id', // section ID
		'', // title (if needed)
		'', // callback function (if needed)
		'bmaw-settings' // page slug
	);

	add_settings_field(
		'homepage_text',
		'BMAW Setting',
		'misha_text_field_html', // function which prints the field
		'bmaw-settings', // page slug
		'some_settings_section_id', // section ID
		array( 
			'label_for' => 'homepage_text',
			'class' => 'misha-class', // for <tr> element
		)
	);

}

function misha_text_field_html(){

    $myarr = array(
        "Committee1" => array("e1"=>"email 1", "e2"=>"email 1.1"),
        "Committee2" => array("e1"=>"email 2", "e2"=>"email 2.1"),
    );
    $lol = update_option("homepage_text", $myarr);
    dbg('lol = '.$lol);
	$arr = get_option( 'homepage_text' );
    
    foreach( $arr as $key => $value ){
        echo $key." ---\n";
        foreach( $value as $k2 => $v2)
        {
            echo $k2."\t=>\t".$v2."\n";
        }
    }
	// printf(
	// 	'<input type="text" id="homepage_text" name="homepage_text" value="%s" />',
	// 	esc_attr( $text )
	// );

}

function display_admin_options_page()
{
    dbg("outputting the admin page");
    include_once('templates/admin_options.php');
}

function the_form_response()
{

    if (isset($_POST['meeting_update_form_nonce']) && wp_verify_nonce($_POST['meeting_update_form_nonce'], 'meeting_update_form_nonce')) {

        // sanitize the input
        // $nds_user_meta_key = sanitize_key( $_POST['nds']['user_meta_key'] );
        // $nds_user_meta_value = sanitize_text_field( $_POST['nds']['user_meta_value'] );
        // $nds_user =  get_user_by( 'login',  $_POST['nds']['user_select'] );
        // $nds_user_id = absint( $nds_user->ID ) ;

        $to = 'emailsendto@example.com';
        $subject = 'The subject';
        $body = 'The email body content';
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: My Site Name <support@example.com>');
        dbg('sending mail');
        wp_mail($to, $subject, $body, $headers);
        dbg('mail sent');
        // redirect the user to the appropriate page
        // wp_redirect( 'https://www.google.com' );
        exit;
    } else {
        wp_die('invalid nonce');
    }
}
