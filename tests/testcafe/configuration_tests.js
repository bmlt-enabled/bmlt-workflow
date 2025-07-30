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

import { 
  waitfor,
  restore_from_backup,
  bmltwf_admin,
  myip,
  set_language_single,
   } from "./helpers/helper.js";
  
import { userVariables } from "../../.testcaferc";

fixture`configuration_test_fixture`
  .beforeEach(async (t) => {
    await waitfor(userVariables.admin_logon_page_single);
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3001"),"hidden";  
    await set_language_single(t, "en_EN");
  });

test("Change_BMLT_To_Unsupported_Version", async (t) => {

  await t.navigateTo(userVariables.admin_settings_page_single)
    .click(ao.bmltwf_configure_bmlt_server)

    // save should be disabled
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()
    // test should be enabled
    .expect(ao.bmltwf_bmlt_configuration_test.withAttribute("disabled").exists).notOk()
    .typeText(ao.bmltwf_bmlt_server_address, "http://"+myip()+":3004/main_server/", { replace: true})
    .click(ao.bmltwf_bmlt_configuration_test)
    // wordpress ui bug?
    .click(ao.bmltwf_bmlt_configuration_test)
    .expect(ao.bmltwf_error_class_options_dialog_bmltwf_error_message.innerText).match(/not supported/)
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()

});

test("Change_BMLT_To_Invalid URL", async (t) => {

  await t.navigateTo(userVariables.admin_settings_page_single)
    .click(ao.bmltwf_configure_bmlt_server)

    // save should be disabled
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()
    // test should be enabled
    .expect(ao.bmltwf_bmlt_configuration_test.withAttribute("disabled").exists).notOk()
    .typeText(ao.bmltwf_bmlt_server_address, "oiuoiuoiuoiu", { replace: true})
    .click(ao.bmltwf_bmlt_configuration_test)
    // wordpress ui bug?
    .click(ao.bmltwf_bmlt_configuration_test)
    .expect(ao.bmltwf_error_class_options_dialog_bmltwf_error_message.innerText).match(/missing trailing/)
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()

});

test("Change_BMLT_To_Blank_Username", async (t) => {

  await t.navigateTo(userVariables.admin_settings_page_single)
    .click(ao.bmltwf_configure_bmlt_server)

    // save should be disabled
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()
    // test should be enabled
    .expect(ao.bmltwf_bmlt_configuration_test.withAttribute("disabled").exists).notOk()
    .selectText(ao.bmltwf_bmlt_username)
    .pressKey('delete')

    .click(ao.bmltwf_bmlt_configuration_test)
    // wordpress ui bug?
    .click(ao.bmltwf_bmlt_configuration_test)
    await t.expect(ao.bmltwf_error_class_options_dialog_bmltwf_error_message.innerText).match(/username parameter/)
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()

});

test("Change_BMLT_To_Blank_Password", async (t) => {

  await t.navigateTo(userVariables.admin_settings_page_single)
    .click(ao.bmltwf_configure_bmlt_server)

    // save should be disabled
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()
    // test should be enabled
    .expect(ao.bmltwf_bmlt_configuration_test.withAttribute("disabled").exists).notOk()
    .selectText(ao.bmltwf_bmlt_password)
    .pressKey('delete')
    .click(ao.bmltwf_bmlt_configuration_test)
    // wordpress ui bug?
    .click(ao.bmltwf_bmlt_configuration_test)
    .expect(ao.bmltwf_error_class_options_dialog_bmltwf_error_message.innerText).match(/password parameter/)
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()

});

test("Change_BMLT_To_Working_Version", async (t) => {

  await t.navigateTo(userVariables.admin_settings_page_single)
    .click(ao.bmltwf_configure_bmlt_server)

    // save should be disabled
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).ok()
    // test should be enabled
    .expect(ao.bmltwf_bmlt_configuration_test.withAttribute("disabled").exists).notOk()
    .typeText(ao.bmltwf_bmlt_server_address, "http://"+myip()+":3001/main_server/", { replace: true})
    .typeText(ao.bmltwf_bmlt_username, "username", { replace: true})
    .typeText(ao.bmltwf_bmlt_password, "password", { replace: true})
    .click(ao.bmltwf_bmlt_configuration_test)
    // wordpress ui bug?
    .click(ao.bmltwf_bmlt_configuration_test)
    .expect(ao.bmltwf_error_class_options_dialog_bmltwf_error_message.innerText).match(/succeeded/)
    .expect(ao.bmltwf_bmlt_configuration_save.withAttribute("disabled").exists).notOk()

});
