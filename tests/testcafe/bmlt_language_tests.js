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

import { uf } from "./models/meeting_update_form";
import { ao } from "./models/admin_options"

import { Selector, Role, ClientFunction } from "testcafe";

import { 
  waitfor,
  restore_from_backup,
  bmltwf_admin,
  myip,
  set_language_single,
  select_dropdown_by_value,
   } from "./helpers/helper.js";
  
import { userVariables } from "../../.testcaferc";

fixture`bmlt3x_language_test_fixture`
  .beforeEach(async (t) => {
    await waitfor(userVariables.admin_logon_page_single);
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3001"),"hidden";  
  });

test("Change_Wordpress_To_French_Check_User_Translations", async (t) => {
  await set_language_single(t, "fr_FR");

  await t.useRole(Role.anonymous());
  await t.navigateTo(userVariables.formpage);

  var selector = uf.update_reason;

  var getText = ClientFunction((index) => {
    const select = selector();
    return select.options[index].text;
    }, { dependencies: { selector } });

    // check we've translated the php file
    await t.expect(getText(1)).eql('Nouvelle réunion')
    await select_dropdown_by_value(uf.update_reason,'reason_change');

    // meeting selector
    await t.click("#select2-meeting-searcher-container");
    // check we've translated js on the fly
    await t.expect(Selector('.select2-search__field').withAttribute('placeholder','Commencez à saisir le nom de votre réunion').exists).ok();
    // search for our meeting
    await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "lifeline");
    await t.pressKey("enter");

    selector = uf.display_format_shared_id_list;

    // check we've translated a getformat call correctly
    getText = ClientFunction((index) => {
      const select = selector();
      return select.options[index].text;
      }, { dependencies: { selector } });

    await t.expect(getText(0)).eql('(B)-Débutants')
});

test("Change_Wordpress_To_English_Check_User_Translations", async (t) => {
  await set_language_single(t, "en_EN");

  await t.useRole(Role.anonymous());
  await t.navigateTo(userVariables.formpage);

  var selector = uf.update_reason;

  var getText = ClientFunction((index) => {
    const select = selector();
    return select.options[index].text;
    }, { dependencies: { selector } });

    // check we've translated the php file
    await t.expect(getText(1)).eql('New Meeting')
    await select_dropdown_by_value(uf.update_reason,'reason_change');

    // meeting selector
    await t.click("#select2-meeting-searcher-container");
    // check we've translated js on the fly
    await t.expect(Selector('.select2-search__field').withAttribute('placeholder','Begin typing your meeting name').exists).ok();
    // search for our meeting
    await t.typeText(Selector('[aria-controls="select2-meeting-searcher-results"]'), "lifeline");
    await t.pressKey("enter");

    selector = uf.display_format_shared_id_list;

    // check we've translated a getformat call correctly
    getText = ClientFunction((index) => {
      const select = selector();
      return select.options[index].text;
      }, { dependencies: { selector } });

    await t.expect(getText(0)).eql('(B)-Beginners')

});

test("Change_Wordpress_To_French_Check_Admin_Translations", async (t) => {
  await set_language_single(t, "fr_FR");

  await t.useRole(bmltwf_admin);
  await t.navigateTo(userVariables.admin_settings_page_single);

    // check we've translated the php file
    await t.expect(ao.backup_button.withText('Configuration de sauvegarde').exists).ok();

});
