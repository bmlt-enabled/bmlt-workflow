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

import { uf } from "./models/meeting_update_form";
import { ao } from "./models/admin_options";

import { Role, Selector } from "testcafe";

import { 
  reset_bmlt3x, 
  reset_bmlt3x_with_states_on, 
  waitfor,
  bmltwf_admin,
  restore_from_backup,
  select_dropdown_by_text, 
  select_dropdown_by_value
  } from "./helpers/helper.js";

import { userVariables } from "../../.testcaferc";

fixture`bmlt3x_meeting_update_form_fixture`
.before(async (t) => {
  await reset_bmlt3x(t);

})
.beforeEach(async (t) => {

  await waitfor(userVariables.admin_logon_page_single);
  await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,"bmlt3x","8001");

  // log in as noone
  await t.useRole(Role.anonymous());
});

test("Success_New_Standard_Meeting_And_Submit", async (t) => {

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
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number, "`12345`");

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  // virtual meeting settings
  await select_dropdown_by_value(uf.venue_type, "1");
  await t
    .expect(uf.venue_type.value)
    .eql("1")
    .expect(uf.virtual_meeting_link.visible)
    .eql(false)
    .expect(uf.phone_meeting_number.visible)
    .eql(false)
    .expect(uf.virtual_meeting_additional_info.visible)
    .eql(false);

  // meeting settings
  await t.typeText(uf.meeting_name, "my test meeting");

  await select_dropdown_by_text(uf.weekday_tinyint, "Monday");

  await t.typeText(uf.start_time, "10:40");

  await select_dropdown_by_value(uf.duration_hours, "04");
  await select_dropdown_by_value(uf.duration_minutes, "30");

  // format list
  await t.click(uf.format_list_clickable).pressKey("b e g enter").click(uf.format_list_clickable).pressKey("l i n enter");

  await t

    .typeText(uf.location_text, "my location")
    .typeText(uf.location_street, "110 Avoca Street")
    .typeText(uf.location_info, "info")
    .typeText(uf.location_municipality, "Randwick")
    // .typeText(uf.location_sub_province, 'subprovince')
    .typeText(uf.location_province, "NSW")
    .typeText(uf.location_postal_code_1, "2031");

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
});

test("Success_New_Hybrid_Meeting_And_Submit", async (t) => {

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
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number, "`12345`");

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

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
    .typeText(uf.phone_meeting_number, "+61 1800 253430 code #8303782669")
    .typeText(uf.virtual_meeting_link, "https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09")
    .typeText(uf.virtual_meeting_additional_info, "Zoom ID 83037287669 Passcode: testing");

  // meeting settings
  await t.typeText(uf.meeting_name, "my test meeting");

  await select_dropdown_by_text(uf.weekday_tinyint, "Monday");

  await t.typeText(uf.start_time, "10:40");

  await select_dropdown_by_value(uf.duration_hours, "04");
  await select_dropdown_by_value(uf.duration_minutes, "30");

  // format list
  await t.click(uf.format_list_clickable).pressKey("b e g enter").click(uf.format_list_clickable).pressKey("l i n enter");

  await t

    .typeText(uf.location_text, "my location")
    .typeText(uf.location_street, "110 Avoca Street")
    .typeText(uf.location_info, "info")
    .typeText(uf.location_municipality, "Randwick")
    // .typeText(uf.location_sub_province, 'subprovince')
    .typeText(uf.location_province, "NSW")
    .typeText(uf.location_postal_code_1, "2031");

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
});

