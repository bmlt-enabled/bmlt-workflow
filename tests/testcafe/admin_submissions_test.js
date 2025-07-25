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
import { ao } from "./models/admin_options";

import {
  restore_from_backup, 
  select_dropdown_by_text, 
  click_table_row_column, 
  click_dt_button_by_index, 
  click_dialog_button_by_index, 
  bmltwf_admin, 
  set_language_single,
  myip
   } from "./helpers/helper.js";

import { RequestLogger, Selector } from "testcafe";

import { userVariables } from "../../.testcaferc";

fixture`admin_submissions_fixture`
.beforeEach(async (t) => {

  await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3001","hidden");
  await set_language_single(t, "en_EN");

  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);
});

test("Approve_New_Meeting", async (t) => {
  // new meeting = row 2
  var row = 2;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  await click_dt_button_by_index(as.dt_submission_wrapper, 0);

  await t.expect(as.approve_dialog_parent.visible).eql(true);

  await t.typeText(as.approve_dialog_textarea, "I approve this request");
  // press ok button
  await click_dialog_button_by_index(as.approve_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.approve_dialog_parent.visible).eql(false);
  // await t.debug();

  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).notContains('None', { timeout: 10000 })
  .expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved", {timeout: 10000});
});

test("Approve_Modify_Meeting", async (t) => {
  // modify meeting = row 1
  var row = 1;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  await click_dt_button_by_index(as.dt_submission_wrapper, 0);

  await t.expect(as.approve_dialog_parent.visible).eql(true);

  await t.typeText(as.approve_dialog_textarea, "I approve this request");
  // press ok button
  await click_dialog_button_by_index(as.approve_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.approve_dialog_parent.visible).eql(false);

  // const s = Selector("#dt-submission tr:nth-child(1) td:nth-child(9)");
  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).notContains('None', { timeout: 10000 })
  .expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved", {timeout: 10000});
});

test("Approve_Close_Meeting_With_Unpublish", async (t) => {

  // set it to unpublish
  await t.navigateTo(userVariables.admin_settings_page_single);
  await select_dropdown_by_text(ao.bmltwf_delete_closed_meetings, "Unpublish");
  await t.click(ao.submit);
  await ao.settings_updated();
  await t.navigateTo(userVariables.admin_submissions_page_single);

  // close meeting = row 0
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  await click_dt_button_by_index(as.dt_submission_wrapper, 0);

  await t.expect(as.approve_close_dialog_parent.visible).eql(true);

  await t.typeText(as.approve_close_dialog_textarea, "I approve this request");
  // press ok button
  await click_dialog_button_by_index(as.approve_close_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.approve_close_dialog_parent.visible).eql(false);
  var column = 8;
  // await t.debug();
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).notContains('None', { timeout: 10000 })
  .expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved", {timeout: 10000});
});

test("Approve_Close_Meeting_With_Delete", async (t) => {

  // set it to delete
  await t.navigateTo(userVariables.admin_settings_page_single);
  await select_dropdown_by_text(ao.bmltwf_delete_closed_meetings, "Delete");
  await t.click(ao.submit);
  await ao.settings_updated();
  await t.navigateTo(userVariables.admin_submissions_page_single);

  // close meeting = row 0
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  await click_dt_button_by_index(as.dt_submission_wrapper, 0);
// await t.debug();

  await t.expect(as.approve_close_dialog_parent.visible).eql(true);

  await t.typeText(as.approve_close_dialog_textarea, "I approve this request");
  // press ok button
  await click_dialog_button_by_index(as.approve_close_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.approve_close_dialog_parent.visible).eql(false);
  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).notContains('None', { timeout: 10000 })
  .expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Approved", {timeout: 10000});
});

