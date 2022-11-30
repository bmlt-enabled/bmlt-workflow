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
  randstr,
  restore_from_backup, 
  reset_bmlt, 
  bmltwf_submission_reviewer,
  bmltwf_submission_nopriv,
  bmltwf_admin,
  select_dropdown_by_text,
  click_table_row_column,
  waitfor
 } from "./helpers/helper.js";

import { userVariables } from "../../.testcaferc";

fixture`admin_submissions_permissions_fixture`.beforeEach(async (t) => {
  await reset_bmlt(t);
  await waitfor(userVariables.admin_logon_page_single);
  await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,"bmlt2x","8000");
});

test("Can_View_Submissions_As_Priv_User", async (t) => {
  
  await t.useRole(bmltwf_submission_reviewer).navigateTo(userVariables.admin_submissions_page_single)
  // .debug()
  .expect(as.dt_submission_wrapper.visible).eql(true);

});

test("Cant_View_Submissions_As_Non_Priv", async (t) => {

  await t.useRole(bmltwf_submission_nopriv).navigateTo(userVariables.admin_submissions_page_single)
  .expect(as.dt_submission_wrapper.visible).eql(false);

});

test("Cant_Delete_Submissions_As_Trusted_Servant", async (t) => {

  await t.useRole(bmltwf_admin)
  .navigateTo(userVariables.admin_settings_page_single);

  // let us save successfully
  const testfso = randstr() + "@" + randstr() + ".com";
  const testfrom = randstr() + "@" + randstr() + ".com";
  await t
    .typeText(ao.bmltwf_fso_email_address, testfso, { replace: true })
    .expect(ao.bmltwf_fso_email_address.value)
    .eql(testfso)
    .typeText(ao.bmltwf_email_from_address, testfrom, { replace: true })
    .expect(ao.bmltwf_email_from_address.value)
    .eql(testfrom);

  // change option to False
  await select_dropdown_by_text(ao.bmltwf_trusted_servants_can_delete_submissions, "False");
  await t.click(ao.submit);
  await ao.settings_updated();

  await t.useRole(bmltwf_submission_reviewer).navigateTo(userVariables.admin_submissions_page_single);
  // check delete button is disabled
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);

  // delete
  var g = as.dt_submission_wrapper.find("button").nth(3);
  await t.expect(g.hasAttribute("disabled")).ok();
  
});


test("Can_Delete_Submissions_As_Admin", async (t) => {

  await t.useRole(bmltwf_admin)
  .navigateTo(userVariables.admin_settings_page_single);

  // let us save successfully
  const testfso = randstr() + "@" + randstr() + ".com";
  const testfrom = randstr() + "@" + randstr() + ".com";
  await t
    .typeText(ao.bmltwf_fso_email_address, testfso, { replace: true })
    .expect(ao.bmltwf_fso_email_address.value)
    .eql(testfso)
    .typeText(ao.bmltwf_email_from_address, testfrom, { replace: true })
    .expect(ao.bmltwf_email_from_address.value)
    .eql(testfrom);

  // change option to False
  await select_dropdown_by_text(ao.bmltwf_trusted_servants_can_delete_submissions, "False");
  await t.click(ao.submit);
  await ao.settings_updated();

  await t.navigateTo(userVariables.admin_submissions_page_single);
  // check delete button is enabled
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);

  // delete
  var g = as.dt_submission_wrapper.find("button").nth(3);
  // await t.debug();
  await t.expect(g.hasAttribute("disabled")).notOk();
  
});

test("Can_Delete_Submissions_As_Trusted_Servant", async (t) => {

  await t.useRole(bmltwf_admin)
  .navigateTo(userVariables.admin_settings_page_single);

  // let us save successfully
  const testfso = randstr() + "@" + randstr() + ".com";
  const testfrom = randstr() + "@" + randstr() + ".com";
  await t
    .typeText(ao.bmltwf_fso_email_address, testfso, { replace: true })
    .expect(ao.bmltwf_fso_email_address.value)
    .eql(testfso)
    .typeText(ao.bmltwf_email_from_address, testfrom, { replace: true })
    .expect(ao.bmltwf_email_from_address.value)
    .eql(testfrom);

  // change option to False
  await select_dropdown_by_text(ao.bmltwf_trusted_servants_can_delete_submissions, "True");
  await t.click(ao.submit);
  await ao.settings_updated();

  await t.useRole(bmltwf_submission_reviewer).navigateTo(userVariables.admin_submissions_page_single);
  // check delete button is enabled
  var row = 0;
  await click_table_row_column(as.dt_submission, row, 0);

  // delete
  var g = as.dt_submission_wrapper.find("button").nth(3);
  await t.expect(g.hasAttribute("disabled")).notOk();

});


