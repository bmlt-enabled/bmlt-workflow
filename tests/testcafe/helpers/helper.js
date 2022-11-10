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

import { t, Role, Selector } from "testcafe";
import { wordpress_login } from "../models/wordpress_login";
import { userVariables } from "../../../.testcaferc";
import { ao } from "../models/admin_options";
import { asb } from "../models/admin_service_bodies";

export function randstr()
{
  return Math.random().toString(36).replace(/[^a-z]+/g, "") .substr(0, 9);
}

export const bmltwf_admin = Role(userVariables.admin_logon_page, async (t) => {
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon).typeText(wordpress_login.user_pass, userVariables.admin_password).click(wordpress_login.wp_submit);
});

export const bmltwf_submission_reviewer = Role(userVariables.admin_logon_page, async (t) => {
  await t.typeText(wordpress_login.user_login, userVariables.submission_reviewer_user).typeText(wordpress_login.user_pass, userVariables.submission_reviewer_pass).click(wordpress_login.wp_submit);
});

export const bmltwf_submission_nopriv = Role(userVariables.admin_logon_page, async (t) => {
  await t.typeText(wordpress_login.user_login, userVariables.submission_reviewer_nopriv_user).typeText(wordpress_login.user_pass, userVariables.submission_reviewer_nopriv_pass).click(wordpress_login.wp_submit);
});

export const bmltwf_admin_multisingle = Role(userVariables.admin_logon_page_multisingle, async (t) => {
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon_multisingle).typeText(wordpress_login.user_pass, userVariables.admin_password_multisingle).click(wordpress_login.wp_submit);
});

export const bmltwf_admin_multinetwork = Role(userVariables.admin_logon_page_multinetwork, async (t) => {
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon_multinetwork).typeText(wordpress_login.user_pass, userVariables.admin_password_multinetwork).click(wordpress_login.wp_submit);
});

export const bmltwf_admin_wpsinglebmlt3x = Role(userVariables.admin_logon_page_wpsinglebmlt3x, async (t) => {
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon_wpsinglebmlt3x).typeText(wordpress_login.user_pass, userVariables.admin_password_wpsinglebmlt3x).click(wordpress_login.wp_submit);
});

export async function select_dropdown_by_id(element, id) {
  await t.click(element).click(element.find("option").withAttribute("id", id));
}

export async function select_dropdown_by_text(element, text) {
  await t.click(element).click(element.find("option").withText(text));
}

export async function select_dropdown_by_value(element, value) {
  await t.click(element).click(element.find("option").withAttribute("value", value));
}

export async function select_select2_dropdown_by_value(element, value) {
  await t.click(element).click(element.find("option").withAttribute("value", value));
}

export async function click_table_row_column(element, row, column) {
  const g = element.child("tbody").child(row).child(column);

  await t.click(g);
}

export async function click_dt_button_by_index(element, index) {
  const g = element.find("button").nth(index);

  await t.click(g);
}

export async function get_table_row_col(element, row, column) {
  return element.child("tbody").child(row).child(column);
}

export async function click_dialog_button_by_index(element, index) {
  const g = element.find("button").nth(index);

  await t.click(g);
}

export async function reset_bmlt(t) {
  console.log("resetting bmlt");
  await t.request(userVariables.blank_bmlt);
  await t.wait(5000);
  console.log("reset");

}

export async function reset_bmlt3x(t) {
  console.log("resetting bmlt");
  await t.request(userVariables.blank_bmlt3x);
  await t.wait(5000);
  console.log("reset");

}

export async function auto_geocoding_on(t) {
console.log("turning geocode on");
  await t.request(userVariables.auto_geocoding_on);
  await t.wait(5000);
  console.log("geocode on");
}

export async function auto_geocoding_off(t) {
  console.log("turning geocode off");
  await t.request(userVariables.auto_geocoding_off);
  await t.wait(5000);
  console.log("geocode off");

}

export async function bmlt3x_auto_geocoding_on(t) {
  console.log("turning geocode on");
    await t.request(userVariables.bmlt3x_auto_geocoding_on);
    await t.wait(5000);
    console.log("geocode on");
  }
  
  export async function bmlt3x_auto_geocoding_off(t) {
    console.log("turning geocode off");
    await t.request(userVariables.bmlt3x_auto_geocoding_off);
    await t.wait(5000);
    console.log("geocode off");
  
  }
  