test("Reject_New_Meeting", async (t) => {
  // new meeting = row 2
  var row = 2;
  await click_table_row_column(as.dt_submission, row, 0);
  // reject
  await click_dt_button_by_index(as.dt_submission_wrapper, 1);

  await t.expect(as.reject_dialog_parent.visible).eql(true);

  await t.typeText(as.reject_dialog_textarea, "I reject this request");
  // press ok button
  await click_dialog_button_by_index(as.reject_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.reject_dialog_parent.visible).eql(false);

  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Rejected");
});

test("Reject_Modify_Meeting", async (t) => {
  // modify meeting = row 1
  var row = 1;
  await click_table_row_column(as.dt_submission, row, 0);
  // reject
  await click_dt_button_by_index(as.dt_submission_wrapper, 1);

  await t.expect(as.reject_dialog_parent.visible).eql(true);

  await t.typeText(as.reject_dialog_textarea, "I reject this request");
  // press ok button
  await click_dialog_button_by_index(as.reject_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.reject_dialog_parent.visible).eql(false);

  // const s = Selector("#dt-submission tr:nth-child(1) td:nth-child(9)");
  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Rejected");
});

test("Reject_Close_Meeting", async (t) => {
  // close meeting = row 0
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  // reject
  await click_dt_button_by_index(as.dt_submission_wrapper, 1);

  await t.expect(as.reject_dialog_parent.visible).eql(true);

  await t.typeText(as.reject_dialog_textarea, "I reject this request");
  // press ok button
  await click_dialog_button_by_index(as.reject_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.reject_dialog_parent.visible).eql(false);

  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Rejected");
});

test("Submission_Buttons_Active_correctly", async (t) => {
  // new meeting = row 2
  var row = 2;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  var g = as.dt_submission_wrapper.find("button").nth(0);
  await t.expect(g.hasAttribute("disabled")).notOk();
  // reject
  g = as.dt_submission_wrapper.find("button").nth(1);
  await t.expect(g.hasAttribute("disabled")).notOk();
  // quickedit
  g = as.dt_submission_wrapper.find("button").nth(2);
  await t.expect(g.hasAttribute("disabled")).notOk();

  // change meeting = row 1
  var row = 1;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  g = as.dt_submission_wrapper.find("button").nth(0);
  await t.expect(g.hasAttribute("disabled")).notOk();
  // reject
  g = as.dt_submission_wrapper.find("button").nth(1);
  await t.expect(g.hasAttribute("disabled")).notOk();
  // quickedit
  g = as.dt_submission_wrapper.find("button").nth(2);
  await t.expect(g.hasAttribute("disabled")).notOk();

  // close meeting = row 0
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  g = as.dt_submission_wrapper.find("button").nth(0);
  await t.expect(g.hasAttribute("disabled")).notOk();
  // reject
  g = as.dt_submission_wrapper.find("button").nth(1);
  await t.expect(g.hasAttribute("disabled")).notOk();
  // quickedit
  g = as.dt_submission_wrapper.find("button").nth(2);
  await t.expect(g.hasAttribute("disabled")).ok();

  // reject a request then we check the buttons again
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  await click_table_row_column(as.dt_submission, row, 0);
  // reject
  await click_dt_button_by_index(as.dt_submission_wrapper, 1);

  await t.expect(as.reject_dialog_parent.visible).eql(true);

  await t.typeText(as.reject_dialog_textarea, "I reject this request");
  // press ok button
  await click_dialog_button_by_index(as.reject_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(as.reject_dialog_parent.visible).eql(false);

  var column = 8;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("Rejected");

  // rejected request has no approve, reject, quickedit
  // close meeting = row 0
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);
  // approve
  g = as.dt_submission_wrapper.find("button").nth(0);
  await t.expect(g.hasAttribute("disabled")).ok();
  // reject
  g = as.dt_submission_wrapper.find("button").nth(1);
  await t.expect(g.hasAttribute("disabled")).ok();
  // quickedit
  g = as.dt_submission_wrapper.find("button").nth(2);
  await t.expect(g.hasAttribute("disabled")).ok();
});

