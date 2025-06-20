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
  myip,
  restore_from_backup, 
  bmltwf_admin, 
  click_dialog_button_by_index, 
  set_language_single,
  select_dropdown_by_text, 
  select_dropdown_by_value, 
  check_checkbox,
  uncheck_checkbox, 
  waitfor} from "./helpers/helper";

import fs from "fs";
import { join as joinPath } from "path";
import os from "os";


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

fixture`bmlt3x_admin_options_fixture`
  .beforeEach(async (t) => {
    await waitfor(userVariables.admin_logon_page_single);
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3001","hidden");
    await set_language_single(t, "en_EN");
    await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
  });

const logger = RequestLogger(/backup/,
{
  logResponseHeaders: true,
  logResponseBody: true,
}
);

test("Backup", async (t) => {
  
  await t.click(ao.backup_button);
  const b_elem = Selector("#bmltwf_backup_filename");
  const state = await b_elem();
  const filename = state.attributes.download;
  downloadedFilePath = getFileDownloadPath(filename);
  await waitForFileDownload(downloadedFilePath);
  var f = JSON.parse(logger.requests[0].response.body.toString());
  // console.log(logger.requests[0].response.body.toString());
  var backup = JSON.parse(f.backup);
  // console.log(backup);
  await t.expect(f.message).eql("Backup Successful");

  await t.expect(backup.options.bmltwf_db_version).eql("0.4.0");
  // find a specific meeting
  let obj = backup.submissions.find((o) => o.change_id === "94");
  // console.log(obj);
  // await t.debug();
  await t.expect(obj.submitter_name).eql("first l").expect(obj.submission_type).eql("reason_change");
})  .requestHooks(logger);