export async function insert_submissions() {
  // pre fill the submissions
  await t.request(userVariables.admin_submission_reset);
}

export async function insert_submissions_multisingle() {
  // pre fill the submissions
  await t.request(userVariables.admin_submission_reset_multisingle);
}

export async function insert_submissions_multinetwork() {
  // pre fill the submissions
  await t.request(userVariables.admin_submission_reset_multinetwork);
}

export async function configure_service_bodies(t) {
  await t.request(userVariables.blank_service_bodies);

  await t
    .useRole(bmltwf_admin)
    .navigateTo(userVariables.admin_service_bodies_page)

    .click(Selector("ul#select2-bmltwf_userlist_id_1-container").parent())
    .pressKey("enter")
    .click(Selector("ul#select2-bmltwf_userlist_id_1-container").parent())
    .typeText(Selector('[aria-controls="select2-bmltwf_userlist_id_1-results"]'), "submitpriv")
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_1")
    .click(Selector("ul#select2-bmltwf_userlist_id_2-container").parent())
    .pressKey("enter")
    .click(Selector("ul#select2-bmltwf_userlist_id_2-container").parent())
    .typeText(Selector('[aria-controls="select2-bmltwf_userlist_id_2-results"]'), "submitpriv")
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_2")
    .click(Selector("ul#select2-bmltwf_userlist_id_3-container").parent())
    .pressKey("enter")
    .click(Selector("ul#select2-bmltwf_userlist_id_3-container").parent())
    .typeText(Selector('[aria-controls="select2-bmltwf_userlist_id_3-results"]'), "submitpriv")
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_3")
    .click(asb.bmltwf_submit);
}

export async function configure_service_bodies_multisingle(t) {
  await t.request(userVariables.blank_service_bodies_multisingle);

  await t
    .useRole(bmltwf_admin_multisingle)
    .navigateTo(userVariables.admin_service_bodies_page_multisingle_plugin)

    .click(Selector("ul#select2-bmltwf_userlist_id_1-container").parent())
    .pressKey("enter")

    .click("#bmltwf_userlist_checkbox_id_1")
    .click(Selector("ul#select2-bmltwf_userlist_id_2-container").parent())
    .pressKey("enter")

    .click("#bmltwf_userlist_checkbox_id_2")
    .click(Selector("ul#select2-bmltwf_userlist_id_3-container").parent())
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_3")
    .click(asb.bmltwf_submit);
}

export async function configure_service_bodies_multinetwork(t) {
  await t.request(userVariables.blank_service_bodies_multinetwork);

  await t
    .useRole(bmltwf_admin_multinetwork)
    .navigateTo(userVariables.admin_service_bodies_page_multinetwork_plugin)

    .click(Selector("ul#select2-bmltwf_userlist_id_1-container").parent())
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_1")
    .click(Selector("ul#select2-bmltwf_userlist_id_2-container").parent())
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_2")
    .click(Selector("ul#select2-bmltwf_userlist_id_3-container").parent())
    .pressKey("enter")
    .click("#bmltwf_userlist_checkbox_id_3")
    .click(asb.bmltwf_submit);
}

export async function configure_service_bodies_wpsinglebmlt3x(t) {
    await t.request(userVariables.blank_service_bodies_wpsinglebmlt3x);
  
    await t
      .useRole(bmltwf_admin)
      .navigateTo(userVariables.admin_service_bodies_page_wpsinglebmlt3x)
  
      .click(Selector("ul#select2-bmltwf_userlist_id_1-container").parent())
      .pressKey("enter")
      .click(Selector("ul#select2-bmltwf_userlist_id_1-container").parent())
      .typeText(Selector('[aria-controls="select2-bmltwf_userlist_id_1-results"]'), "submitpriv")
      .pressKey("enter")
      .click("#bmltwf_userlist_checkbox_id_1")
      .click(Selector("ul#select2-bmltwf_userlist_id_2-container").parent())
      .pressKey("enter")
      .click(Selector("ul#select2-bmltwf_userlist_id_2-container").parent())
      .typeText(Selector('[aria-controls="select2-bmltwf_userlist_id_2-results"]'), "submitpriv")
      .pressKey("enter")
      .click("#bmltwf_userlist_checkbox_id_2")
      .click(Selector("ul#select2-bmltwf_userlist_id_3-container").parent())
      .pressKey("enter")
      .click(Selector("ul#select2-bmltwf_userlist_id_3-container").parent())
      .typeText(Selector('[aria-controls="select2-bmltwf_userlist_id_3-results"]'), "submitpriv")
      .pressKey("enter")
      .click("#bmltwf_userlist_checkbox_id_3")
      .click(asb.bmltwf_submit);
  }

