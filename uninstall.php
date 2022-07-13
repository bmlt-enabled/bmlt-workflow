<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

use wbw\WBW_Database;
use wbw\WBW_WP_Options;
use wbw\WBW_Debug;

$WBW_WP_Options = new WBW_WP_Options();
$WBW_Database = new WBW_Database();
$WBW_Debug = new WBW_Debug();

foreach ($WBW_WP_Options->wbw_options as $key => $value) {
    $this->debug_log("deleting option " . $value );
    delete_option($value);
}

$WBW_Database->wbw_drop_tables;
$this->debug_log("removed tables");
$this->debug_log("uninstall complete");

?>