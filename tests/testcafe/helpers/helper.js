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
import { CURRENT_DB_VERSION } from "./db_version.js";

const execSync = require("child_process").execSync;

export function randstr() {
  return Math.random()
    .toString(36)
    .replace(/[^a-z]+/g, "")
    .substr(0, 9);
}

export const bmltwf_admin = Role(userVariables.admin_logon_page_single, async (t) => {
  await t.click(wordpress_login.user_login);
  // console.log("trying to log on to "+userVariables.admin_logon_page_single+" using username "+userVariables.admin_password_single+" password "+userVariables.admin_password_single);
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon_single).typeText(wordpress_login.user_pass, userVariables.admin_password_single).click(wordpress_login.wp_submit);
  // await t.expect(wordpress_login.user_login.value).eql(userVariables.admin_logon_single);
});

export const bmltwf_submission_reviewer = Role(userVariables.admin_logon_page_single, async (t) => {
  await t.click(wordpress_login.user_login);
  await t.typeText(wordpress_login.user_login, userVariables.submission_reviewer_user).typeText(wordpress_login.user_pass, userVariables.submission_reviewer_pass).click(wordpress_login.wp_submit);
});

export const bmltwf_submission_nopriv = Role(userVariables.admin_logon_page_single, async (t) => {
  await t.click(wordpress_login.user_login);
  await t
    .typeText(wordpress_login.user_login, userVariables.submission_reviewer_nopriv_user)
    .typeText(wordpress_login.user_pass, userVariables.submission_reviewer_nopriv_pass)
    .click(wordpress_login.wp_submit);
});

export const bmltwf_admin_multisingle = Role(userVariables.admin_logon_page_multisingle, async (t) => {
  await t.click(wordpress_login.user_login);
  // console.log("trying to log on to "+userVariables.admin_logon_page_multisingle+" using username "+userVariables.admin_password_multisingle+" password "+userVariables.admin_password_multisingle);
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon_multisingle).typeText(wordpress_login.user_pass, userVariables.admin_password_multisingle).click(wordpress_login.wp_submit);
  // await t.expect(wordpress_login.user_login.value).eql(userVariables.admin_logon_multisingle);

});

export const bmltwf_admin_multinetwork = Role(userVariables.admin_logon_page_multinetwork, async (t) => {
  await t.click(wordpress_login.user_login);
  // console.log("trying to log on to "+userVariables.admin_logon_page_multinetwork+" using username "+userVariables.admin_password_multinetwork+" password "+userVariables.admin_password_multinetwork);
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon_multinetwork).typeText(wordpress_login.user_pass, userVariables.admin_password_multinetwork).click(wordpress_login.wp_submit);
});

