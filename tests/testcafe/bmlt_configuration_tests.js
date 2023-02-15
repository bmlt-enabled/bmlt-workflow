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
import { uf } from "./models/meeting_update_form";
import { Selector  } from "testcafe";

import { 
  waitfor,
  restore_from_backup,
  click_table_row_column, 
  click_dt_button_by_index, 
  click_dialog_button_by_index, 
  select_dropdown_by_text, 
  select_dropdown_by_value, 
  bmltwf_admin,
  myip
   } from "./helpers/helper.js";
  
import { userVariables } from "../../.testcaferc";

fixture`bmlt_configuration_test_fixture`
  .beforeEach(async (t) => {
    await waitfor(userVariables.admin_logon_page_single);
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3000"),"hidden";  
  });

test("Change BMLT To Unsupported Version", async (t) => {
  await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3004"),"hidden";  

  await t.navigateTo(userVariables.admin_options_page_single);


});
