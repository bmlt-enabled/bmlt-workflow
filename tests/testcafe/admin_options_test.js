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
import { uf } from "./models/meeting_update_form";

import { userVariables } from "../../.testcaferc";
import { t, Selector, Role, RequestLogger } from "testcafe";

import { 
  randstr,
  reset_bmlt, 
  basic_options, 
  configure_service_bodies, 
  insert_submissions, 
  bmltwf_admin, 
  click_dialog_button_by_index, 
  select_dropdown_by_text, 
  select_dropdown_by_value, 
  delete_submissions,
  check_checkbox,
  uncheck_checkbox} from "./helpers/helper";

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


let downloadedFilePath = null;

fixture`admin_options_fixture`
  .beforeEach(async (t) => {

    await reset_bmlt(t);

    await basic_options(t);
    
    await delete_submissions(t);
    
    await configure_service_bodies(t);

    await insert_submissions(t);

    await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page);
  })
  .requestHooks(logger);

test("Backup", async (t) => {
  
  // console.log(backupurl);
  await t.click(ao.backup_button);
  const b_elem = Selector("#bmltwf_backup_filename");
  const state = await b_elem();
  const filename = state.attributes.download;
  downloadedFilePath = getFileDownloadPath(filename);
  await waitForFileDownload(downloadedFilePath);
  // console.log(logger);
  var f = JSON.parse(logger.requests[0].response.body.toString());
  // console.log(logger.requests[0].response.body.toString());
  var backup = JSON.parse(f.backup);

  await t.expect(f.message).eql("Backup Successful");

  await t.expect(backup.options.bmltwf_db_version).eql("0.4.0").expect(backup.options.bmltwf_bmlt_server_address).eql("http://54.153.167.239/blank_bmlt/main_server/");
  // find a specific meeting
  let obj = backup.submissions.find((o) => o.id === "94");

  await t.expect(obj.submitter_name).eql("first last").expect(obj.submission_type).eql("reason_change");
});

test("Restore", async (t) => {

  await t
    .setFilesToUpload(ao.bmltwf_file_selector, ["./uploads/restoretest1.json"])
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
    .typeText(ao.bmltwf_fso_email_address, testfso, { replace: true })
    .expect(ao.bmltwf_fso_email_address.value)
    .eql(testfso)
    .typeText(ao.bmltwf_email_from_address, testfrom, { replace: true })
    .expect(ao.bmltwf_email_from_address.value)
    .eql(testfrom);
  await check_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_nation_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_required_checkbox);
  // await select_dropdown_by_text(ao.bmltwf_optional_location_nation, "Display + Required Field");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_province, "Display + Required Field");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_sub_province, "Display Only");
  await select_dropdown_by_text(ao.bmltwf_delete_closed_meetings, "Delete");
  await t.click(ao.submit);
  await ao.settings_updated();
  await t.expect(ao.bmltwf_fso_email_address.value).eql(testfso).expect(ao.bmltwf_email_from_address.value).eql(testfrom);

});

test("Check_Optional_Fields", async (t) => {
  // test optional fields with 'display and required' option

  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page);
  // await select_dropdown_by_text(ao.bmltwf_optional_location_nation, "Display + Required Field");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_sub_province, "Display + Required Field");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_province, "Display + Required Field");
  // await select_dropdown_by_text(ao.bmltwf_optional_postcode, "Display + Required Field");
  await check_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_nation_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);
  const testnationdisplay = randstr();
  const testprovincedisplay = randstr();
  const testsubprovincedisplay= randstr();
  const testpostcodedisplay = randstr();

  await t.typeText(ao.bmltwf_optional_location_nation_displayname, testnationdisplay, { replace: true })
  .typeText(ao.bmltwf_optional_location_province_displayname, testprovincedisplay, { replace: true })
  .typeText(ao.bmltwf_optional_location_sub_province_displayname, testsubprovincedisplay, { replace: true })
  .typeText(ao.bmltwf_optional_postcode_displayname, testpostcodedisplay, { replace: true })
  .click(ao.submit);
  await ao.settings_updated();

  await t.useRole(Role.anonymous())
    .navigateTo(userVariables.formpage);
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
    .expect(uf.location_province.getAttribute("required"))
    .eql("required")
    .expect(uf.location_postal_code_1.getAttribute("required"))
    .eql("required")
    .expect(uf.location_nation_label.innerText).eql(testnationdisplay+" *")
    .expect(uf.location_province_label.innerText).eql(testprovincedisplay+" *")
    .expect(uf.location_sub_province_label.innerText).eql(testsubprovincedisplay+" *")
    .expect(uf.location_postal_code_1_label.innerText).eql(testpostcodedisplay+" *")

    // test optional fields with 'hidden' option

    .useRole(bmltwf_admin)
    .navigateTo(userVariables.admin_settings_page);
  // await select_dropdown_by_text(ao.bmltwf_optional_location_nation, "Hidden");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_sub_province, "Hidden");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_province, "Hidden");
  // await select_dropdown_by_text(ao.bmltwf_optional_postcode, "Hidden");

  await select_dropdown_by_text(ao.bmltwf_fso_feature, "Disabled");

  await uncheck_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);

  await t.click(ao.submit);
  await ao.settings_updated();
  await t.useRole(Role.anonymous()).navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_new");

  await t
    .expect(uf.optional_location_nation.visible).eql(false)
    .expect(uf.optional_location_sub_province.visible).eql(false)
    .expect(uf.optional_location_province.visible).eql(false)
    .expect(uf.starter_pack.visible).eql(false)
    .expect(uf.location_postal_code_1.visible).eql(false)

    // test optional fields with 'display' option
    .useRole(bmltwf_admin)
    .navigateTo(userVariables.admin_settings_page);

  //   await select_dropdown_by_text(ao.bmltwf_optional_location_nation, "Display");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_sub_province, "Display");
  // await select_dropdown_by_text(ao.bmltwf_optional_location_province, "Display");
  // await select_dropdown_by_text(ao.bmltwf_optional_postcode, "Display");

  await select_dropdown_by_text(ao.bmltwf_fso_feature, "Enabled");
  
  await check_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_nation_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);

  await t.click(ao.submit);
  await ao.settings_updated();
  await t.useRole(Role.anonymous()).navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_new");

  await t.expect(uf.optional_location_nation.visible).eql(true)
  .expect(uf.optional_location_sub_province.visible).eql(true)
  .expect(uf.optional_location_province.visible).eql(true)
  .expect(uf.starter_pack.visible).eql(true)
  .expect(uf.location_postal_code_1.visible).eql(true);

});