// set a from email address, turn off the optional settings and submit
export async function basic_options() {
  await t
    .useRole(bmltwf_admin)
    .navigateTo(userVariables.admin_settings_page)
    .typeText(ao.bmltwf_email_from_address, "testing@test.org.zz", { replace: true })
    .typeText(ao.bmltwf_fso_email_address, "testing@test.org.zz", { replace: true });

  await uncheck_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);

  await t.click(ao.submit);
  await ao.settings_updated();
}

export async function basic_options_multisingle() {
  await t
    .useRole(bmltwf_admin_multisingle)
    .navigateTo(userVariables.admin_settings_page_multisingle_plugin)
    .typeText(ao.bmltwf_email_from_address, "testing@test.org.zz", { replace: true })
    .typeText(ao.bmltwf_fso_email_address, "testing@test.org.zz", { replace: true });

  await uncheck_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);

  await t.click(ao.submit);
  await ao.settings_updated();
}

export async function basic_options_multinetwork() {
  await t
    .useRole(bmltwf_admin_multinetwork)
    .navigateTo(userVariables.admin_settings_page_multinetwork_plugin)
    .typeText(ao.bmltwf_email_from_address, "testing@test.org.zz", { replace: true })
    .typeText(ao.bmltwf_fso_email_address, "testing@test.org.zz", { replace: true });

  await uncheck_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);

  await t.click(ao.submit);
  await ao.settings_updated();
  await t
    .navigateTo(userVariables.admin_settings_page_multinetwork_plugin2)
    .typeText(ao.bmltwf_email_from_address, "testing@test.org.zz", { replace: true })
    .typeText(ao.bmltwf_fso_email_address, "testing@test.org.zz", { replace: true });

  await uncheck_checkbox(t,ao.bmltwf_optional_location_nation_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_province_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_location_province_required_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_location_sub_province_visible_checkbox);
  await check_checkbox(t,ao.bmltwf_optional_postcode_visible_checkbox);
  await uncheck_checkbox(t,ao.bmltwf_optional_postcode_required_checkbox);

  await t.click(ao.submit);
  await ao.settings_updated();
}

export async function bmlt_states_off(t) {
  // disable state dropdown
  console.log("turning states off");
  await t.request(userVariables.bmlt_states_off);
  await t.wait(5000);
  console.log("states off");

}

export async function bmlt_states_on(t) {
  // enable state dropdown
  console.log("turning states on");
  await t.request(userVariables.bmlt_states_on);
  await t.wait(5000);
  console.log("states on");
}

export async function bmlt3x_states_off(t) {
  // disable state dropdown
  console.log("turning states off");
  await t.request(userVariables.bmlt3x_states_off);
  await t.wait(5000);
  console.log("states off");

}

export async function bmlt3x_states_on(t) {
  // enable state dropdown
  console.log("turning states on");
  await t.request(userVariables.bmlt3x_states_on);
  await t.wait(5000);
  console.log("states on");
}

export async function delete_submissions(t) {
  await t.request(userVariables.blank_submission);
}

export async function delete_submissions_multisingle(t) {
  await t.request(userVariables.blank_submission_multisingle);
}

export async function delete_submissions_multinetwork(t) {
  await t.request(userVariables.blank_submission_multinetwork);
}

export async function delete_submissions_wpsinglebmlt3x(t) {
  await t.request(userVariables.blank_submission);
}

export async function check_checkbox(t,s) {
  var state = await s();
  if (!state.checked)
  {
    await t.click(s);
  }
}

export async function uncheck_checkbox(t,s) {
  var state = await s();
  if (state.checked)
  {
    await t.click(s);
  }
}
