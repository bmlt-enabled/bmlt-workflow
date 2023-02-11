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
import { Selector, Role } from "testcafe";

import { reset_bmlt3x, 
  click_table_row_column, 
  click_dt_button_by_index, 
  click_dialog_button_by_index, 
  select_dropdown_by_text, 
  select_dropdown_by_value, 
  waitfor,
  restore_from_backup,
  crouton3x,
  bmltwf_admin_multisingle } from "./helpers/helper.js";
  
import { userVariables } from "../../.testcaferc";

fixture`bmlt3x_multisite_single_e2e_test_fixture`
  // .page(userVariables.admin_submissions_page_single)
  .before(async(t)=> {
  })
  .beforeEach(async (t) => {
    await reset_bmlt3x(t);
    await crouton3x(t);
    await waitfor(userVariables.admin_logon_page_multisingle);
    await restore_from_backup(bmltwf_admin_multisingle, userVariables.admin_settings_page_multisingle_plugin, userVariables.admin_restore_json_multisingle_plugin,"bmlt3x","8001");
  });

test("MultiSite_Single_Submit_New_Meeting_And_Approve_And_Verify", async (t) => {
  var meeting = {
    location_text: "the church",
    location_street: "105 avoca street",
    location_info: "info",
    location_municipality: "randwick",
    location_province: "nsw",
    location_postal_code_1: "2032",
  };
  // console.log(userVariables.formpage_multisingle);

  await t.navigateTo(userVariables.formpage_multisingle);
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
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number, "`12345`");

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

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
  await select_dropdown_by_value(uf.venue_type, "3");
  await t
    .expect(uf.venue_type.value)
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

  await select_dropdown_by_text(uf.service_body_bigint, "Mid-Hudson Area Service");
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
  await t.useRole(bmltwf_admin_multisingle).navigateTo(userVariables.admin_submissions_page_multisingle_plugin);

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

test("Multisite_Single_Submit_Change_Meeting_And_Approve_And_Verify", async (t) => {
  // await t.debug();
  await t.navigateTo(userVariables.formpage_multisingle);

  // console.log(userVariables.formpage_multisingle);

  await select_dropdown_by_value(uf.update_reason, "reason_change");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "chance");
  await t.pressKey("enter");
  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true).expect(uf.meeting_details.visible).eql(true).expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number, "`12345`")
    .typeText(uf.location_text, "location")

    .typeText(uf.meeting_name, "update", { replace: true })
    // make sure highlighting is present
    .expect(uf.meeting_name.hasClass("bmltwf-changed"))
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

    // await t.debug();
  // switch to admin page
  await t.useRole(bmltwf_admin_multisingle).navigateTo(userVariables.admin_submissions_page_multisingle_plugin);

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

  // check meeting shows up in crouton
  await t.useRole(Role.anonymous()).navigateTo(userVariables.crouton_page);
  await t.dispatchEvent(ct.groups_dropdown, "mousedown", { which: 1 });

  await t.typeText(Selector('input[class="select2-search__field"]'), "update");
  await t.pressKey("enter");

  await t.expect(ct.meeting_name.innerText).eql("update");
});
