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

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require 'config.php';

if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class) {
        if (strpos($class, 'bmltwf\\') === 0) {
            $class = str_replace('bmltwf\\', '', $class);
            require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
        }
    });
}

use bmltwf\BMLTWF_WP_Options;
use bmltwf\BMLTWF_Database;

function bmltwf_uninstaller()
{
    $BMLTWF_WP_Options = new BMLTWF_WP_Options();
    $BMLTWF_Database = new BMLTWF_Database();

    // Delete all plugin options
    foreach ($BMLTWF_WP_Options->bmltwf_options as $value) {
        delete_option($value);
    }

    // Drop all database tables
    $BMLTWF_Database->bmltwf_drop_tables();

    // Remove custom capabilities from all users
    $users = get_users();
    foreach ($users as $user) {
        $user->remove_cap($BMLTWF_WP_Options->bmltwf_capability_manage_submissions);
    }

    // Remove custom role
    remove_role('bmltwf_trusted_servant');
}

bmltwf_uninstaller();
