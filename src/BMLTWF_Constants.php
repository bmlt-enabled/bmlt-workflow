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

trait BMLTWF_Constants
{

    public $bmltwf_capability_manage_submissions = 'bmltwf_manage_submissions';

    // option list so we can back them up
    public $bmltwf_options = array(
        'bmltwf_db_version',
        'bmltwf_bmlt_server_address',
        'bmltwf_bmlt_username',
        'bmltwf_bmlt_password',
        'bmltwf_bmlt_test_status',
        'bmltwf_submitter_email_template',
        'bmltwf_required_meeting_formats',
        'bmltwf_optional_location_sub_province',
        'bmltwf_optional_location_sub_province_displayname',
        'bmltwf_optional_location_province',
        'bmltwf_optional_location_province_displayname',
        'bmltwf_optional_location_nation',
        'bmltwf_optional_location_nation_displayname',
        'bmltwf_optional_postcode',
        'bmltwf_optional_postcode_displayname',
        'bmltwf_delete_closed_meetings',
        'bmltwf_email_from_address',
        'bmltwf_fso_email_template',
        'bmltwf_fso_email_address',
        'bmltwf_submitter_email_template',
        'bmltwf_fso_feature',
        'bmltwf_trusted_servants_can_delete_submissions',
        'bmltwf_remove_virtual_meeting_details_on_venue_change',
        'bmltwf_google_maps_key'
    );

    public $bmltwf_rest_namespace = 'bmltwf/v1';
    public $bmltwf_submissions_rest_base = 'submissions';
    public $bmltwf_service_bodies_rest_base = 'servicebodies';
    public $bmltwf_bmltserver_rest_base = 'bmltserver';
    public $bmltwf_options_rest_base = 'options';

}
