<?php

use wbw\WBW_WP_Options;
use wbw\WBW_Debug;
use wbw\WBW_Database;

foreach ($this->WBW_WP_Options->wbw_options as $key => $value) {
    $this->debug_log("deleting option " . $value);
    delete_option($value);
}

$this->WBW_Database->wbw_drop_tables();
$this->debug_log("removed tables");

// remove custom capability

$this->debug_log("deleting capabilities");

$users = get_users();
foreach ($users as $user) {
    $user->remove_cap($this->WBW_WP_Options->wbw_capability_manage_submissions);
}

remove_role('wbw_trusted_servant');

$this->debug_log("uninstall complete");
