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

add_options_page('BMAW', 'BMAW', 'activate_plugins', basename(__FILE__), 'adminOptionsPage');

function adminOptionsPage()
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
