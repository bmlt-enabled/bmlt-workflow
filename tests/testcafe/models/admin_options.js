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
        this.backup_button = Selector("#bmltwf_backup");
        this.restore_button = Selector("#bmltwf_restore");
        this.bmltwf_file_selector = Selector("#bmltwf_file_selector");
        // dialogs
        this.restore_warning_dialog = Selector("#bmltwf_restore_warning_dialog");
        this.restore_warning_dialog_parent = this.restore_warning_dialog.parent();
        this.bmltwf_fso_email_address = Selector("#bmltwf_fso_email_address");
        this.bmltwf_optional_location_nation_displayname = Selector("#bmltwf_optional_location_nation_displayname");
        this.bmltwf_optional_location_nation_visible_checkbox = Selector("#bmltwf_optional_location_nation_visible_checkbox");
        this.bmltwf_optional_location_nation_required_checkbox = Selector("#bmltwf_optional_location_nation_required_checkbox");
        this.bmltwf_optional_location_sub_province_displayname = Selector("#bmltwf_optional_location_sub_province_displayname");
        this.bmltwf_optional_location_sub_province_visible_checkbox = Selector("#bmltwf_optional_location_sub_province_visible_checkbox");
        this.bmltwf_optional_location_sub_province_required_checkbox = Selector("#bmltwf_optional_location_sub_province_required_checkbox");
        this.bmltwf_optional_location_province_displayname = Selector("#bmltwf_optional_location_province_displayname");
        this.bmltwf_optional_location_province_visible_checkbox = Selector("#bmltwf_optional_location_province_visible_checkbox");
        this.bmltwf_optional_location_province_required_checkbox = Selector("#bmltwf_optional_location_province_required_checkbox");
        this.bmltwf_optional_postcode_displayname = Selector("#bmltwf_optional_postcode_displayname");
        this.bmltwf_optional_postcode_visible_checkbox = Selector("#bmltwf_optional_postcode_visible_checkbox");
        this.bmltwf_optional_postcode_required_checkbox = Selector("#bmltwf_optional_postcode_required_checkbox");
        this.bmltwf_fso_feature = Selector("#bmltwf_fso_feature");
        this.bmltwf_delete_closed_meetings = Selector("#bmltwf_delete_closed_meetings");
        this.bmltwf_email_from_address = Selector("#bmltwf_email_from_address");
        this.bmltwf_trusted_servants_can_delete_submissions = Selector("#bmltwf_trusted_servants_can_delete_submissions");
        this.submit = Selector('#submit');
        this.settings_updated = Selector("#setting-error-settings_updated");
    }
}

export const ao = new Admin_Options();