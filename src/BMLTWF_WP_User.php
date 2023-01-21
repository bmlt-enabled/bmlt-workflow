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

namespace bmltwf;

class BMLTWF_WP_User
{
    use \bmltwf\BMLTWF_Debug;

    protected $BMLTWF_Database = new BMLTWF_Database();
    protected $BMLTWF_WP_Options = new BMLTWF_WP_Options();

    public function add_remove_caps()
    {
        global $wpdb;
        // add / remove user capabilities
        $users = get_users();
        $result = $wpdb->get_col('SELECT DISTINCT wp_uid from ' . $this->BMLTWF_Database->bmltwf_service_bodies_access_table_name, 0);
        // $this->debug_log(($sql));
        // $this->debug_log(($result));
        foreach ($users as $user) {
            $this->debug_log("checking user id " . $user->get('ID'));
            if (in_array($user->get('ID'), $result)) {
                $user->add_cap($this->BMLTWF_WP_Options->bmltwf_capability_manage_submissions);
                $this->debug_log("adding cap");
            } else {
                $user->remove_cap($this->BMLTWF_WP_Options->bmltwf_capability_manage_submissions);
                $this->debug_log("removing cap");
            }
        }
    }
}
