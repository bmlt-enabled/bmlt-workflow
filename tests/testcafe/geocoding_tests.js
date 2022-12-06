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
  restore_from_backup, 
  reset_bmlt,
  auto_geocoding_off,
  auto_geocoding_on,
  select_dropdown_by_text, 
  click_table_row_column, 
  click_dt_button_by_index, 
  click_dialog_button_by_index, 
  bmltwf_admin, 
   } from "./helpers/helper.js";

import { userVariables } from "../../.testcaferc";

fixture`geocoding_tests_fixture`
.before(async (t) => {
  await reset_bmlt(t);
})
.beforeEach(async (t) => {
  await auto_geocoding_on(t);

  await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,"bmlt2x","8000");

  await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);
});

