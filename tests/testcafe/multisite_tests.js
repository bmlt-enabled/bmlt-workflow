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

import { restore_from_backup, bmltwf_admin_multisingle, bmltwf_admin_multinetwork, waitfor, myip, setupCorrespondenceFeature, click_table_row_column } from "./helpers/helper";
import { wordpress_options } from "./models/wordpress_options";
import { userVariables } from "../../.testcaferc";
import { ao } from "./models/admin_options";
import { t, Selector } from "testcafe";
import { asb } from "./models/admin_service_bodies";

fixture`multisite_tests_fixture`.beforeEach(async (t) => {
  await waitfor(userVariables.admin_logon_page_multisingle);
  await restore_from_backup(bmltwf_admin_multisingle, userVariables.admin_settings_page_multisingle_plugin, userVariables.admin_restore_json_multisingle_plugin,myip(),"3001","hidden");
});

test("MultiSite_Single_Check_Options", async (t) => {

  // check that our options are installed only for sites that have the plugin enabled
  await t
    .useRole(bmltwf_admin_multisingle)
    .navigateTo(userVariables.admin_options_page_multisingle_plugin)
    // .debug()
    // does our db version appear in the options table?
    .expect(wordpress_options.bmltwf_db_version.exists)
    .eql(true)
    .navigateTo(userVariables.admin_options_page_multisingle_noplugin)
    // no plugin installed so option should not appear
    .expect(wordpress_options.bmltwf_db_version.exists)
    .eql(false);
});

test("MultiSite_Single_Check_Plugin_Config_Page", async (t) => {
  // check that our plugin config page shows only for sites that have the plugin enabled
  await t
    .useRole(bmltwf_admin_multisingle)
    .navigateTo(userVariables.admin_settings_page_multisingle_plugin);
  await ao.navigateToTab(t, 'email-templates');
  await t
    // we should see the email from address field in a working plugin
    .expect(ao.bmltwf_email_from_address.exists)
    .eql(true)
    .navigateTo(userVariables.admin_settings_page_multisingle_noplugin)
    // should get an error page from the bmltwf configuration page
    .expect(Selector("#error-page").exists)
    .eql(true);
});

test("MultiSite_Network_Check_Options", async (t) => {
  // check that our plugin options exist for all sites in network enabled
  await t
    .useRole(bmltwf_admin_multinetwork)
    .navigateTo(userVariables.admin_options_page_multinetwork_plugin)
    // does our db version appear in the options table?
    .expect(wordpress_options.bmltwf_db_version.exists)
    .eql(true)
    .navigateTo(userVariables.admin_options_page_multinetwork_plugin2)
    // does our db version appear in the options table?
    .expect(wordpress_options.bmltwf_db_version.exists)
    .eql(true);
});

test("MultiSite_Network_Check_Plugin_Config_Page", async (t) => {
  // check that our plugin config page shows for all sites in network enabled
  await t
    .useRole(bmltwf_admin_multinetwork)
    .navigateTo(userVariables.admin_settings_page_multinetwork_plugin);
  await ao.navigateToTab(t, 'email-templates');
  await t
    // we should see the email from address field in a working plugin
    .expect(ao.bmltwf_email_from_address.exists)
    .eql(true)
    .navigateTo(userVariables.admin_settings_page_multinetwork_plugin2);
  await ao.navigateToTab(t, 'email-templates');
  await t
    // we should see the email from address field in a working plugin
    .expect(ao.bmltwf_email_from_address.exists)
    .eql(true);
});

test("MultiSite_Network_Check_Plugin_Doesnt_Touch_Plugin2", async (t) => {
  await restore_from_backup(bmltwf_admin_multinetwork, userVariables.admin_settings_page_multinetwork_plugin, userVariables.admin_restore_json_multinetwork_plugin, myip(), "3001","hidden");
  await restore_from_backup(bmltwf_admin_multinetwork, userVariables.admin_settings_page_multinetwork_plugin2, userVariables.admin_restore_json_multinetwork_plugin2, myip(), "3001","hidden");

  // uncheck the service bodies in plugin1 and check they are still checked in plugin2
  await t
    .useRole(bmltwf_admin_multinetwork)
    .navigateTo(userVariables.admin_service_bodies_page_multinetwork_plugin)
    .click(Selector("ul#select2-bmltwf_userlist_id_1009-container").parent())
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_1009")
    .click(Selector("ul#select2-bmltwf_userlist_id_1046-container").parent())
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_1046")
    .click(Selector("ul#select2-bmltwf_userlist_id_1047-container").parent())
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_1047")
    .click(asb.bmltwf_submit)
    .navigateTo(userVariables.admin_settings_page_multinetwork_plugin2)
    .navigateTo(userVariables.admin_service_bodies_page_multinetwork_plugin2)
    .expect(Selector("#bmltwf_userlist_checkbox_id_1009").checked)
    .eql(true)
    .expect(Selector("#bmltwf_userlist_checkbox_id_1046").checked)
    .eql(true)
    .expect(Selector("#bmltwf_userlist_checkbox_id_1047").checked)
    .eql(true);
});

