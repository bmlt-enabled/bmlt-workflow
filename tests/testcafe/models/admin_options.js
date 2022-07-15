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

import { Selector } from 'testcafe';

class Admin_Options {
    constructor () {
        this.backup_button = Selector("#wbw_backup");
        this.restore_button = Selector("#wbw_restore");
        this.wbw_file_selector = Selector("#wbw_file_selector");
        // dialogs
        this.restore_warning_dialog = Selector("#wbw_restore_warning_dialog");
        this.restore_warning_dialog_parent = this.restore_warning_dialog.parent();
        this.wbw_fso_email_address = Selector("#wbw_fso_email_address");
        this.wbw_optional_location_nation = Selector("#wbw_optional_location_nation");
        this.wbw_optional_location_sub_province = Selector("#wbw_optional_location_sub_province");
        this.wbw_delete_closed_meetings = Selector("#wbw_delete_closed_meetings");
        this.wbw_email_from_address = Selector("#wbw_email_from_address");
        this.submit = Selector('#submit');
        this.settings_updated = Selector("#setting-error-settings_updated");
    }
}

export const ao = new Admin_Options();