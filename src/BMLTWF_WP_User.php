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

trait BMLTWF_WP_User
{
    use \bmltwf\BMLTWF_Debug;
    use \bmltwf\BMLTWF_Constants;

    public function add_remove_caps($uids)
    {
        // add / remove user capabilities
        $users = get_users();
        // $this->debug_log(($sql));
        // $this->debug_log(($result));
        foreach ($users as $user) {
            $this->debug_log("checking user id " . $user->get('ID'));
            if (in_array($user->get('ID'), $uids)) {
                $user->add_cap($this->bmltwf_capability_manage_submissions);
                $this->debug_log("adding cap");
            } else {
                $user->remove_cap($this->bmltwf_capability_manage_submissions);
                $this->debug_log("removing cap");
            }
        }
    }
}
