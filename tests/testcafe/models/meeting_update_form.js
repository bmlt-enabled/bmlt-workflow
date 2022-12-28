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
        this.add_email = Selector('#add_email');
        this.contact_number_confidential = Selector('#contact_number_confidential');
        this.group_relationship = Selector('#group_relationship');
        this.virtual_meeting_options = Selector('#virtual_meeting_options');
        this.venue_type = Selector('#venue_type');
        this.virtual_meeting_settings = Selector('#virtual_meeting_settings');
        this.virtual_meeting_link = Selector('#virtual_meeting_link');
        this.virtual_meeting_additional_info = Selector('#virtual_meeting_additional_info');
        this.phone_meeting_number = Selector('#phone_meeting_number');
        this.meeting_details = Selector('#meeting_details');
        this.meeting_name = Selector('#meeting_name');
        this.weekday_tinyint = Selector('#weekday_tinyint');
        this.start_time = Selector('#start_time');
        this.duration_hours = Selector('#duration_hours');
        this.duration_minutes = Selector('#duration_minutes');
        this.duration_time = Selector('#duration_time');
        this.display_format_shared_id_list = Selector('#display_format_shared_id_list');
        this.format_list_clickable = Selector('.select2-selection--multiple');
        this.format_shared_id_list = Selector('#format_shared_id_list');
        this.display_format_shared_id_list_label = Selector('#display_format_shared_id_list_label')
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
        this.service_body_bigint = Selector('#service_body_bigint');
        this.additional_info_div = Selector('#additional_info_div');
        this.additional_info = Selector('#additional_info');
        this.starter_pack = Selector('#starter_pack');
        this.starter_kit_required = Selector('#starter_kit_required');
        this.starter_kit_postal_address_div = Selector('#starter_kit_postal_address_div');
        this.starter_kit_postal_address = Selector('#starter_kit_postal_address');
        this.submit = Selector('#submit');
        this.success_page_header = Selector('#bmltwf_response_message');
        this.error_para = Selector('.bmltwf-error-message + .notice p');
    }
}

export const uf = new Meeting_Update_Form();