test('Quickedit_New_Meeting', async t => {

await t.useRole(bmltwf_admin);

    // new meeting = row 2
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    // quickedit
    await click_dt_button_by_index(as.dt_submission_wrapper,2);

    await t
    .expect(as.quickedit_dialog_parent.visible).eql(true)

    // '{"name":"my test meeting",
    // "startTime":"10:40",
    // "duration":"04:30",
    // "location_text":"my location",
    // "location_street":"110 avoca st",
    // "location_info":"info",
    // "location_municipality":"Randwick",
    // "location_province":"NSW",
    // "location_postal_code_1":"2031",
    // "day":"4",
    // "serviceBodyId":1009,
    // "formatIds":"2,5",
    // "contact_number":"12345",
    // "group_relationship":"Group Member",
    // "add_contact":"yes",
    // "additional_info":"some extra info",
    // "virtual_meeting_additional_info":"Zoom ID 83037287669 Passcode: testing",
    // "phone_meeting_number":"12345",
    // "virtual_meeting_link":"https:\\\/\\\/us02web.zoom.us\\\/j\\\/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09",
    // "starter_kit_required":"no",
    // "venueType":3}',
    .expect(as.quickedit_name.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_name.value).eql("my test meeting")
    .expect(as.quickedit_startTime.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_startTime.value).eql("10:40")
    .expect(as.quickedit_day.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_day.value).eql("4")
    .expect(as.quickedit_duration_hours.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_duration_hours.value).eql("04")
    .expect(as.quickedit_duration_minutes.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_duration_minutes.value).eql("30")
    .expect(as.quickedit_virtual_meeting_additional_info.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_virtual_meeting_additional_info.value).eql("Zoom ID 83037287669 Passcode: testing")
    .expect(as.quickedit_phone_meeting_number.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_phone_meeting_number.value).eql("12345")
    .expect(as.quickedit_virtual_meeting_link.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_virtual_meeting_link.value).eql("https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09")
    .expect(as.quickedit_additional_info.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_additional_info.value).eql("some extra info")
    .expect(as.quickedit_venueType.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_venueType.value).eql("3")
    .expect(as.quickedit_location_text.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_location_text.value).eql("my location")
    .expect(as.quickedit_location_street.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_location_street.value).eql("110 avoca st")
    .expect(as.quickedit_location_info.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_location_info.value).eql("info")
    .expect(as.quickedit_location_municipality.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_location_municipality.value).eql("Randwick")
    .expect(as.quickedit_location_province.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_location_province.value).eql("NSW")
    .expect(as.quickedit_location_postal_code_1.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_location_postal_code_1.value).eql("2031")
    // we dont have sub province or nation visible so these should not be shown
    .expect(as.quickedit_location_sub_province.visible).eql(false)
    .expect(as.quickedit_location_nation.visible).eql(false)
});

test('Quickedit_Change_Meeting', async t => {

  await t.useRole(bmltwf_admin);

    // change meeting = row 1
    var row = 1;
    await click_table_row_column(as.dt_submission,row,0);
    // quickedit
    await click_dt_button_by_index(as.dt_submission_wrapper,2);

    await t
    .expect(as.quickedit_dialog_parent.visible).eql(true)
    // changes_requested: '{
    // "name":"update",
    // "original_name":"2nd Chance Group",
    // "original_startTime":"18:30:00",
    // "original_duration":"01:30:00",
    // "location_text":"update location",
    // "original_location_street":"360 Warren Street",
    // "original_location_municipality":"Hudson",
    // "original_location_province":"NY",
    // "original_location_nation":"US",
    // "original_location_sub_province":"Columbia",
    // "original_day":"3",
    // "original_serviceBodyId":"1009",
    // "original_formatIds":"3,17,36",
    // "original_venueType":"1",
    // "contact_number":"12345",
    // "group_relationship":"Group Member",
    // "add_contact":"yes",
    // "additional_info":"please action asap"}',

    // these changed
    .expect(as.quickedit_name.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_name.value).eql("update")
    .expect(as.quickedit_additional_info.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_additional_info.value).eql("please action asap")
    .expect(as.quickedit_location_text.hasClass("bmltwf-changed")).ok()
    .expect(as.quickedit_location_text.value).eql("update location")
    // these didnt change
    .expect(as.quickedit_startTime.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_day.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_duration_hours.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_duration_minutes.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_virtual_meeting_additional_info.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_phone_meeting_number.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_virtual_meeting_link.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_venueType.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_location_street.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_location_info.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_location_municipality.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_location_province.hasClass("bmltwf-changed")).notOk()
    .expect(as.quickedit_location_postal_code_1.hasClass("bmltwf-changed")).notOk()
    // we dont have sub province or nation visible so these should not be shown
    .expect(as.quickedit_location_sub_province.visible).eql(false)
    .expect(as.quickedit_location_nation.visible).eql(false)
  });