test("MultiSite_Single_Correspondence_Feature", async (t) => {
  // Test correspondence feature works in multisite single site activation
  const correspondencePageUrl = await setupCorrespondenceFeature(
    t, 
    bmltwf_admin_multisingle,
    userVariables.admin_settings_page_multisingle_plugin,
    userVariables.wp_pages_multisingle_plugin,
    userVariables.admin_correspondence_json_multisingle_plugin,
    userVariables.siteurl_multisingle_plugin
  );
  // Navigate to submissions page and verify correspondence button is enabled
  await t
    .navigateTo(userVariables.admin_submissions_page_multisingle_plugin)
    .wait(2000);
  await click_table_row_column(Selector('#dt-submission'), 0, 0);
  await t
    .wait(1000)
    .expect(Selector('#dt-submission_wrapper > div.dt-buttons > button:nth-child(3)').hasClass('disabled'))
    .notOk('Correspondence button should be enabled in multisite single site')
    
    // Verify correspondence page is accessible and contains correspondence form
    .navigateTo(correspondencePageUrl)
    .expect(Selector('.bmltwf-error').exists)
    .ok('Correspondence page should show error message when no thread specified');
});

test("MultiSite_Network_Correspondence_Feature", async (t) => {
  // Test correspondence feature works in multisite network activation
  let correspondencePageUrl = await setupCorrespondenceFeature(
    t,
    bmltwf_admin_multinetwork,
    userVariables.admin_settings_page_multinetwork_plugin,
    userVariables.wp_pages_multinetwork_plugin,
    userVariables.admin_correspondence_json_multinetwork_plugin,
    userVariables.siteurl_multinetwork_plugin
  );
  
  // Test on first site
  await t
    .navigateTo(userVariables.admin_submissions_page_multinetwork_plugin)
    .wait(2000);
  await click_table_row_column(Selector('#dt-submission'), 0, 0);
  await t
    .wait(1000)
    .expect(Selector('#dt-submission_wrapper > div.dt-buttons > button:nth-child(3)').exists)
    .ok('Correspondence button should exist in multisite network site 1')
    .expect(Selector('#dt-submission_wrapper > div.dt-buttons > button:nth-child(3)').hasClass('disabled'))
    .notOk('Correspondence button should be enabled in multisite network site 1')
    
    correspondencePageUrl = await setupCorrespondenceFeature(
    t,
    bmltwf_admin_multinetwork,
    userVariables.admin_settings_page_multinetwork_plugin2,
    userVariables.wp_pages_multinetwork_plugin2,
    userVariables.admin_correspondence_json_multinetwork_plugin2,
    userVariables.siteurl_multinetwork_plugin2
  );

    // Test on second site
    await t.navigateTo(userVariables.admin_submissions_page_multinetwork_plugin2)
    .wait(2000);
    await click_table_row_column(Selector('#dt-submission'), 0, 0);
    await t
    .wait(1000)
    .expect(Selector('#dt-submission_wrapper > div.dt-buttons > button:nth-child(3)').exists)
    .ok('Correspondence button should exist in multisite network site 2')
    .expect(Selector('#dt-submission_wrapper > div.dt-buttons > button:nth-child(3)').hasClass('disabled'))
    .notOk('Correspondence button should be enabled in multisite network site 2');
});

test("MultiSite_Correspondence_Database_Tables", async (t) => {
  // Test that correspondence database tables are created correctly in multisite
  await t
    .useRole(bmltwf_admin_multisingle)
    .navigateTo(userVariables.admin_settings_page_multisingle_plugin);
  await ao.navigateToTab(t, 'advanced');
  await t
    .click(ao.backup_button)
    .wait(3000);
    
  // If backup completes without error, database tables exist
  const backupCompleted = await t.eval(() => {
    const spinner = document.querySelector('#bmltwf-backup-spinner');
    return spinner && spinner.style.display !== 'block';
  });
  
  await t.expect(backupCompleted).ok('Backup should complete successfully, indicating database tables exist');
});