export const bmltwf_dbupgrade_admin = Role(userVariables.admin_logon_page_dbupgrade, async (t) => {
  await t.click(wordpress_login.user_login);
  await t.typeText(wordpress_login.user_login, userVariables.admin_logon_single).typeText(wordpress_login.user_pass, userVariables.admin_password_single).click(wordpress_login.wp_submit);
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

export function myip(){
  return execSync("ipconfig getifaddr en0").toString().trim();
}

export async function waitfor(site) {
  // console.log("waiting for "+site);
  execSync(userVariables.waitfor + " " + site);
}

export async function restore_from_backup(role, settings_page, restore_json, host, port, subprovince, custom_data = null) {
  let restorebody;
  
  if (custom_data) {
    restorebody = custom_data;
    if (restorebody.options && restorebody.options.bmltwf_bmlt_server_address) {
      restorebody.options.bmltwf_bmlt_server_address = "http://" + host + ":" + port + "/main_server/";
    }
  } else {
    // Use hardcoded default
    restorebody = {
    options: {
      bmltwf_email_from_address: "example@example.com",
      bmltwf_delete_closed_meetings: "unpublish",
      bmltwf_optional_location_nation: "hidden",
      bmltwf_optional_location_nation_displayname: "Nation",
      bmltwf_optional_location_sub_province: subprovince,
      bmltwf_optional_location_sub_province_displayname: "Sub Province",
      bmltwf_optional_location_province: "display",
      bmltwf_optional_location_province_displayname: "Province",
      bmltwf_optional_postcode: "display",
      bmltwf_optional_postcode_displayname: "Postcode",
      bmltwf_required_meeting_formats: "true",
      bmltwf_trusted_servants_can_delete_submissions: "true",
      bmltwf_google_maps_key: "",
      bmltwf_submitter_email_template:
        '<p><br>Thank you for submitting the online meeting update.<br>We will usually be able action your\n    request within 48 hours.<br>Our process also updates NA websites around Australia and at NA World Services.<br>\n</p>\n<hr>What was submitted: <br><br>\n<table class="blueTable" style="border: 1px solid #1C6EA4;background-color: #EEEEEE;text-align: left;border-collapse: collapse;">\n    <thead style="background: #1C6EA4;border-bottom: 2px solid #444444;">\n        <tr>\n            <th style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: none;">\n                <br>Field Name\n            </th>\n            <th style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: 2px solid #D0E4F5;">\n                <br>Value\n            </th>\n        </tr>\n    </thead>\n    <tbody>\n        {field:submission}\n    </tbody>\n</table>\n\n',
      bmltwf_fso_email_template:
        '<p>Attn: FSO.<br>\nPlease send a starter kit to the following meeting:\n</p>\n<hr><br>\n<table class="blueTable" style="border: 1px solid #1C6EA4;background-color: #EEEEEE;text-align: left;border-collapse: collapse;">\n    <thead style="background: #1C6EA4;border-bottom: 2px solid #444444;">\n        <tr>\n            <th style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: none;">\n                <br>Field Name\n            </th>\n            <th style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 14px;font-weight: bold;color: #FFFFFF;border-left: 2px solid #D0E4F5;">\n                <br>Value\n            </th>\n        </tr>\n    </thead>\n    <tbody>\n        <tr>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">Group Name</td>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">{field:name}</td>\n        </tr>\n        <tr>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">Requester First Name</td>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">{field:first_name}</td>\n        </tr>\n        <tr>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">Requester Last Name</td>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">{field:last_name}</td>\n        </tr>\n        <tr>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">Starter Kit Postal Address</td>\n            <td style="border: 1px solid #AAAAAA;padding: 3px 2px;font-size: 13px;">{field:starter_kit_postal_address}\n            </td>\n        </tr>\n    </tbody>\n</table>\n',
      bmltwf_fso_email_address: "example@example.com",
      bmltwf_fso_feature: "display",
      bmltwf_db_version: "1.1.27",
      bmltwf_bmlt_server_address: "http://" + host + ":" + port + "/main_server/",
      bmltwf_bmlt_username: "bmlt-workflow-bot",
      bmltwf_bmlt_test_status: "success",
      bmltwf_bmlt_password:
        'a:2:{s:6:"config";a:6:{s:4:"size";s:4:"MzI=";s:4:"salt";s:24:"/5ObzNuYZ/Y5aoYTsr0sZw==";s:9:"limit_ops";s:4:"OA==";s:9:"limit_mem";s:12:"NTM2ODcwOTEy";s:3:"alg";s:4:"Mg==";s:5:"nonce";s:16:"VukDVzDkAaex/jfB";}s:9:"encrypted";s:44:"fertj+qRqQrs9tC+Cc32GrXGImHMfiLyAW7sV6Xojw==";}',
    },
    submissions: [
      {
        change_id: "93",
        submission_time: "2022-05-15 12:32:38",
        change_time: "0000-00-00 00:00:00",
        changed_by: null,
        change_made: null,
        submitter_name: "first last",
        submission_type: "reason_new",
        submitter_email: "test@test.com.zz",
        id: "0",
        serviceBodyId: "1047",
        changes_requested: 
        '{"name":"my test meeting","startTime":"10:40","duration":"04:30","location_text":"my location","location_street":"110 avoca st","location_info":"info","location_municipality":"Randwick","location_province":"NSW","location_postal_code_1":"2031","day":4,"serviceBodyId":1009,"formatIds":[2,5],"contact_number":"12345","group_relationship":"Group Member","add_contact":"yes","additional_info":"some extra info","virtual_meeting_additional_info":"Zoom ID 83037287669 Passcode: testing","phone_meeting_number":"12345","virtual_meeting_link":"https:\\\/\\\/us02web.zoom.us\\\/j\\\/83037287669?pwd=OWRRQU52ZC91TUpEUUExUU40eTh2dz09","starter_kit_required":"no","venueType":3,"comments":"Test meeting comments for new meeting"}',
        action_message: null,
      },
      {
        change_id: "94",
        submission_time: "2023-02-13 11:24:59",
        change_time: "0000-00-00 00:00:00",
        changed_by: null,
        change_made: null,
        submitter_name: "first l",
        submission_type: "reason_change",
        submitter_email: "test@example.com",
        id: "2562",
        serviceBodyId: "1009",
        changes_requested: '{"name":"update","original_name":"2nd Chance Group","original_startTime":"18:30","original_duration":"01:30","location_text":"update location","original_location_street":"360 Warren Street","original_location_municipality":"Hudson","original_location_province":"NY","original_location_nation":"US","original_location_sub_province":"Columbia","original_day":3,"original_serviceBodyId":"1009","original_formatIds":[3,17,36],"original_venueType":"1","contact_number":"12345","group_relationship":"Group Member","add_contact":"yes","additional_info":"please action asap","comments":"Updated meeting comments"}',
        action_message: null
      },
      {
        change_id: "95",
        submission_time: "2023-02-13 11:28:23",
        change_time: "0000-00-00 00:00:00",
        changed_by: null,
        change_made: null,
        submitter_name: "oiu oiu",
        submission_type: "reason_close",
        submitter_email: "oiu@oiu.com",
        id: "2562",
        serviceBodyId: "1009",
        changes_requested: '{"contact_number":"","group_relationship":"Group Member","add_contact":"yes","serviceBodyId":1009,"additional_info":"close it now","name":"2nd Chance Group","day":3,"startTime":"18:30"}',
        action_message: null
    }
  ],
    service_bodies: [
      {
        serviceBodyId: "1009",
        service_body_name: "Mid-Hudson Area Service",
        service_body_description: "Area Service Serving Counties North of Westchester.",
        show_on_form: "1",
      },
      {
        serviceBodyId: "1046",
        service_body_name: "ABCD Region",
        service_body_description: "North Hudson Valley Area, including some of Western Mass.",
        show_on_form: "1",
      },
      {
        serviceBodyId: "1047",
        service_body_name: "Albany-Rensselaer Area",
        service_body_description: "",
        show_on_form: "1",
      },
      {
        serviceBodyId: "1048",
        service_body_name: "Berkshire County Area",
        service_body_description: "",
        show_on_form: "0",
      },
      {
        serviceBodyId: "1049",
        service_body_name: "Mohawk River Area",
        service_body_description: "",
        show_on_form: "0",
      },
      {
        serviceBodyId: "1050",
        service_body_name: "Southern Adirondack Mountain Miracles Area",
        service_body_description: "",
        show_on_form: "1",
      },
      {
        serviceBodyId: "1051",
        service_body_name: "Green Mountain Area",
        service_body_description: "",
        show_on_form: "0",
      },
    ],
    service_bodies_access: [
      {
        serviceBodyId: "1009",
        wp_uid: "2",
      },
      {
        serviceBodyId: "1046",
        wp_uid: "2",
      },
      {
        serviceBodyId: "1047",
        wp_uid: "2",
      },
      {
        serviceBodyId: "1050",
        wp_uid: "2",
      },
    ],
  };
  }

  await t.useRole(role).navigateTo(settings_page);
  // Navigate to advanced tab to ensure nonce field is accessible
  const { ao } = await import('../models/admin_options');
  await ao.navigateToTab(t, 'advanced');
  
  let my_cookies = await t.getCookies();
  let cookieHeader = my_cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');

  // Wait for the nonce element to be available
  await t.expect(Selector("#_wprestnonce").exists).ok();
  const nonce = await Selector("#_wprestnonce").value;
  
  const resp = await t.request({
    url: restore_json,
    method: "POST",
    withCredentials: true, 
    body: restorebody,
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": nonce,
      "Cookie": cookieHeader
    },
  });

  // console.log("nonce = "+nonce);
  // console.log("SETTINGS PAGE");
  // console.log(settings_page);
  // console.log("RESTORE JSON");
  // console.log(restore_json);
  // console.log(resp);
  // await t.debug();

}

export async function check_checkbox(t, s) {
  var state = await s();
  if (!state.checked) {
    await t.click(s);
  }
}

export async function uncheck_checkbox(t, s) {
  var state = await s();
  if (state.checked) {
    await t.click(s);
  }
}

export async function set_language_single(t, lang)
{
  await t.navigateTo(userVariables.wordpress_general_options_single)
  if(lang === "en_EN")
  {
    await select_dropdown_by_text(Selector("#WPLANG"),"English (United States)");
  }
  else
  {
    await select_dropdown_by_value(Selector("#WPLANG"),lang);
  }
  await t.click(Selector("#submit"));
}

export async function setupCorrespondenceFeature(t, role = bmltwf_admin, settingsPage = userVariables.admin_settings_page_single, wpPagesUrl = userVariables.wp_pages_single, correspondenceJsonUrl = userVariables.admin_correspondence_json_single, siteUrl = userVariables.siteurl_single) {
  await t.useRole(role);
  
  const cookies = await t.getCookies();
  const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
  
  await t.navigateTo(settingsPage);
  // Navigate to form settings tab for correspondence page configuration
  const { ao } = await import('../models/admin_options');
  await ao.navigateToTab(t, 'form-settings');
  // Wait for the nonce element to be available
  await t.expect(Selector('#_wprestnonce').exists).ok();
  const nonce = await Selector('#_wprestnonce').value;
  
  let pageId;
  try {
    // Search for existing published pages with correspondence shortcode
    const allPages = await t.request({
      url: `${wpPagesUrl}&status=publish&per_page=100`,
      method: 'GET',
      headers: {
        'Cookie': cookieHeader
      }
    });
    let existingPage = null;
    // Handle both array and object responses
    let pages = [];
    if (Array.isArray(allPages.body)) {
      pages = allPages.body;
    } else if (allPages.body && typeof allPages.body === 'object') {
      // Check if it's an error response
      if (allPages.body.code && allPages.body.message) {
        pages = []; // No pages found due to error
      } else if (allPages.body.data && Array.isArray(allPages.body.data)) {
        pages = allPages.body.data;
      } else {
        // Convert object values to array if they look like page objects
        pages = Object.values(allPages.body).filter(item => 
          item && typeof item === 'object' && item.id && item.content
        );
      }
    }
        
    existingPage = pages.find(page => {
      const hasCorrespondenceTitle = page.title?.rendered === 'Correspondence';
      const hasShortcodeInRendered = page.content?.rendered?.includes('[bmltwf-correspondence-form]');
      return hasCorrespondenceTitle || hasShortcodeInRendered;
    });
    if (existingPage) {
      pageId = existingPage.id;
    } else {
      // Create new page only if none exists
      const pageData = {
        title: 'Correspondence',
        content: '[bmltwf-correspondence-form]',
        status: 'publish'
      };
      
      const pageResponse = await t.request({
        url: wpPagesUrl,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Cookie': cookieHeader,
          'X-WP-Nonce': nonce,
        },
        body: pageData
      });
      pageId = pageResponse.body?.id;
      
      if (!pageId) {
        throw new Error(`Page creation failed. Status: ${pageResponse.status}`);
      }
    }
  } catch (error) {
    throw new Error(`Failed to setup correspondence page: ${error.message}`);
  }
  
  // Configure the correspondence feature with the page ID
  await t.request({
    url: correspondenceJsonUrl,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
      'Cookie': cookieHeader
    },
    body: {
      page_id: pageId.toString()
    }
  });
  
  return `${siteUrl}/?page_id=${pageId}`;
}