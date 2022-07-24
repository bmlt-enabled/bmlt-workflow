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

import { t, Role, Selector } from 'testcafe';
import { wordpress_login } from '../models/wordpress_login';
import { userVariables } from "../../../.testcaferc";
import { ao } from "../models/admin_options";
import { asb } from "../models/admin_service_bodies";

export const wbw_admin = Role(userVariables.admin_logon_page, async t => {
    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
});

export async function select_dropdown_by_id(element, id) {
    await t
    .click(element)
    .click((element).find('option').withAttribute('id',id))
};

export async function select_dropdown_by_text(element, text) {
    await t
    .click(element)
    .click((element).find('option').withText(text))
};

export async function select_dropdown_by_value(element, value) {
    await t
    .click(element)
    .click((element).find('option').withAttribute('value', value))
};

export async function select_select2_dropdown_by_value(element, value) {
    await t
    .click(element)
    .click((element).find('option').withAttribute('value', value))
};

export async function click_table_row_column(element, row, column) {
    const g = element.child('tbody').child(row).child(column);

    await t 
    .click(g);
};

export async function click_dt_button_by_index(element, index) {
    const g = element.find('button').nth(index);

    await t 
    .click(g);
};

export async function get_table_row_col(element, row, column) {
    return element.child('tbody').child(row).child(column);
};

export async function click_dialog_button_by_index(element, index) {
    const g = element.find('button').nth(index);

    await t 
    .click(g);
};

export async function reset_bmlt()
{
    var http = require("http");
    // reset bmlt to reasonable state
    http.get(userVariables.blank_bmlt);
}

export async function insert_submissions()
{
    var http = require("http");
    // pre fill the submissions
    http.get(userVariables.admin_submission_reset);
}

export async function configure_service_bodies()
{
    var http = require("http");
    http.get(userVariables.blank_service_bodies);

    await t.useRole(wbw_admin).navigateTo(userVariables.admin_service_bodies_page)
    
    .click(Selector('ul#select2-wbw_userlist_id_1-container').parent())
    .pressKey("enter")
    .click("#wbw_userlist_checkbox_id_1")
    .click(Selector('ul#select2-wbw_userlist_id_2-container').parent())
    .pressKey("enter")
    .click("#wbw_userlist_checkbox_id_2")
    .click(Selector('ul#select2-wbw_userlist_id_3-container').parent())
    .pressKey("enter")
    .click("#wbw_userlist_checkbox_id_3")
    .click(asb.wbw_submit);

}

// set a from email address, turn off the optional settings and submit
export async function basic_options()
{
    
    await t.useRole(wbw_admin).navigateTo(userVariables.admin_options_page)
    .typeText(ao.wbw_email_from_address, "testing@test.org.zz", { replace: true })
    .typeText(ao.wbw_fso_email_address, "testing@test.org.zz", { replace: true });
    
    await select_dropdown_by_text(ao.wbw_optional_location_nation, "Hidden");
    await select_dropdown_by_text(ao.wbw_optional_location_sub_province, "Hidden");
    await select_dropdown_by_text(ao.wbw_optional_postcode, "Display");

    await t.click(ao.submit);
    await ao.settings_updated();  
}

export async function bmlt_states_off()
{
    var http = require("http");
    // disable state dropdown
    http.get(userVariables.bmlt_states_off);
  
}

export async function bmlt_states_on()
{
    var http = require("http");
    // enable state dropdown
    http.get(userVariables.bmlt_states_on);
  
}

export async function delete_submissions()
{
    var http = require("http");
    http.get(userVariables.blank_submission);
}