test("Success_New_Virtual_Meeting_And_Submit", async (t) => {

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
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number, "`12345`");

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  // virtual meeting settings
  await select_dropdown_by_value(uf.venue_type, "2");
  await t
    .expect(uf.venue_type.value)
    .eql("2")
    .expect(uf.virtual_meeting_link.visible)
    .eql(true)
    .expect(uf.phone_meeting_number.visible)
    .eql(true)
    .expect(uf.virtual_meeting_additional_info.visible)
    .eql(true);
  await t
    .typeText(uf.phone_meeting_number, "+61 1800 253430 code #8303782669")
    .typeText(uf.virtual_meeting_link, "https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09")
    .typeText(uf.virtual_meeting_additional_info, "Zoom ID 83037287669 Passcode: testing");

  // meeting settings
  await t.typeText(uf.meeting_name, "my test meeting");

  await select_dropdown_by_text(uf.weekday_tinyint, "Monday");

  await t.typeText(uf.start_time, "10:40");

  await select_dropdown_by_value(uf.duration_hours, "04");
  await select_dropdown_by_value(uf.duration_minutes, "30");

  // format list
  await t.click(uf.format_list_clickable).pressKey("b e g enter").click(uf.format_list_clickable).pressKey("l i n enter");

  await t

    // no location for virtual meeting
    // .typeText(uf.location_text, "my location")
    // .typeText(uf.location_street, "110 Avoca Street")
    // .typeText(uf.location_info, "info")
    .typeText(uf.location_municipality, "Randwick")
    // .typeText(uf.location_sub_province, 'subprovince')
    .typeText(uf.location_province, "NSW")
    .typeText(uf.location_postal_code_1, "2031");

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
});

test("Success_New_Tempclosure_Meeting_And_Submit", async (t) => {

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
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number, "`12345`");

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  // virtual meeting settings
  await select_dropdown_by_value(uf.venue_type, "4");
  await t
    .expect(uf.venue_type.value)
    .eql("4")
    .expect(uf.virtual_meeting_link.visible)
    .eql(true)
    .expect(uf.phone_meeting_number.visible)
    .eql(true)
    .expect(uf.virtual_meeting_additional_info.visible)
    .eql(true);
  await t
    .typeText(uf.phone_meeting_number, "+61 1800 253430 code #8303782669")
    .typeText(uf.virtual_meeting_link, "https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09")
    .typeText(uf.virtual_meeting_additional_info, "Zoom ID 83037287669 Passcode: testing");

  // meeting settings
  await t.typeText(uf.meeting_name, "my test meeting");

  await select_dropdown_by_text(uf.weekday_tinyint, "Monday");

  await t.typeText(uf.start_time, "10:40");

  await select_dropdown_by_value(uf.duration_hours, "04");
  await select_dropdown_by_value(uf.duration_minutes, "30");

  // format list
  await t.click(uf.format_list_clickable).pressKey("b e g enter").click(uf.format_list_clickable).pressKey("l i n enter");

  await t

    // no location for virtual meeting
    .typeText(uf.location_text, "my location")
    .typeText(uf.location_street, "110 Avoca Street")
    .typeText(uf.location_info, "info")
    .typeText(uf.location_municipality, "Randwick")
    // .typeText(uf.location_sub_province, 'subprovince')
    .typeText(uf.location_province, "NSW")
    .typeText(uf.location_postal_code_1, "2031");

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
});

test("Success_Change_Meeting_Name_And_Submit", async (t) => {

  await t.navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_change");

  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "lifeline");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true)
  .expect(uf.meeting_details.visible).eql(true)
  .expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number, "`12345`")

    .typeText(uf.meeting_name, "update")
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
    .expect(uf.success_page_header.innerText)
    .match(/submission\ successful/);
});

test("Success_Close_Meeting_And_Submit", async (t) => {
  await t.navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_close");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_close");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "lifeline");
  await t.pressKey("enter");

  // validate form is laid out correctl
  await t.expect(uf.personal_details.visible).eql(true)
  .expect(uf.meeting_details.visible).eql(true)
  .expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number, "`12345`")

    .typeText(uf.meeting_name, "update");

  // email dropdown
  await select_dropdown_by_text(uf.add_contact, "Yes");
  await t.expect(uf.add_contact.value).eql("yes");

  // group member dropdown
  await select_dropdown_by_value(uf.group_relationship, "Group Member");
  await t.expect(uf.group_relationship.value).eql("Group Member");

  await t.typeText(uf.additional_info, "my additional info");

  await t
    .click(uf.submit)
    .expect(uf.success_page_header.innerText)
    .match(/submission\ successful/);
});

