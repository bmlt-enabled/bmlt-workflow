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

import { as } from "./models/admin_submissions";
import { uf } from "./models/meeting_update_form";

import { Selector } from "testcafe";

import {
  restore_from_backup,
  select_dropdown_by_text,
  select_dropdown_by_value,
  click_table_row_column,
  click_dt_button_by_index,
  click_dialog_button_by_index,
  waitfor,
  myip,
  bmltwf_admin,
  set_language_single
} from "./helpers/helper.js";

import { userVariables } from "../../.testcaferc";

fixture`geocoding_tests_fixture`
  .before(async (t) => {})
  .beforeEach(async (t) => {

    // geocoding disabled on port 3002
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3002","hidden");
    await set_language_single(t, "en_EN");

  });

test("Submit_New_Meeting_And_Approve_With_Geocoding_Disabled", async (t) => {
  var meeting = {
    location_text: "the church",
    location_street: "105 avoca street",
    location_info: "info",
    location_municipality: "randwick",
    location_province: "nsw",
    location_postal_code_1: "2032",
  };

  await waitfor(userVariables.admin_logon_page_single);
  await t.navigateTo(userVariables.formpage);

  await select_dropdown_by_value(uf.update_reason, "reason_new");

  // check our divs are visible
  await t
    .expect(uf.update_reason.value)
    .eql("reason_new")

    // validate form is laid out correctly
    .expect(uf.personal_details.visible)
    .eql(true)
    .expect(uf.meeting_details.visible)
    .eql(true)
    .expect(uf.additional_info_div.visible)
    .eql(true);
  // personal details
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number, "123-456-7890");

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  var meeting = {
    name: "my test meeting 99999",
    location_text: "the church",
    location_street: "105 avoca street",
    location_info: "info",
    location_municipality: "randwick",
    location_province: "nsw",
    location_postal_code_1: "2032",
    phone_meeting_number: "+61 1800 253430 code #8303782669",
    virtual_meeting_link: "https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09",
    virtual_meeting_additional_info: "Zoom ID 83037287669 Passcode: testing",
  };

  // virtual meeting settings
  await select_dropdown_by_value(uf.venueType, "3");
  await t
    .expect(uf.venueType.value)
    .eql("3")
    .expect(uf.virtual_meeting_link.visible)
    .eql(true)
    .expect(uf.phone_meeting_number.visible)
    .eql(true)
    .expect(uf.virtual_meeting_additional_info.visible)
    .eql(true);

  await t
    .typeText(uf.phone_meeting_number, meeting.phone_meeting_number)
    .typeText(uf.virtual_meeting_link, meeting.virtual_meeting_link)
    .typeText(uf.virtual_meeting_additional_info, meeting.virtual_meeting_additional_info);

  // meeting settings
  await t.typeText(uf.name, meeting.name);

  await select_dropdown_by_text(uf.day, "Monday");

  await t.typeText(uf.startTime, "10:40");

  await select_dropdown_by_value(uf.duration_hours, "04");
  await select_dropdown_by_value(uf.duration_minutes, "30");

  // format list
  await t.click(uf.format_list_clickable).pressKey("b e g enter").click(uf.format_list_clickable).pressKey("l i n enter");

  await t

    .typeText(uf.location_text, meeting.location_text)
    .typeText(uf.location_street, meeting.location_street)
    .typeText(uf.location_info, meeting.location_info)
    .typeText(uf.location_municipality, meeting.location_municipality)
    .typeText(uf.location_province, meeting.location_province)
    .typeText(uf.location_postal_code_1, meeting.location_postal_code_1);

  await select_dropdown_by_text(uf.serviceBodyId, "Mid-Hudson Area Service");
  await t.typeText(uf.additional_info, "my additional info");

  await select_dropdown_by_value(uf.starter_kit_required, "yes");
  await t
    // .typeText(uf.starter_kit_postal_address, 'postal address')
    .typeText(uf.starter_kit_postal_address, "postal address")
    .expect(uf.starter_kit_postal_address.value)
    .eql("postal address");

  await t
    .click(uf.submit)
    .expect(uf.success_page_header.innerText)
    .match(/submission\ successful/);

  // switch to admin page
  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);

  // new meeting = row 0
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  await click_dt_button_by_index(as.dt_submission_wrapper, 0);

  await t.expect(as.approve_dialog_parent.visible).eql(true);

  await t.typeText(as.approve_dialog_textarea, "I approve this request");
  // press ok button
  await click_dialog_button_by_index(as.approve_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.approve_dialog_parent.visible).eql(false);

  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).notContains('None', { timeout: 10000 })
  .expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved", {timeout: 10000});

});

test("Submit_Change_Meeting_And_Approve_With_Geocoding_Disabled", async (t) => {
  //console.log("hi1");
  await t.navigateTo(userVariables.formpage);

  await select_dropdown_by_value(uf.update_reason, "reason_change");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "right");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true).expect(uf.meeting_details.visible).eql(true).expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number, "12345")
    .typeText(uf.location_text, "location")

    .typeText(uf.name, "update", { replace: true })
    // make sure highlighting is present
    .expect(uf.name.hasClass("bmltwf-changed"))
    .ok();

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  await t.typeText(uf.additional_info, "my additional info");
  await t
    .click(uf.submit)
    .expect(Selector("#bmltwf_response_message").innerText)
    .match(/submission\ successful/);

  // switch to admin page
  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);

  // new meeting = row 0
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  await click_dt_button_by_index(as.dt_submission_wrapper, 0);

  await t.expect(as.approve_dialog_parent.visible).eql(true);

  await t.typeText(as.approve_dialog_textarea, "I approve this request");
  // press ok button
  await click_dialog_button_by_index(as.approve_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.approve_dialog_parent.visible).eql(false);

  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).notContains('None', { timeout: 10000 })
  .expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved", {timeout: 10000});

});

test("Approve_New_Meeting_No_Geocoding", async (t) => {

  // await t.eval(() => location.reload(true));
  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);

  // new meeting = row 2
  var row = 2;
  await click_table_row_column(as.dt_submission, row, 0);

  // quickedit
  await click_dt_button_by_index(as.dt_submission_wrapper, 2);
  // geocode div should be invisible
  await t.expect(as.optional_auto_geocode_enabled.visible).eql(false);

  // // check the geocode button is disabled
  var g = as.quickedit_dialog_parent.find("button.ui-corner-all").nth(1);
  await t.expect(g.withAttribute("disabled").exists).ok();
});