test("Restore", async (t) => {

  const fs = require('fs');

  const restoretest = String.raw`
  {
    "options": {
        "bmltwf_db_version": "0.4.0",
        "bmltwf_bmlt_server_address": "http:\/\/${myip()}:3001\/main_server\/",
        "bmltwf_bmlt_username": "bmlt-workflow-bot",
        "bmltwf_bmlt_password": "a:2:{s:6:\"config\";a:6:{s:4:\"size\";s:4:\"MzI=\";s:4:\"salt\";s:24:\"\/5ObzNuYZ\/Y5aoYTsr0sZw==\";s:9:\"limit_ops\";s:4:\"OA==\";s:9:\"limit_mem\";s:12:\"NTM2ODcwOTEy\";s:3:\"alg\";s:4:\"Mg==\";s:5:\"nonce\";s:16:\"VukDVzDkAaex\/jfB\";}s:9:\"encrypted\";s:44:\"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw==\";}",
        "bmltwf_bmlt_test_status": "success",
        "bmltwf_submitter_email_template": "<p><br>Thank you for submitting the online meeting update.<br>We will usually be able action your\r\n    request within 48 hours.<br>Our process also updates NA websites around Australia and at NA World Services.<br>\r\n<\/p>\r\n<hr><br>\r\n<table class=\"blueTable\" style=\"border: 1px solid #1C6EA4;background-color: #EEEEEE;text-align: left;border-collapse: collapse;\">\r\n    <thead style=\"background: #1C6EA4;border-bottom: 2px solid #444444;\">\r\n        <tr>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: none;\">\r\n                <br>Field Name\r\n            <\/th>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: 2px solid #D0E4F5;\">\r\n                <br>Value\r\n            <\/th>\r\n        <\/tr>\r\n    <\/thead>\r\n    <tbody>\r\n        {field:submission}\r\n    <\/tbody>\r\n<\/table>",
        "bmltwf_optional_location_province": "display",
        "bmltwf_optional_location_sub_province": "hidden",
        "bmltwf_optional_location_nation": "hidden",
        "bmltwf_delete_closed_meetings": "unpublish",
        "bmltwf_email_from_address": "Test <test@test.org>",
        "bmltwf_fso_email_template": "<p>Attn: FSO.<br>\r\nPlease send a starter kit to the following meeting:\r\n<\/p>\r\n<hr><br>\r\n<table class=\"blueTable\" style=\"border: 1px solid #1C6EA4;background-color: #EEEEEE;text-align: left;border-collapse: collapse;\">\r\n    <thead style=\"background: #1C6EA4;border-bottom: 2px solid #444444;\">\r\n        <tr>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: none;\">\r\n                <br>Field Name\r\n            <\/th>\r\n            <th style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: 2px solid #D0E4F5;\">\r\n                <br>Value\r\n            <\/th>\r\n        <\/tr>\r\n    <\/thead>\r\n    <tbody>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Group Name<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:name}<\/td>\r\n        <\/tr>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Requester First Name<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:first_name}<\/td>\r\n        <\/tr>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Requester Last Name<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:last_name}<\/td>\r\n        <\/tr>\r\n        <tr>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">Starter Kit Postal Address<\/td>\r\n            <td style=\"border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;\">{field:starter_kit_postal_address}\r\n            <\/td>\r\n        <\/tr>\r\n    <\/tbody>\r\n<\/table>",
        "bmltwf_fso_email_address": ""
    },
    "submissions": [
        {
            "change_id": "22222",
            "submission_time": "2022-05-15 12:32:38",
            "change_time": "0000-00-00 00:00:00",
            "changed_by": null,
            "change_made": null,
            "submitter_name": "first last",
            "submission_type": "reason_new",
            "submitter_email": "restoretest",
            "id": "0",
            "serviceBodyId": "2",
            "changes_requested": "{\"name\":\"my test meeting\",\"startTime\":\"10:40:00\",\"duration\":\"04:30:00\",\"location_text\":\"my location\",\"location_street\":\"110 Avoca Street\",\"location_info\":\"info\",\"location_municipality\":\"Randwick\",\"location_province\":\"NSW\",\"location_postal_code_1\":2031,\"day\":\"2\",\"serviceBodyId\":2,\"formatIds\":[\"1\",\"2\",\"56\"],\"contact_number\":\"12345\",\"group_relationship\":\"Group Member\",\"add_contact\":\"yes\",\"additional_info\":\"my additional info\",\"virtual_meeting_additional_info\":\"Zoom ID 83037287669 Passcode: testing\",\"phone_meeting_number\":\"+61 1800 253430 code #8303782669\",\"virtual_meeting_link\":\"https:\\\/\\\/us02web.zoom.us\\\/j\\\/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09\"}",
            "action_message": null
        }
    ],
    "service_bodies": [
        {
            "serviceBodyId": "1",
            "service_body_name": "toplevel",
            "service_body_description": "",
            "show_on_form": "1"
        },
        {
            "serviceBodyId": "2",
            "service_body_name": "a-level1",
            "service_body_description": "",
            "show_on_form": "1"
        },
        {
            "serviceBodyId": "3",
            "service_body_name": "b-level1",
            "service_body_description": "",
            "show_on_form": "1"
        }
    ],
    "service_bodies_access": [
        {
            "serviceBodyId": "1",
            "wp_uid": "1"
        },
        {
            "serviceBodyId": "2",
            "wp_uid": "4"
        },
        {
            "serviceBodyId": "2",
            "wp_uid": "1"
        },
        {
            "serviceBodyId": "3",
            "wp_uid": "1"
        }
    ]
}`
  try {
    fs.writeFileSync("tests/testcafe/uploads/restoretest2.json", restoretest, { flag: 'w' });
  } catch (err) {
    console.log('Error writing json:' + err)
  }

  await t
    .setFilesToUpload(ao.bmltwf_file_selector, ["./uploads/restoretest2.json"])
    // .click(ao.restore_button)
    // .debug()
    .expect(ao.restore_warning_dialog_parent.visible)
    .eql(true);
  // click ok
  await click_dialog_button_by_index(ao.restore_warning_dialog_parent, 1);
  // dialog closes after ok button
  await t.expect(ao.restore_warning_dialog_parent.visible).eql(false)
  .navigateTo(userVariables.admin_submissions_page_single);
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

  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
  await check_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_nation_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);
  await check_checkbox(t,ao.bmltwf_required_meeting_formats_required_checkbox);

  const testfso = randstr() + "@" + randstr() + ".com";
  const testfrom = randstr() + "@" + randstr() + ".com";
  await t
    .typeText(ao.bmltwf_fso_email_address, testfso, { replace: true })
    .expect(ao.bmltwf_fso_email_address.value)
    .eql(testfso)
    .typeText(ao.bmltwf_email_from_address, testfrom, { replace: true })
    .expect(ao.bmltwf_email_from_address.value)
    .eql(testfrom);

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
    .expect(uf.display_formatIds.getAttribute("required"))
    .eql('')
    .expect(uf.location_nation_label.innerText).eql(testnationdisplay+" *")
    .expect(uf.location_province_label.innerText).eql(testprovincedisplay+" *")
    .expect(uf.location_sub_province_label.innerText).eql(testsubprovincedisplay+" *")
    .expect(uf.location_postal_code_1_label.innerText).eql(testpostcodedisplay+" *")

    // test optional fields with 'hidden' option

    .useRole(bmltwf_admin)
    .navigateTo(userVariables.admin_settings_page_single);

  
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
    .navigateTo(userVariables.admin_settings_page_single);

  await select_dropdown_by_text(ao.bmltwf_fso_feature, "Enabled");
  
  await check_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_nation_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_required_meeting_formats_required_checkbox);

  await t.click(ao.submit);
  await ao.settings_updated();
  await t.useRole(Role.anonymous()).navigateTo(userVariables.formpage);
  await select_dropdown_by_value(uf.update_reason, "reason_new");

  await t.expect(uf.optional_location_nation.visible).eql(true)
  .expect(uf.optional_location_sub_province.visible).eql(true)
  .expect(uf.optional_location_province.visible).eql(true)
  .expect(uf.starter_pack.visible).eql(true)
  .expect(uf.location_postal_code_1.visible).eql(true)
  .expect(uf.display_formatIds.getAttribute("required"))
  .eql(null);


});