test("Change_Meeting_Details_Check_Highlighting", async (t) => {

  await t.navigateTo(userVariables.formpage);

  await select_dropdown_by_value(uf.update_reason, "reason_change");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "lifeline");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true)
  .expect(uf.meeting_details.visible).eql(true)
  .expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t
    .typeText(uf.first_name, "first")
    .typeText(uf.last_name, "last")
    .typeText(uf.email_address, "test@test.com.zz")
    .typeText(uf.contact_number, "`12345`")

    .typeText(uf.meeting_name, "update")
    // make sure highlighting is present
    .expect(uf.meeting_name.hasClass("bmltwf-changed"))
    .ok();

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
    .typeText(uf.phone_meeting_number, "+61 1800 253430 code #8303782669")
    .expect(uf.phone_meeting_number.hasClass("bmltwf-changed"))
    .ok()
    .typeText(uf.virtual_meeting_link, "https://us02web.zoom.us/j/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09")
    .expect(uf.virtual_meeting_link.hasClass("bmltwf-changed"))
    .ok()
    .typeText(uf.virtual_meeting_additional_info, "Zoom ID 83037287669 Passcode: testing")
    .expect(uf.virtual_meeting_additional_info.hasClass("bmltwf-changed"))
    .ok();

  // meeting settings

  // weekday
  await select_dropdown_by_text(uf.weekday_tinyint, "Monday");
  // await t.debug();
  await t
    .expect(uf.weekday_tinyint.hasClass("bmltwf-changed"))
    .ok()
    // start time
    .typeText(uf.start_time, "10:40")
    .expect(uf.start_time.hasClass("bmltwf-changed"))
    .ok();

  // duration
  await select_dropdown_by_value(uf.duration_hours, "09");
  await t.expect(uf.duration_hours.hasClass("bmltwf-changed")).ok();
  await select_dropdown_by_value(uf.duration_minutes, "35");
  await t.expect(uf.duration_minutes.hasClass("bmltwf-changed")).ok();

  // format list
  await t.click(uf.format_list_clickable).pressKey("g u i d enter").expect(uf.format_list_clickable.hasClass("bmltwf-changed")).ok();

  await t
    .typeText(uf.location_text, "my location")
    .expect(uf.format_list_clickable.hasClass("bmltwf-changed"))
    .ok()
    .typeText(uf.location_street, "110 Avoca Street")
    .expect(uf.location_street.hasClass("bmltwf-changed"))
    .ok()
    .typeText(uf.location_info, "info")
    .expect(uf.location_info.hasClass("bmltwf-changed"))
    .ok()
    .typeText(uf.location_municipality, "Randwick")
    .expect(uf.location_municipality.hasClass("bmltwf-changed"))
    .ok()
    .typeText(uf.location_province, "VIC")
    .expect(uf.location_province.hasClass("bmltwf-changed"))
    .ok()
    .typeText(uf.location_postal_code_1, "2031")
    .expect(uf.location_postal_code_1.hasClass("bmltwf-changed"))
    .ok();
});

test("Change_Nothing_Check_Error", async (t) => {
  await t.navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_change");

  // check our divs are visible
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "lifeline");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true)
  .expect(uf.meeting_details.visible).eql(true)
  .expect(uf.additional_info_div.visible).eql(true);

  // personal details
  await t.typeText(uf.first_name, "first").typeText(uf.last_name, "last").typeText(uf.email_address, "test@test.com.zz").typeText(uf.contact_number, "`12345`");

  await select_dropdown_by_value(uf.group_relationship, "Group Member");

  await t
    .click(uf.submit)
    .expect(uf.error_para.innerText)
    .match(/Nothing\ was\ changed/);
});

test("Check_States_Dropdown_Appears_And_Set_Correctly", async (t) => {

  await reset_bmlt3x_with_states_on(t);
  
  await t.navigateTo(userVariables.formpage);
  // console.log(userVariables.formpage);
  // await t.debug();
  await select_dropdown_by_value(uf.update_reason, "reason_change");
  await t.expect(uf.update_reason.value).eql("reason_change");

  // meeting selector
  await t.click("#select2-meeting-searcher-container");
  await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "chance");
  await t.pressKey("enter");

  // validate form is laid out correctly
  await t.expect(uf.personal_details.visible).eql(true).expect(uf.meeting_details.visible).eql(true).expect(uf.additional_info_div.visible).eql(true);

  await t
  // should be a select element if we have a dropdown
  .expect(uf.location_province.tagName).eql("select")
  // should have changed the state to NY which is not the default
  .expect(uf.location_province.value).eql("NY");

});
