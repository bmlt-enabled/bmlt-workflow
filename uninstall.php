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


require 'config.php';

if (file_exists('vendor/autoload.php')) {
    // use composer autoload if we're running under phpunit
    include 'vendor/autoload.php';
} else {
    // custom autoloader if not. only autoloads out of src directory
    spl_autoload_register(function (string $class) {
        if (strpos($class, 'wbw\\') === 0) {
            $class = str_replace('wbw\\', '', $class);
            require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
        }
    });
}

use wbw\WBW_WP_Options;
use wbw\WBW_Database;

function debug_log($message)
{
    if (WBW_DEBUG) {
        $out = print_r($message, true);
        error_log(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": " . $out);
    }
}


function wbw_uninstaller()
{
    $WBW_WP_Options = new WBW_WP_Options();
    $WBW_Database = new WBW_Database();

    foreach ($WBW_WP_Options->wbw_options as $key => $value) {
        debug_log("deleting option " . $value);
        delete_option($value);
    }

    $WBW_Database->wbw_drop_tables();
    debug_log("removed tables");

    // remove custom capability

    debug_log("deleting capabilities");

    $users = get_users();
    foreach ($users as $user) {
        $user->remove_cap($WBW_WP_Options->wbw_capability_manage_submissions);
    }

    remove_role('wbw_trusted_servant');

    debug_log("uninstall complete");
}

wbw_uninstaller();
