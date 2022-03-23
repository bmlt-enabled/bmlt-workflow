<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$option_name = 'wporg_option';
 
delete_option('wbw_service_committee_option_array');
delete_option('wbw_new_meeting_template');
delete_option('wbw_existing_meeting_template');
delete_option('wbw_other_meeting_template');
delete_option('wbw_email_from_address');

?>