test('Quickedit_States_Dropdowns', async t => {
  await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3003","display");
  await set_language_single(t, "en_EN");
  await t.navigateTo(userVariables.admin_submissions_page_single);
  // change meeting = row 1
  var row = 1;
  await click_table_row_column(as.dt_submission,row,0);
  // quickedit
  await click_dt_button_by_index(as.dt_submission_wrapper,2);
  await t
  .expect(as.quickedit_dialog_parent.visible).eql(true)
  .expect(as.quickedit_location_sub_province.hasClass("bmltwf-changed")).notOk()
  .expect(as.quickedit_location_sub_province.value).eql("Columbia")
  .expect(as.quickedit_location_province.hasClass("bmltwf-changed")).notOk()
  .expect(as.quickedit_location_province.value).eql("NY")
  // make sure they are select dropdowns
  .expect(as.quickedit_location_sub_province_select.exists).eql(true)
  .expect(as.quickedit_location_province_select.exists).eql(true)
  // make sure sub province is even showing up
  .expect(as.quickedit_location_sub_province.visible).eql(true)

});

const submissionslogger = RequestLogger(/bmltwf\/v1\/submissions\/93/,
{
  logRequestBody: true,
}
);

test('Quickedit_Saves_No_Changes_Correctly', async t => {

    // new meeting = row 2
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    // quickedit
    await click_dt_button_by_index(as.dt_submission_wrapper,2);

    await t
    .expect(as.quickedit_dialog_parent.visible).eql(true)
    await t.click(as.quickedit_dialog_parent.find("button.ui-corner-all").nth(2))
    .wait(1000);

    var f = JSON.parse(submissionslogger.requests[0].request.body.toString());
    // console.log(f.changes_requested.name);
    await t.expect(f.changes_requested.name).eql("my test meeting");

}).requestHooks(submissionslogger);

const submissions2logger = RequestLogger(/bmltwf\/v1\/submissions\/94/,
{
  logRequestBody: true,
}
);

test('Quickedit_Hides_Virtual_Meeting_Publish', async t => {

  // change meeting = row 1
  var row = 1;
  await click_table_row_column(as.dt_submission,row,0);
  // quickedit
  await click_dt_button_by_index(as.dt_submission_wrapper,2);

  await t
  .expect(as.quickedit_dialog_parent.visible).eql(true)
  .expect(as.quickedit_virtualna_published.visible).eql(false)
  await t.click(as.quickedit_dialog_parent.find("button.ui-corner-all").nth(2))
  .wait(1000);

  var f = JSON.parse(submissions2logger.requests[0].request.body.toString());
  await t.expect(f.changes_requested.virtualna_published).typeOf("undefined");

}).requestHooks(submissions2logger);

const submissions1logger = RequestLogger(/bmltwf\/v1\/submissions\/93/,
{
logRequestBody: true,
}
);

