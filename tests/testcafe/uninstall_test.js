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

import { Selector } from "testcafe";
import { bmltwf_admin, restore_from_backup, myip } from "./helpers/helper.js";
import { userVariables } from "../../.testcaferc";

fixture`uninstall_test_fixture`
  .beforeEach(async (t) => {
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single, userVariables.admin_restore_json, myip(), "3001");
  });

test.skip("Uninstall_Plugin_And_Verify_Cleanup", async (t) => {
  // Set up handler for native confirm dialog
  await t.setNativeDialogHandler(() => true);

  // Navigate to plugins page
  await t.useRole(bmltwf_admin).navigateTo(userVariables.siteurl_single + "/wp-admin/plugins.php");

  // Find the BMLT Workflow plugin row
  const pluginRow = Selector('tr[data-slug="bmlt-workflow"]');
  await t.expect(pluginRow.exists).ok("Plugin should be installed");

  // Click deactivate link
  const deactivateLink = pluginRow.find('a').withText('Deactivate');
  await t.click(deactivateLink);

  // Wait for deactivation
  await t.expect(Selector('.updated').withText('Plugin deactivated').exists).ok("Plugin should be deactivated");

  // Click delete link
  const deleteLink = pluginRow.find('a').withText('Delete');
  await t.click(deleteLink);

  // Confirm deletion in the modal (if it appears)
  const submitButton = Selector('#submit');
  if (await submitButton.exists) {
    await t.click(submitButton);
  }

  // Wait for deletion confirmation
  await t.expect(Selector('.updated').withText('deleted').exists).ok("Plugin should be deleted", { timeout: 10000 });

  // Verify plugin is removed from plugin list
  await t.expect(pluginRow.exists).notOk("Plugin should not appear in plugin list");

  // Verify BMLT Workflow menu is removed from sidebar
  const bmltwfMenu = Selector('#toplevel_page_bmltwf-settings, #toplevel_page_bmltwf-submissions');
  await t.expect(bmltwfMenu.exists).notOk("BMLT Workflow menu should be removed from sidebar");

  // Navigate to options page to verify options are deleted
  await t.navigateTo(userVariables.admin_options_page_single);

  // Search for any bmltwf options in the page content
  const pageContent = Selector('body').innerText;
  const hasBmltwfOptions = await pageContent;
  
  // Check that no bmltwf_ options exist
  await t.expect(hasBmltwfOptions).notContains('bmltwf_', "No bmltwf options should remain in wp_options");
});
