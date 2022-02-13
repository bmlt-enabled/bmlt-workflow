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

add_action('admin_menu', 'bmaw_options_page');

function bmaw_admin_css($hook)
{
    if ($hook != 'settings_page_bmaw-settings') {
        return;
    }
    wp_enqueue_style('bmaw-admin-css', plugin_dir_url(__FILE__) . 'css/admin_page.css', false, filemtime(plugin_dir_path(__FILE__) . 'css/admin_page.css'), 'all');
}
add_action('admin_enqueue_scripts', 'bmaw_admin_css');

function bmaw_options_page()
{

    add_options_page(
        'BMAW Settings', // page <title>Title</title>
        'BMAW Settings', // menu link text
        'manage_options', // capability to access the page
        'bmaw-settings', // page URL slug
        'display_admin_options_page', // callback function with content
        2 // priority
    );
}

add_action('admin_init',  'bmaw_register_setting');

function array_sanitize_callback($args)
{
    return $args;
}
function bmaw_register_setting()
{

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_service_committee_option_array',
        array(
            'type' => 'array',
            'description' => 'bmlt service committee array',
            'sanitize_callback' => 'array_sanitize_callback',
            'show_in_rest' => false,
            'default' => array(
                "0" => array("name" => "Committee1", "e1" => "email 1", "e2" => "email 1.1"),
                "1" => array("name" => "Committee2", "e1" => "email 2", "e2" => "email 2.1"),
            )
        )
    );

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_new_meeting_template',
        array(
            'type' => 'array',
            'description' => 'bmaw_new_meeting_template',
            'sanitize_callback' => 'editor_sanitize_callback',
            'show_in_rest' => false,
            'default' => array(
                "0" => array("name" => "Committee1", "e1" => "email 1", "e2" => "email 1.1"),
                "1" => array("name" => "Committee2", "e1" => "email 2", "e2" => "email 2.1"),
            )
        )
    );

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_existing_meeting_template',
        array(
            'type' => 'array',
            'description' => 'bmaw_new_meeting_template',
            'sanitize_callback' => 'bmaw_existing_meeting_template',
            'show_in_rest' => false,
            'default' => array(
                "0" => array("name" => "Committee1", "e1" => "email 1", "e2" => "email 1.1"),
                "1" => array("name" => "Committee2", "e1" => "email 2", "e2" => "email 2.1"),
            )
        )
    );

    register_setting(
        'bmaw-settings-group', // settings group name
        'bmaw_other_meeting_template',
        array(
            'type' => 'array',
            'description' => 'bmaw_other_meeting_template',
            'sanitize_callback' => 'editor_sanitize_callback',
            'show_in_rest' => false,
            'default' => array(
                "0" => array("name" => "Committee1", "e1" => "email 1", "e2" => "email 1.1"),
                "1" => array("name" => "Committee2", "e1" => "email 2", "e2" => "email 2.1"),
            )
        )
    );
    add_settings_section(
        'bmaw-settings-section-id',
        '', 
        '', 
        'bmaw-settings' 
    );

    add_settings_field(
        'bmaw_service_committee_option_array',
        'Service Committee Configuration',
        'service_committee_table_html', 
        'bmaw-settings', 
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_new_meeting_template',
        'Email Template for New Meeting',
        'bmaw_new_meeting_template_html', 
        'bmaw-settings', 
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_existing_meeting_template',
        'Email Template for Existing Meeting',
        'bmaw_existing_meeting_template_html', 
        'bmaw-settings', 
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

    add_settings_field(
        'bmaw_other_meeting_template',
        'Email Template for Other Meeting Update',
        'bmaw_other_meeting_template_html', 
        'bmaw-settings', 
        'bmaw-settings-section-id',
        array(
            'label_for' => 'bmaw_service_committee_option_array'
        )
    );

}

function bmaw_new_meeting_template_html()
{
    echo "<h2>new meeting</h2>";
    $content   = '';
    $editor_id = 'bmaw_new_meeting_template';
    
    wp_editor( $content, $editor_id );
}

function bmaw_existing_meeting_template_html()
{
    echo "<h2>existing meeting</h2>";
    $content   = '';
    $editor_id = 'bmaw_existing_meeting_template';
    
    wp_editor( $content, $editor_id );
}

function bmaw_other_meeting_template_html()
{
    echo "<h2>other meeting</h2>";

    $content   = '';
    $editor_id = 'bmaw_other_meeting_template';
    
    wp_editor( $content, $editor_id );
}

function service_committee_table_html()
{

    dbg("printing the text field");
    $arr = get_option('bmaw_service_committee_option_array');

    echo <<<END
    <table class="committeetable" id="bmaw-service-committee-table">
        <thead>
            <tr>
                <th>Service Area</th>
                <th>Email Address</th>
                <th>CC</th>
                <th style="width:auto;"></th>
            </tr>
        </thead>
    <tbody>
    END;
    $i = 0;
    foreach ($arr as $key => $value) {
        echo '<tr>';
        foreach ($value as $k2 => $v2) {
            echo '<td><input type="text" name="bmaw_service_committee_option_array[' . $i . '][' . $k2 . ']" value="' . $v2 . '"/></td>';
        }
        echo '<td><span class="dashicons dashicons-remove" id="bmaw-service-committee-' . $key . '-remove"></span></td></tr>';
        $i++;
    }   
    echo '<tr><td></td><td></td><td></td><td><span id="bmaw-service-committee-new-row" class="dashicons dashicons-insert"></span></td></tr>';
    echo '</tbody></table>';
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
