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

class Meeting_Update_Form {
    constructor () {

        this.form_replace = Selector('#form_replace');
        this.meeting_update_form      = Selector('#meeting_update_form');
        this.update_reason = Selector('#update_reason');
        this.meeting_selector = Selector('#meeting_selector');
        this.meeting_selector_clickable = Selector('span .meeting_selector-select2');
        this.meeting = Selector('#meeting');
        this.meetinginput       = Selector('#meeting_input');
        this.meeting_id = Selector('#meeting_id');
        this.meeting_content = Selector('#meeting_content');
        this.instructions = Selector('#instructions');
        this.personal_details = Selector('#personal_details');
        this.first_name = Selector('#first_name');
        this.last_name = Selector('#last_name');
        this.email_address = Selector('#email_address');
        this.add_contact = Selector('#add_contact');
        this.contact_number = Selector('#contact_number');
        this.group_relationship = Selector('#group_relationship');
        this.virtual_meeting_options = Selector('#virtual_meeting_options');
        this.venueType = Selector('#venueType');
        this.virtual_meeting_settings = Selector('#virtual_meeting_settings');
        this.virtual_meeting_link = Selector('#virtual_meeting_link');
        this.virtual_meeting_additional_info = Selector('#virtual_meeting_additional_info');
        this.phone_meeting_number = Selector('#phone_meeting_number');
        this.meeting_details = Selector('#meeting_details');
        this.name = Selector('#name');
        this.day = Selector('#day');
        this.startTime = Selector('#startTime');
        this.duration_hours = Selector('#duration_hours');
        this.duration_minutes = Selector('#duration_minutes');
        this.duration = Selector('#duration');
        this.display_formatIds = Selector('#display_formatIds');
        this.format_list_clickable = Selector('.select2-selection--multiple');
        this.formatIds = Selector('#formatIds');
        this.display_formatIds_label = Selector('#display_formatIds_label')
        this.published = Selector('#published');
        this.location_text = Selector('#location_text');
        this.location_street = Selector('#location_street');
        this.location_info = Selector('#location_info');
        this.location_municipality = Selector('#location_municipality');
        this.optional_location_sub_province = Selector('#optional_location_sub_province');
        this.optional_location_province = Selector('#optional_location_province');
        this.location_nation = Selector('#location_nation');
        this.location_nation_label = Selector('#location_nation_label');
        this.location_sub_province = Selector('#location_sub_province');
        this.location_sub_province_label = Selector('#location_sub_province_label');
        this.location_province = Selector('#location_province');
        this.location_province_label = Selector('#location_province_label');
        this.location_postal_code_1 = Selector('#location_postal_code_1');
        this.location_postal_code_1_label = Selector('#location_postal_code_1_label');
        this.optional_location_nation = Selector('#optional_location_nation');
        this.serviceBodyId = Selector('#serviceBodyId');
        this.additional_info_div = Selector('#additional_info_div');
        this.additional_info = Selector('#additional_info');
        this.starter_pack = Selector('#starter_pack');
        this.starter_kit_required = Selector('#starter_kit_required');
        this.starter_kit_postal_address_div = Selector('#starter_kit_postal_address_div');
        this.starter_kit_postal_address = Selector('#starter_kit_postal_address');
        this.submit = Selector('#submit');
        this.success_page_header = Selector('#bmltwf_response_message');
        this.error_para = Selector('.bmltwf-error-message + .notice p');
        // jquery validate error in virtual meeting details
        this.groupName_error = Selector("#groupName-error");
    }
}

export const uf = new Meeting_Update_Form();