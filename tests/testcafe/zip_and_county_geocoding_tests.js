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

import { as } from "./models/admin_submissions.js";
import { uf } from "./models/meeting_update_form.js";
import { ao } from "./models/admin_options.js";
import { Selector } from "testcafe";

import {
  restore_from_backup,
  check_checkbox,
  uncheck_checkbox,
  click_table_row_column,
  click_dt_button_by_index,
  myip,
  bmltwf_admin,
  set_language_single
} from "./helpers/helper.js";

import { userVariables } from "../../.testcaferc.js";

fixture`zip_and_county_geocoding_tests_fixture`
  .before(async (t) => {})
  .beforeEach(async (t) => {

    // geocoding disabled on port 3002
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3005","hidden");
    await set_language_single(t, "en_EN");

  });

test("Pick_Up_Zip_And_County_Geocoding_Setting", async (t) => {

  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
  await t
    .expect(ao.bmltwf_auto_geocoding_settings_text.innerText).contains("Zip codes will be automatically added from geocoding results on save")
    .expect(ao.bmltwf_auto_geocoding_settings_text.innerText).contains("County will be automatically added from geocoding results on save")

});

test("Submit_New_Meeting_And_Check_Zip_And_County_Geolocation", async (t) => {
  // switch to admin page
  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);
  await t.click(ao.submit);
  await ao.settings_updated();

  await t.navigateTo(userVariables.admin_submissions_page_single);
  // new meeting = row 1
  var row = 1;
  await click_table_row_column(as.dt_submission, row, 0);
  // quickedit

  await click_dt_button_by_index(as.dt_submission_wrapper, 3);
  await t.expect(as.quickedit_location_postal_code_1.withAttribute("disabled").exists).ok()
    .expect(as.quickedit_location_sub_province.withAttribute("disabled").exists).ok()
    // Click geolocate button
    .click(as.quickedit_dialog_parent.find("button.ui-corner-all").nth(1))
    .expect(as.quickedit_location_sub_province.value).eql("Columbia County")
    .drag(".yNHHyP-marker-view",50,50)
    .expect(as.quickedit_latitude.value).eql("42.25078842999364")
    .typeText(as.quickedit_location_municipality, "Cobble Hill", { replace: true})
    .click(as.quickedit_dialog_parent.find("button.ui-corner-all").nth(1))
    .expect(as.quickedit_location_sub_province.value).eql("Kings County")
    .expect(as.quickedit_location_postal_code_1.value).eql('11201')
});

test("Check_Optional_County_Hidden_Even_When_Geolocation_On", async (t) => {
  // switch to admin page
  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);
  await t.click(ao.submit);
  await ao.settings_updated();

  await t.navigateTo(userVariables.admin_submissions_page_single);
  // new meeting = row 1
  var row = 1;
  await click_table_row_column(as.dt_submission, row, 0);
  // quickedit

  await click_dt_button_by_index(as.dt_submission_wrapper, 3);
  await t.expect(as.optional_location_sub_province.visible).eql(false);
});