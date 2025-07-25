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
  waitfor,
  bmltwf_admin,
  restore_from_backup, 
  select_dropdown_by_value,
  myip,
  set_language_single
  } from "./helpers/helper.js";

import { userVariables } from "../../.testcaferc";

fixture`meeting_update_form_states_test_fixture`
.beforeEach(async (t) => {

  await waitfor(userVariables.admin_logon_page_single);
  // 3002 is the states/subprovinces on host
  await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3003","display");
  await set_language_single(t, "en_EN");

  // log in as noone
  await t.useRole(Role.anonymous());
});

test("Check_States_And_SubProvince_Dropdown_Appears_And_Set_Correctly", async (t) => {
  
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

  await t
  // should be a select element if we have a dropdown
  .expect(uf.location_sub_province.tagName).eql("select")
  .expect(uf.location_sub_province.value).eql("Columbia");
});
