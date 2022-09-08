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

import { bmltwf_admin_multisingle } from "./helpers/helper";
import { wordpress_options } from "./models/wordpress_options";
import { userVariables } from "../../.testcaferc";
import { ao } from "./models/admin_options";
import { t, Selector } from "testcafe";

fixture`multisite_tests_fixture`.beforeEach(async (t) => {});

test("MultiSite_Single_Check_Options", async (t) => {
  // check that our options are installed only for sites that have the plugin enabled
  await t
    .useRole(bmltwf_admin_multisingle)
    .navigateTo(userVariables.admin_options_page_multisingle_plugin)
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
    .navigateTo(userVariables.admin_settings_page_multisingle_plugin)
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
    .useRole(bmltwf_admin_multisingle)
    .navigateTo(userVariables.admin_settings_page_multisingle_plugin)
    // we should see the email from address field in a working plugin
    .expect(ao.bmltwf_email_from_address.exists)
    .eql(true)
    .navigateTo(userVariables.admin_settings_page_multisingle_plugin2)
    // we should see the email from address field in a working plugin
    .expect(ao.bmltwf_email_from_address.exists)
    .eql(true);
});
