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
import { ct } from "./models/crouton";
import { ao } from "./models/admin_options";
import { Selector, Role } from "testcafe";

import { reset_bmlt, 
  bmlt_states_off, 
  configure_service_bodies, 
  delete_submissions, 
  click_table_row_column, 
  click_dt_button_by_index, 
  click_dialog_button_by_index, 
  select_dropdown_by_text, 
  select_dropdown_by_value, 
  bw_admin, 
  basic_options } from "./helpers/helper.js";
  
import { userVariables } from "../../.testcaferc";

fixture`e2e_test_fixture`
  // .page(userVariables.admin_submissions_page)
  .beforeEach(async (t) => {

    await reset_bmlt(t);
    await bmlt_states_off(t);

    await basic_options(t);

    await delete_submissions(t);

    await configure_service_bodies(t);


  });

test("Submit_New_Meeting_And_Approve_And_Verify", async (t) => {
  var meeting = {
    location_text: "the church",
    location_street: "105 avoca street",
    location_info: "info",
    location_municipality: "randwick",
    location_province: "nsw",
    location_postal_code_1: "2032",
  };

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
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number_confidential, "`12345`");

  // email dropdown
  await select_dropdown_by_text(uf.add_email, "Yes");
  await t.expect(uf.add_email.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  var meeting = {
    meeting_name: "my test meeting 99999",
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
  await select_dropdown_by_value(uf.virtual_hybrid_select, "hybrid");
  await t
    .expect(uf.virtual_hybrid_select.value)
    .eql("hybrid")
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
  await t.typeText(uf.meeting_name, meeting.meeting_name);

  await select_dropdown_by_text(uf.weekday_tinyint, "Monday");

  await t.typeText(uf.start_time, "10:40");

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

  await select_dropdown_by_text(uf.service_body_bigint, "a-level1");
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
  await t.useRole(bw_admin).navigateTo(userVariables.admin_submissions_page);

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
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved");

  // check meeting shows up in crouton
  await t.useRole(Role.anonymous()).navigateTo(userVariables.crouton_page);

  await t.dispatchEvent(ct.groups_dropdown, "mousedown", { which: 1 });

  await t.typeText(Selector('input[class="select2-search__field"]'), "99999");
  await t.pressKey("enter");

  // var meeting = {
  //     meeting_name: 'my test meeting 99999',
  //     location_text: 'the church',
  //     location_street: '105 avoca street',
  //     location_info: 'info',
  //     location_municipality: 'randwick',
  //     location_province: 'nsw',
  //     location_postal_code_1: '2032',
  //     phone_meeting_number: '+61 1800 253430 code #8303782669',
  //     virtual_meeting_link: 'https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09',
  //     virtual_meeting_additional_info: 'Zoom ID 83037287669 Passcode: testing'
  //     };

  await t
    .expect(ct.meeting_name.innerText)
    .eql(meeting.meeting_name)
    .expect(ct.location_text.innerText)
    .eql(meeting.location_text)
    .expect(ct.phone_meeting_number.innerText)
    .eql(meeting.phone_meeting_number)
    .expect(ct.virtual_meeting_link.innerText)
    .eql(meeting.virtual_meeting_link);
});

test("Submit_Change_Meeting_And_Approve_And_Verify", async (t) => {
  await t.navigateTo(userVariables.formpage);

  await select_dropdown_by_value(uf.update_reason, "reason_change");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "virtualmeeting");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true).expect(uf.meeting_details.visible).eql(true).expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number_confidential, "`12345`")

    .typeText(uf.meeting_name, "update")
    // make sure highlighting is present
    .expect(uf.meeting_name.hasClass("bw-changed"))
    .ok();

  // email dropdown
  await select_dropdown_by_text(uf.add_email, "Yes");
  await t.expect(uf.add_email.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  await t.typeText(uf.additional_info, "my additional info");

  await t
    .click(uf.submit)
    .expect(Selector("#bw_response_message").innerText)
    .match(/submission\ successful/);

  // switch to admin page
  await t.useRole(bw_admin).navigateTo(userVariables.admin_submissions_page);

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
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved");

  // check meeting shows up in crouton
  await t.useRole(Role.anonymous()).navigateTo(userVariables.crouton_page);

  await t.dispatchEvent(ct.groups_dropdown, "mousedown", { which: 1 });

  await t.typeText(Selector('input[class="select2-search__field"]'), "virtualmeeting");
  await t.pressKey("enter");

  await t.expect(ct.meeting_name.innerText).eql("virtualmeeting randwickupdate");
});