const gmapslogger = RequestLogger(/https:\/\/maps.googleapis.com\/maps\/api\/js\?key=/,
{
  logRequestBody: true,
}
);

test("Check_Custom_Google_Maps_Key", async (t) => {
  // check we can put in a google maps key and use it

  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
  await select_dropdown_by_text(ao.bmltwf_google_maps_key_select, "Custom Google Maps Key");
  await t.expect(ao.bmltwf_google_maps_key.visible).eql(true)
  .typeText(ao.bmltwf_google_maps_key, 'AIXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', { replace: true })
  .click(ao.submit);
  await ao.settings_updated();

  await t.navigateTo(userVariables.admin_submissions_page_single)
  .wait(1000);
  // console.log(gmapslogger.requests);
  var f = gmapslogger.requests[0].request.url;
  await t.expect(f).contains('=AIXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX&');

}).requestHooks(gmapslogger);

test("Check_BMLT_Google_Maps_Key", async (t) => {
  // check we can put in a google maps key and use it

  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
  await select_dropdown_by_text(ao.bmltwf_google_maps_key_select, "Google Maps Key from BMLT");
  await t.expect(ao.bmltwf_google_maps_key.visible).eql(false)
  await t.click(ao.submit);
  await ao.settings_updated();

  await t.navigateTo(userVariables.admin_submissions_page_single)
  .wait(1000);
  // console.log(gmapslogger.requests);
  var f = gmapslogger.requests[0].request.url;
  await t.expect(f).contains('=AIzaSy');

}).requestHooks(gmapslogger);

test('Quickedit_Input_Labels', async t => {
});