test('Quickedit_Saves_Changes_Correctly', async t => {

    // new meeting = row 2
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    // quickedit
    await click_dt_button_by_index(as.dt_submission_wrapper,2);

    await t
    .expect(as.quickedit_dialog_parent.visible).eql(true)
    .typeText(as.quickedit_location_info,'garbage', {replace: true})
    .typeText(as.quickedit_virtual_meeting_additional_info,'meetinginfo', {replace: true})
    .typeText(as.quickedit_virtual_meeting_link,'meetinglink', {replace: true})
    await t.click(as.quickedit_dialog_parent.find("button.ui-corner-all").nth(2))
    .wait(1000);

    var f = JSON.parse(submissions1logger.requests[0].request.body.toString());
    // console.log(f.changes_requested);
    await t.expect(f.changes_requested.location_info).eql("garbage")
    .expect(f.changes_requested.virtual_meeting_additional_info).eql("meetinginfo")
    .expect(f.changes_requested.virtual_meeting_link).eql("meetinglink");

}).requestHooks(submissions1logger);

test("Approve_New_Meeting_Geocoding", async (t) => {

  // new meeting = row 2
  var row = 2;
  await click_table_row_column(as.dt_submission, row, 0);

  // quickedit
  await click_dt_button_by_index(as.dt_submission_wrapper,2);
  // geocode div should be visible
  await t.expect(as.optional_auto_geocode_enabled.visible).eql(true)

  // check the geocode button is enabled
  await t.expect((as.quickedit_dialog_parent).find("button.ui-corner-all").nth(1).hasAttribute("disabled")).notOk();
});

const formatIdsLogger = RequestLogger(/bmltwf\/v1\/submissions\/93/,
{
  logRequestBody: true,
}
);

test('Quickedit_JSON_Format_Validation', async t => {
  // Select a meeting and open quickedit
  const row = 2; // new meeting
  await click_table_row_column(as.dt_submission, row, 0);
  await click_dt_button_by_index(as.dt_submission_wrapper, 2);
  
  // Make some changes to ensure fields are marked as changed
  await t
    .expect(as.quickedit_dialog_parent.visible).eql(true)
    // Change format IDs to test array handling
    .click(as.quickedit_formatIds)
    .click(Selector('li[id^="select2-quickedit_formatIds-result"]').withText('(B)-Beginners'))
    .click(as.quickedit_formatIds)
    .click(Selector('li[id^="select2-quickedit_formatIds-result"]').withText('(C)-Closed'))
    .click(as.quickedit_formatIds)
    .click(Selector('li[id^="select2-quickedit_formatIds-result"]').withText('(D)-Discussion'))
       // Change time fields to test HH:MM format
    .click(as.quickedit_startTime)
    .pressKey('ctrl+a delete')
    .typeText(as.quickedit_startTime, '14:30:00')
    .click(as.quickedit_duration_hours)
    .click(Selector('option').withText('2'))
    .click(as.quickedit_duration_minutes)
    .click(Selector('option').withText('45'))
    // Save the changes
    .click(as.quickedit_dialog_parent.find("button.ui-corner-all").nth(2))
    .wait(1000);
  
  // Get the request that was sent
  const requestBody = JSON.parse(formatIdsLogger.requests[0].request.body.toString());
  
  // Validate the JSON format
  await t
    // Check that formatIds is an array of integers
    .expect(Array.isArray(requestBody.changes_requested.formatIds)).ok('formatIds should be an array')
    .expect(requestBody.changes_requested.formatIds.every(id => Number.isInteger(id))).ok('formatIds should contain only integers')
    // Check that duration is in HH:MM format (no seconds)
    .expect(requestBody.changes_requested.duration).match(/^\d{2}:\d{2}$/, 'duration should be in HH:MM format')
    // Check that startTime is in HH:MM format (no seconds)
    .expect(requestBody.changes_requested.startTime).match(/^\d{2}:\d{2}$/, 'startTime should be in HH:MM format');
}).requestHooks(formatIdsLogger);