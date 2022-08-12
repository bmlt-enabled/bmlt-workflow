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
        if (strpos($class, 'bw\\') === 0) {
            $class = str_replace('bw\\', '', $class);
            require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
        }
    });
}

use bw\BW_WP_Options;
use bw\BW_Database;

function debug_log($message)
{
    if (BW_DEBUG) {
        $out = print_r($message, true);
        error_log(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ": " . $out);
    }
}


function bw_uninstaller()
{
    $BW_WP_Options = new BW_WP_Options();
    $BW_Database = new BW_Database();

    foreach ($BW_WP_Options->bw_options as $key => $value) {
        debug_log("deleting option " . $value);
        delete_option($value);
    }

    $BW_Database->bw_drop_tables();
    debug_log("removed tables");

    // remove custom capability

    debug_log("deleting capabilities");

    $users = get_users();
    foreach ($users as $user) {
        $user->remove_cap($BW_WP_Options->bw_capability_manage_submissions);
    }

    remove_role('bw_trusted_servant');

    debug_log("uninstall complete");
}

bw_uninstaller();
