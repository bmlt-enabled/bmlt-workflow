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
        this.backup_button = Selector("#bw_backup");
        this.restore_button = Selector("#bw_restore");
        this.bw_file_selector = Selector("#bw_file_selector");
        // dialogs
        this.restore_warning_dialog = Selector("#bw_restore_warning_dialog");
        this.restore_warning_dialog_parent = this.restore_warning_dialog.parent();
        this.bw_fso_email_address = Selector("#bw_fso_email_address");
        this.bw_optional_location_nation = Selector("#bw_optional_location_nation");
        this.bw_optional_location_sub_province = Selector("#bw_optional_location_sub_province");
        this.bw_optional_postcode = Selector("#bw_optional_postcode");
        this.bw_fso_feature = Selector("#bw_fso_feature");
        this.bw_delete_closed_meetings = Selector("#bw_delete_closed_meetings");
        this.bw_email_from_address = Selector("#bw_email_from_address");
        this.submit = Selector('#submit');
        this.settings_updated = Selector("#setting-error-settings_updated");
    }
}

export const ao = new Admin_Options();