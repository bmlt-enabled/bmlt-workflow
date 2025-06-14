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
import { Selector, Role } from "testcafe";

import {  
  restore_from_backup,
  click_table_row_column, 
  click_dt_button_by_index, 
  click_dialog_button_by_index, 
  select_dropdown_by_text, 
  select_dropdown_by_value, 
  waitfor,
  bmltwf_admin,
  set_language_single,
  myip
   } from "./helpers/helper.js";
  
import { userVariables } from "../../.testcaferc";

fixture`bmlt3x_e2e_test_fixture`
  .before(async (t) => {
  })
  .beforeEach(async (t) => {

    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3001"),"hidden";
    await set_language_single(t, "en_EN");
    await waitfor(userVariables.admin_logon_page_single);
  });

test("Bmlt3x_Submit_New_Meeting_And_Approve", async (t) => {
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
// await t.debug();
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
  await t.typeText(uf.first_name, "first")
  .typeText(uf.last_name, "last")
  .typeText(uf.email_address, "test@test.com.zz")
  .typeText(uf.contact_number, "123-456-7890");

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
  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);
  // await t.useRole(bmltwf_admin_wpsinglebmlt3x).navigateTo(userVariables.admin_submissions_page_wpsinglebmlt3x);

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

test("Bmlt3x_Submit_Change_Meeting_And_Approve", async (t) => {
  await t.navigateTo(userVariables.formpage);

  await select_dropdown_by_value(uf.update_reason, "reason_change");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "matter");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true).expect(uf.meeting_details.visible).eql(true).expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number, "123-456-7890")
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
  // await t.useRole(bmltwf_admin_wpsinglebmlt3x).navigateTo(userVariables.admin_submissions_page_wpsinglebmlt3x);
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

test("Bmlt3x_Submit_Change_Meeting_With_Unpublish_And_Approve", async (t) => {
  await t.navigateTo(userVariables.formpage);

  await select_dropdown_by_value(uf.update_reason, "reason_change");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "insanity");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true).expect(uf.meeting_details.visible).eql(true).expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number, "123-456-7890")
    .typeText(uf.location_text, "location")

    .typeText(uf.name, "update", { replace: true })
    // make sure highlighting is present
    .expect(uf.name.hasClass("bmltwf-changed"))
    .ok();

    // unpublish this meeting
  await select_dropdown_by_value(uf.published, "0");

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

// test("Change_Meeting_Details_Check_Highlighting_And_Check_Submission_Dropdown", async (t) => {

//   await t.navigateTo(userVariables.formpage);

//   await select_dropdown_by_value(uf.update_reason, "reason_change");

//   // check our divs are visible
//   await t.expect(uf.update_reason.value).eql("reason_change");

//   // meeting selector
//   await t.click("#select2-meeting-searcher-container");
//   await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "lifeline");
//   await t.pressKey("enter");

//   // validate form is laid out correctly
//   await t.expect(uf.personal_details.visible).eql(true)
//   .expect(uf.meeting_details.visible).eql(true)
//   .expect(uf.additional_info_div.visible).eql(true);

//   // personal details
//   await t
//     .typeText(uf.first_name, "first")
//     .typeText(uf.last_name, "last")
//     .typeText(uf.email_address, "test@test.com.zz")
//     .typeText(uf.contact_number, "123-456-7890")

//     .typeText(uf.name, "update")
//     // make sure highlighting is present
//     .expect(uf.name.hasClass("bmltwf-changed"))
//     .ok();

//   // virtual meeting settings
//   await select_dropdown_by_value(uf.venueType, "3");
//   await t
//     .expect(uf.venueType.value)
//     .eql("3")
//     .expect(uf.virtual_meeting_link.visible)
//     .eql(true)
//     .expect(uf.phone_meeting_number.visible)
//     .eql(true)
//     .expect(uf.virtual_meeting_additional_info.visible)
//     .eql(true);
//   await t
//     .typeText(uf.phone_meeting_number, "+61 1800 253430 code #8303782669")
//     .expect(uf.phone_meeting_number.hasClass("bmltwf-changed"))
//     .ok()
//     .typeText(uf.virtual_meeting_link, "https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09")
//     .expect(uf.virtual_meeting_link.hasClass("bmltwf-changed"))
//     .ok()
//     .typeText(uf.virtual_meeting_additional_info, "Zoom ID 83037287669 Passcode: testing")
//     .expect(uf.virtual_meeting_additional_info.hasClass("bmltwf-changed"))
//     .ok();

//   // meeting settings

//   // weekday
//   await select_dropdown_by_text(uf.day, "Monday");
//   // await t.debug();
//   await t
//     .expect(uf.day.hasClass("bmltwf-changed"))
//     .ok()
//     // start time
//     .typeText(uf.startTime, "10:40")
//     .expect(uf.startTime.hasClass("bmltwf-changed"))
//     .ok();

//   // duration
//   await select_dropdown_by_value(uf.duration_hours, "09");
//   await t.expect(uf.duration_hours.hasClass("bmltwf-changed")).ok();
//   await select_dropdown_by_value(uf.duration_minutes, "35");
//   await t.expect(uf.duration_minutes.hasClass("bmltwf-changed")).ok();

//   // format list
//   await t.click(uf.format_list_clickable).pressKey("g u i d enter").expect(uf.format_list_clickable.hasClass("bmltwf-changed")).ok();

//   await t
//     .typeText(uf.location_text, "my location")
//     .expect(uf.format_list_clickable.hasClass("bmltwf-changed"))
//     .ok()
//     .typeText(uf.location_street, "110 Avoca Street")
//     .expect(uf.location_street.hasClass("bmltwf-changed"))
//     .ok()
//     .typeText(uf.location_info, "info")
//     .expect(uf.location_info.hasClass("bmltwf-changed"))
//     .ok()
//     .typeText(uf.location_municipality, "Randwick")
//     .expect(uf.location_municipality.hasClass("bmltwf-changed"))
//     .ok()
//     .typeText(uf.location_province, "VIC")
//     .expect(uf.location_province.hasClass("bmltwf-changed"))
//     .ok()
//     .typeText(uf.location_postal_code_1, "2031")
//     .expect(uf.location_postal_code_1.hasClass("bmltwf-changed"))
//     .ok();

//     await select_dropdown_by_value(uf.group_relationship, "Group Member");

//     await t
//     .click(uf.submit)
//     .expect(Selector("#bmltwf_response_message").innerText)
//     .match(/submission\ successful/);

//   // switch to admin page
//   await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);

//     // first row open the dropdown
//     await click_table_row_column(as.dt_submission, 0, 9);
  
// });
