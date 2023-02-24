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
class Admin_Submissions {
    constructor () {
        // datatables
        this.dt_submission = Selector('#dt-submission');
        this.dt_submission_wrapper = Selector("#dt-submission_wrapper");
        // dialogs
        this.approve_dialog = Selector("#bmltwf_submission_approve_dialog");
        this.approve_dialog_parent = this.approve_dialog.parent();
        this.approve_dialog_textarea = Selector("#bmltwf_submission_approve_dialog_textarea");
        this.approve_close_dialog = Selector("#bmltwf_submission_approve_close_dialog");
        this.approve_close_dialog_parent = this.approve_close_dialog.parent();
        this.approve_close_dialog_textarea = Selector("#bmltwf_submission_approve_close_dialog_textarea");
        this.reject_dialog = Selector("#bmltwf_submission_reject_dialog");
        this.reject_dialog_parent = this.reject_dialog.parent();
        this.reject_dialog_textarea = Selector("#bmltwf_submission_reject_dialog_textarea");
        this.quickedit_dialog = Selector("#bmltwf_submission_quickedit_dialog");
        // quickedit fields
        this.quickedit_meeting_name = Selector("#quickedit_meeting_name");
        this.quickedit_format_shared_id_list = Selector("#quickedit_format_shared_id_list");
        this.quickedit_start_time = Selector("#quickedit_start_time");
        this.quickedit_duration_hours = Selector("#quickedit_duration_hours");
        this.quickedit_duration_minutes = Selector("#quickedit_duration_minutes");
        this.quickedit_virtual_meeting_additional_info = Selector("#quickedit_virtual_meeting_additional_info");
        this.quickedit_phone_meeting_number = Selector("#quickedit_phone_meeting_number");
        this.quickedit_virtual_meeting_link = Selector("#quickedit_virtual_meeting_link");
        this.quickedit_additional_info = Selector("#quickedit_additional_info");
        this.quickedit_venue_type = Selector("#quickedit_venue_type");
        this.quickedit_weekday_tinyint = Selector("#quickedit_weekday_tinyint");
        this.quickedit_location_text = Selector("#quickedit_location_text");
        this.quickedit_location_street = Selector("#quickedit_location_street");
        this.quickedit_location_info = Selector("#quickedit_location_info");
        this.quickedit_location_municipality = Selector("#quickedit_location_municipality");
        this.quickedit_location_sub_province = Selector("#quickedit_location_sub_province");
        this.quickedit_location_sub_province_select = Selector("select#quickedit_location_sub_province");
        this.quickedit_location_province = Selector("#quickedit_location_province");
        this.quickedit_location_province_select = Selector("select#quickedit_location_province");
        this.quickedit_location_postal_code_1 = Selector("#quickedit_location_postal_code_1");
        this.quickedit_location_nation = Selector("#quickedit_location_nation");
        this.quickedit_latitude = Selector("#quickedit_latitude");
        this.quickedit_longitude = Selector("#quickedit_longitude");
        
        this.quickedit_dialog_parent = this.quickedit_dialog.parent();
        this.optional_auto_geocode_enabled = Selector("#optional_auto_geocode_enabled");
    }
}

export const as = new Admin_Submissions();