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


add_action('admin_menu', 'bmaw_initialise_options');
// add_action('admin_init', 'bmaw_initialise_settings');

function bmaw_initialise_options()
{
    dbg('in bmaw_initialise_options');

    add_options_page('BMAW', 'BMAW', 'manage_options', basename(__FILE__), 'display_admin_options_page');

}

function bmaw_initialise_settings()
{
    dbg('in initialize settings');

    add_settings_section(
        'list_service_areas_section',         // ID used to identify this section and with which to register options
        'Service Areas',                  // Title to be displayed on the administration page
        'service_areas_section_callback', // Callback used to render the description of the section
        'bmaw_settings_page'                           // Page on which to add this section of options
    );

    add_settings_field(
        'list_service_areas_field',                      // ID used to identify the field throughout the theme
        'Service Areas',                           // The label to the left of the option interface element
        'list_service_areas_callback',   // The name of the function responsible for rendering the option interface
        'bmaw_settings_page',                          // The page on which this option will be displayed
        'list_service_areas_section',         // The name of the section to which this field belongs
        array(                              // The array of arguments to pass to the callback. In this case, just a description.
            'Activate this setting to display the header.'
        )
    );
    register_setting(
        'BMAW',
        'homepage_text'
    );
}

function list_service_areas_callback($args)
{
    dbg('in list_service_areas_callback');

    // // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
    // $html = '<input type="checkbox" id="show_header" name="show_header" value="1" ' . checked(1, 1, false) . '/>';
    // // Here, we will take the first argument of the array and add it to a label next to the checkbox
    // $html .= '<label for="show_header"> '  . $args[0] . '</label>';

    // echo $html;
    $text = get_option( 'homepage_text' );

	printf(
		'<input type="text" id="homepage_text" name="homepage_text" value="%s" />',
		esc_attr( $text )
	);
}

function service_areas_section_callback($args)
{
    dbg('in service_areas_section_callback');

    echo '<p> hows it going </p>';
    // // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
    // $html = '<input type="checkbox" id="show_header" name="show_header" value="1" ' . checked(1, 1, false) . '/>';
    // // Here, we will take the first argument of the array and add it to a label next to the checkbox
    // $html .= '<label for="show_header"> '  . $args[0] . '</label>';

    // echo $html;
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

/**
 * Adds a new top-level menu to the bottom of the WordPress administration menu.
 */
function sandbox_create_menu_page()
{

    add_menu_page(
        'Sandbox Options',          // The title to be displayed on the corresponding page for this menu
        'Sandbox',                  // The text to be displayed for this actual menu item
        'administrator',            // Which type of users can see this menu
        'sandbox',                  // The unique ID - that is, the slug - for this menu item
        'sandbox_menu_page_display', // The name of the function to call when rendering the menu for this page
        ''
    );
} // end sandbox_create_menu_page
add_action('admin_menu', 'sandbox_create_menu_page');

/**
 * Renders the basic display of the menu page for the theme.
 */
function sandbox_menu_page_display()
{

    // Create a header in the default WordPress 'wrap' container
    $html = '<div class="wrap">';
    $html .= '<h2>Sandbox</h2>';
    $html .= '</div>';

    // Send the markup to the browser
    echo $html;
} // end sandbox_menu_page_display
