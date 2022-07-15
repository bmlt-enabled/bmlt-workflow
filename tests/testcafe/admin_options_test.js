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

import { ao } from "./models/admin_options";
import { as } from "./models/admin_submissions";
import { asb } from "./models/admin_service_bodies";
import { uf } from "./models/meeting_update_form";

import { userVariables } from "../../.testcaferc";
import { t, Selector, Role, RequestLogger } from "testcafe";

import { 
  reset_bmlt, 
  basic_options, 
  configure_service_bodies, 
  insert_submissions, 
  wbw_admin, 
  click_dialog_button_by_index, 
  select_dropdown_by_text, 
  select_dropdown_by_value, 
  delete_submissions} from "./helpers/helper";

import fs from "fs";
import { join as joinPath } from "path";
import os from "os";

const backupurl = userVariables.admin_backup_json;
const logger = RequestLogger(
  { url: backupurl, method: "post" },
  {
    logResponseHeaders: true,
    logResponseBody: true,
  }
);

async function waitForFileDownload(path) {
  for (let i = 0; i < 10; i++) {
    if (fs.existsSync(path)) return true;

    await t.wait(500);
  }

  return fs.existsSync(path);
}

function getFileDownloadPath(download) {
  return joinPath(os.homedir(), "Downloads", download);
}

function randstr()
{
  return Math.random().toString(36).replace(/[^a-z]+/g, "") .substr(0, 9);
}

let downloadedFilePath = null;

fixture`admin_options_fixture`
  .beforeEach(async (t) => {

    await reset_bmlt();

    await basic_options();
    
    await delete_submissions();
    
    await configure_service_bodies();

    await insert_submissions();

    await t.useRole(wbw_admin).navigateTo(userVariables.admin_options_page);
  })
  .requestHooks(logger);

test("Backup", async (t) => {
  
  // console.log(backupurl);
  await t.click(ao.backup_button);
  const b_elem = Selector("#wbw_backup_filename");
  const state = await b_elem();
  const filename = state.attributes.download;
  downloadedFilePath = getFileDownloadPath(filename);
  await waitForFileDownload(downloadedFilePath);
  // console.log(logger);
  var f = JSON.parse(logger.requests[0].response.body.toString());
  // console.log(logger.requests[0].response.body.toString());
  var backup = JSON.parse(f.backup);

  await t.expect(f.message).eql("Backup Successful");

  await t.expect(backup.options.wbw_db_version).eql("0.4.0").expect(backup.options.wbw_bmlt_server_address).eql("http://54.153.167.239/blank_bmlt/main_server/");
  // find a specific meeting
  let obj = backup.submissions.find((o) => o.id === "94");

  await t.expect(obj.submitter_name).eql("first last").expect(obj.submission_type).eql("reason_change");
});

test("Restore", async (t) => {

  await t
    .setFilesToUpload(ao.wbw_file_selector, ["./uploads/restoretest1.json"])
    // .click(ao.restore_button)
    // .debug()
    .expect(ao.restore_warning_dialog_parent.visible)
    .eql(true);
  // click ok
  await click_dialog_button_by_index(ao.restore_warning_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(ao.restore_warning_dialog_parent.visible).eql(false).navigateTo(userVariables.admin_submissions_page);
  // assert id = 22222
  var row = 0;
  var column = 0;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("22222");
  // assert email = restoretest
  var row = 0;
  var column = 2;
  await t.expect(as.dt_submission.child("tbody").child(row).child(column).innerText).eql("restoretest");
});

test("Options_Save", async (t) => {
  const testfso = randstr() + "@" + randstr() + ".com";
  const testfrom = randstr() + "@" + randstr() + ".com";
  await t
    .typeText(ao.wbw_fso_email_address, testfso, { replace: true })
    .expect(ao.wbw_fso_email_address.value)
    .eql(testfso)
    .typeText(ao.wbw_email_from_address, testfrom, { replace: true })
    .expect(ao.wbw_email_from_address.value)
    .eql(testfrom);
  await select_dropdown_by_text(ao.wbw_optional_location_nation, "Display + Required Field");
  await select_dropdown_by_text(ao.wbw_optional_location_sub_province, "Display Only");
  await select_dropdown_by_text(ao.wbw_delete_closed_meetings, "Delete");
  await t.click(ao.submit);
  await ao.settings_updated();
  await t.expect(ao.wbw_fso_email_address.value).eql(testfso).expect(ao.wbw_email_from_address.value).eql(testfrom);

  // .expect(ao.settings_updated.exists).eql(true);
});

test("Check_Optional_Fields", async (t) => {
  // test optional fields with 'display and required' option

  await t.useRole(wbw_admin).navigateTo(userVariables.admin_options_page);
  await select_dropdown_by_text(ao.wbw_optional_location_nation, "Display + Required Field");
  await select_dropdown_by_text(ao.wbw_optional_location_sub_province, "Display + Required Field");
  await t.click(ao.submit);
  await ao.settings_updated();
  await t.useRole(Role.anonymous()).navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_new");

  await t
    .expect(uf.optional_location_nation.visible)
    .eql(true)
    .expect(uf.optional_location_sub_province.visible)
    .eql(true)
    .expect(uf.location_nation.getAttribute("required"))
    .eql("required")
    .expect(uf.location_sub_province.getAttribute("required"))
    .eql("required")

    // test optional fields with 'hidden' option

    .useRole(wbw_admin)
    .navigateTo(userVariables.admin_options_page);
  await select_dropdown_by_text(ao.wbw_optional_location_nation, "Hidden");
  await select_dropdown_by_text(ao.wbw_optional_location_sub_province, "Hidden");
  await t.click(ao.submit);
  await ao.settings_updated();
  await t.useRole(Role.anonymous()).navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_new");

  await t
    .expect(uf.optional_location_nation.visible)
    .eql(false)
    .expect(uf.optional_location_sub_province.visible)
    .eql(false)

    // test optional fields with 'display' option
    .useRole(wbw_admin)
    .navigateTo(userVariables.admin_options_page);
  await select_dropdown_by_text(ao.wbw_optional_location_nation, "Display");
  await select_dropdown_by_text(ao.wbw_optional_location_sub_province, "Display");
  await t.click(ao.submit);
  await ao.settings_updated();
  await t.useRole(Role.anonymous()).navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_new");

  await t.expect(uf.optional_location_nation.visible).eql(true).expect(uf.optional_location_sub_province.visible).eql(true);
});
