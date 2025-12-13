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

import { userVariables } from "../../.testcaferc";
import { bmltwf_admin, restore_from_backup, myip, set_language_single, setupCorrespondenceFeature, get_table_row_col, click_dialog_button_by_index, click_dt_button_by_index } from './helpers/helper';
import { cs } from './models/correspondence';
import { as } from './models/admin_submissions';
import { Selector } from 'testcafe';

fixture`Correspondence_E2E_Workflow`
    .beforeEach(async t => {
        await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3001","hidden");
        await set_language_single(t, "en_EN");

        // Create correspondence page and configure correspondence feature
        t.ctx.correspondencePageUrl = await setupCorrespondenceFeature(t);

        await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);
    });

test('E2E_Admin_Initiates_Then_User_Responds', async t => {
    const adminMessage = 'Admin initial message';
    const userResponse = 'User response to admin message';
    
    // Step 1: Admin initiates correspondence
    await t.click(cs.firstRow);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 2: Verify status changed to correspondence_sent
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Sent");
    
    // Step 3: Get thread_id from submissions API after correspondence is created
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    const nonce = await Selector('#_wprestnonce').value;
    
    const submissionsResponse = await t.request({
        url: `${userVariables.admin_submissions_page_single.replace('/wp-admin/admin.php?page=bmltwf-submissions', '')}/index.php?rest_route=/bmltwf/v1/submissions&first=0&last=0`,
        method: 'GET',
        headers: {
            'Cookie': cookieHeader,
            'X-WP-Nonce': nonce
        }
    });
    
    const firstSubmission = submissionsResponse.body.data[0];
    const threadId = firstSubmission.thread_id;
    
    if (!threadId) {
        throw new Error('Thread ID not found in API response after creating correspondence');
    }
    
    // Step 4: User responds to correspondence using the thread_id
    const separator = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    const fullUrl = `${t.ctx.correspondencePageUrl}${separator}thread=${threadId}`;
    await t.navigateTo(fullUrl);
    
    // Wait for correspondence to load and reply section to become visible
    await t.expect(Selector('#bmltwf-correspondence-reply').visible).ok('Reply section should be visible', {timeout: 10000});
    
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userResponse)
        .click(cs.submitButton);
    
    // Step 5: Return to admin page and verify status shows correspondence_received
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Received");
});



test('E2E_Full_Correspondence_Cycle', async t => {
    const adminInitialMessage = 'Admin initial message';
    const userMessage = 'User response to admin';
    const adminResponse = 'Admin response to user';
    const userFollowup = 'User follow-up message';
    let threadId;
    
    // Step 1: Admin initiates correspondence first to create thread_id
    await t.click(cs.firstRow);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminInitialMessage)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Get thread_id from API after correspondence is created
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    const nonce = await Selector('#_wprestnonce').value;
    
    const submissionsResponse = await t.request({
        url: `${userVariables.admin_submissions_page_single.replace('/wp-admin/admin.php?page=bmltwf-submissions', '')}/index.php?rest_route=/bmltwf/v1/submissions&first=0&last=1`,
        method: 'GET',
        headers: {
            'Cookie': cookieHeader,
            'X-WP-Nonce': nonce
        }
    });
    
    const firstSubmission = submissionsResponse.body.data[0];
    const changeId = firstSubmission.change_id;
    threadId = firstSubmission.thread_id;
    
    if (!threadId) {
        throw new Error('Thread ID not found in API response after creating correspondence');
    }
    
    // Step 2: User responds to admin's initial message
    if (!t.ctx.correspondencePageUrl || !changeId) {
        throw new Error(`Missing required values: correspondencePageUrl=${t.ctx.correspondencePageUrl}, changeId=${changeId}`);
    }
    const separator = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator}thread=${threadId}`);
    
    // Wait for correspondence to load and reply section to become visible
    await t.expect(Selector('#bmltwf-correspondence-reply').visible).ok('Reply section should be visible', {timeout: 10000});
    
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userMessage)
        .click(cs.submitButton);
    
    // Step 3: Verify admin sees correspondence_received status
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Received");
    
    // Step 4: Admin responds to user's message
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminResponse)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 5: Verify status changed to correspondence_sent
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Sent");
    
    // Step 6: Verify all messages appear on public page
    if (!t.ctx.correspondencePageUrl || !changeId) {
        throw new Error(`Missing required values: correspondencePageUrl=${t.ctx.correspondencePageUrl}, changeId=${changeId}`);
    }
    const separator2 = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator2}thread=${threadId}`);
    await t
        .expect(Selector('.bmltwf-correspondence-message').withText(adminInitialMessage).exists).ok('Admin initial message should appear on public page')
        .expect(Selector('.bmltwf-correspondence-message').withText(userMessage).exists).ok('User message should appear on public page')
        .expect(Selector('.bmltwf-correspondence-message').withText(adminResponse).exists).ok('Admin response should appear on public page');
    
    // Step 7: User sends follow-up
    // Wait for correspondence to load and reply section to become visible
    await t.expect(Selector('#bmltwf-correspondence-reply').visible).ok('Reply section should be visible', {timeout: 10000});
    
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userFollowup)
        .click(cs.submitButton);
    
    // Step 8: Verify admin sees correspondence_received status again
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Received");
    
    // Step 9: Verify all four messages appear in admin correspondence history
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .expect(cs.correspondenceHistory.textContent).contains(adminInitialMessage)
        .expect(cs.correspondenceHistory.textContent).contains(userMessage)
        .expect(cs.correspondenceHistory.textContent).contains(adminResponse)
        .expect(cs.correspondenceHistory.textContent).contains(userFollowup);
});

test('E2E_Correspondence_Disabled_After_Approval', async t => {
    const adminMessage = 'Admin message before approval';
    
    // Step 1: Admin initiates correspondence on modify meeting submission (row 1)
    const secondRowCell = await get_table_row_col(as.dt_submission, 1, 0);
    await t.click(secondRowCell);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 2: Verify correspondence button is enabled for pending submission
    var g = as.dt_submission_wrapper.find("button").nth(2); // correspondence button
    await t.expect(g.hasAttribute("disabled")).notOk('Correspondence button should be enabled for pending submission');

    // Step 3: Approve the submission (row should still be selected)
    await click_dt_button_by_index(as.dt_submission_wrapper, 0); // Approve button
    await t.expect(as.approve_dialog_parent.visible).eql(true);
    await t.typeText(as.approve_dialog_textarea, "I approve this request");
    await click_dialog_button_by_index(as.approve_dialog_parent, 1);
    await t.expect(as.approve_dialog_parent.visible).eql(false);
    
    // Step 4: Re-select row and verify correspondence button is disabled after approval
    await t.click(secondRowCell);
    g = as.dt_submission_wrapper.find("button").nth(2); // correspondence button
    await t.expect(g.hasAttribute("disabled")).ok('Correspondence button should be disabled after approval');
});

test('E2E_Correspondence_Disabled_After_Rejection', async t => {
    const adminMessage = 'Admin message before rejection';
    
    // Step 1: Admin initiates correspondence on new meeting submission (row 2)
    const thirdRowCell = await get_table_row_col(as.dt_submission, 2, 0);
    await t.click(thirdRowCell);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 2: Re-select row and verify correspondence button is enabled for pending submission
    var g = as.dt_submission_wrapper.find("button").nth(2); // correspondence button
    await t.expect(g.hasAttribute("disabled")).notOk('Correspondence button should be enabled for pending submission');
    
    // Step 3: Reject the submission (row should still be selected)
    await click_dt_button_by_index(as.dt_submission_wrapper, 1); // Reject button
    await t.expect(as.reject_dialog_parent.visible).eql(true);
    await t.typeText(as.reject_dialog_textarea, "I reject this request");
    await click_dialog_button_by_index(as.reject_dialog_parent, 1);
    await t.expect(as.reject_dialog_parent.visible).eql(false);
    
    // Step 4: Re-select row and verify correspondence button is disabled after rejection
    await t.click(thirdRowCell);
    g = as.dt_submission_wrapper.find("button").nth(2); // correspondence button
    await t.expect(g.hasAttribute("disabled")).ok('Correspondence button should be disabled after rejection');
});

test('E2E_Multiple_Submissions_Independent_Correspondence', async t => {
    const adminMessage1 = 'Admin message for first submission';
    const adminMessage2 = 'Admin message for second submission';
    const userResponse1 = 'User response to first submission';
    const userResponse2 = 'User response to second submission';
    
    // Step 1: Admin initiates correspondence for first submission
    await t.click(cs.firstRow);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage1)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 2: Admin initiates correspondence for second submission
    const secondRowCell = await get_table_row_col(Selector('table#dt-submission'), 1, 0);
    await t.click(secondRowCell);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage2)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 3: Get thread_ids from API after correspondence is created
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    const nonce = await Selector('#_wprestnonce').value;
    
    const submissionsResponse = await t.request({
        url: `${userVariables.admin_submissions_page_single.replace('/wp-admin/admin.php?page=bmltwf-submissions', '')}/index.php?rest_route=/bmltwf/v1/submissions&first=0&last=1`,
        method: 'GET',
        headers: {
            'Cookie': cookieHeader,
            'X-WP-Nonce': nonce
        }
    });
    
    const firstSubmission = submissionsResponse.body.data[0];
    const secondSubmission = submissionsResponse.body.data[1];
    const threadId1 = firstSubmission.thread_id;
    const threadId2 = secondSubmission.thread_id;
    
    if (!threadId1 || !threadId2) {
        throw new Error('Thread IDs not found in API response after creating correspondence');
    }
    
    // Step 4: User responds to first submission
    const separator1 = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator1}thread=${threadId1}`);
    
    // Wait for correspondence to load and reply section to become visible
    await t.expect(Selector('#bmltwf-correspondence-reply').visible).ok('Reply section should be visible', {timeout: 10000});
    
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userResponse1)
        .click(cs.submitButton);
    
    // Step 5: User responds to second submission
    const separator2 = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator2}thread=${threadId2}`);
    
    // Wait for correspondence to load and reply section to become visible
    await t.expect(Selector('#bmltwf-correspondence-reply').visible).ok('Reply section should be visible', {timeout: 10000});
    
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userResponse2)
        .click(cs.submitButton);
    
    // Step 6: Verify messages appear only on their respective pages
    const separator3 = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator3}thread=${threadId1}`);
    await t
        .expect(Selector('.bmltwf-correspondence-message').withText(userResponse1).exists).ok('User response 1 should appear on thread 1')
        .expect(Selector('.bmltwf-correspondence-message').withText(userResponse2).exists).notOk('User response 2 should not appear on thread 1');
    
    const separator4 = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator4}thread=${threadId2}`);
    await t
        .expect(Selector('.bmltwf-correspondence-message').withText(userResponse2).exists).ok('User response 2 should appear on thread 2')
        .expect(Selector('.bmltwf-correspondence-message').withText(userResponse1).exists).notOk('User response 1 should not appear on thread 2');
});

test('E2E_Correspondence_Filter_Validation', async t => {
    const adminMessage = 'Admin message for filter test';
    
    // Step 1: Admin initiates correspondence on first submission
    await t.click(cs.firstRow);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 2: Verify submission appears in "Pending" filter
    await t
        .click(Selector('#dt-submission-filters'))
        .click(Selector('#dt-submission-filters option').withText('Pending'));
    await t.expect(Selector('table#dt-submission tbody tr').count).gte(1, 'At least one submission should appear in Pending filter');
    await t.expect(Selector('table#dt-submission tbody tr').nth(0).child(8).innerText).eql('Correspondence Sent');
    
    // Step 3: Verify submission appears in "Correspondence" filter
    await t
        .click(Selector('#dt-submission-filters'))
        .click(Selector('#dt-submission-filters option').withText('Correspondence'));
    await t.expect(Selector('table#dt-submission tbody tr').count).gte(1, 'At least one submission should appear in Correspondence filter');
    await t.expect(Selector('table#dt-submission tbody tr').nth(0).child(8).innerText).eql('Correspondence Sent');
    
    // Step 4: Verify submission does NOT appear in "Approved" filter
    await t
        .click(Selector('#dt-submission-filters'))
        .click(Selector('#dt-submission-filters option').withText('Approved'));
    await t.expect(Selector('table#dt-submission tbody tr td').withText('Correspondence Sent').exists).notOk('Correspondence Sent should not appear in Approved filter');
    
    // Step 5: Verify submission does NOT appear in "Rejected" filter
    await t
        .click(Selector('#dt-submission-filters'))
        .click(Selector('#dt-submission-filters option').withText('Rejected'));
    await t.expect(Selector('table#dt-submission tbody tr td').withText('Correspondence Sent').exists).notOk('Correspondence Sent should not appear in Rejected filter');
});


test('E2E_Correspondence_ReadOnly_After_Approval', async t => {
    const adminMessage = 'Admin message before approval';
    
    // Step 1: Admin initiates correspondence
    const secondRowCell = await get_table_row_col(as.dt_submission, 1, 0);
    await t.click(secondRowCell);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'));
    
    // Step 2: Get thread_id
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    const nonce = await Selector('#_wprestnonce').value;
    
    const submissionsResponse = await t.request({
        url: `${userVariables.admin_submissions_page_single.replace('/wp-admin/admin.php?page=bmltwf-submissions', '')}/index.php?rest_route=/bmltwf/v1/submissions&first=0&last=1`,
        method: 'GET',
        headers: {
            'Cookie': cookieHeader,
            'X-WP-Nonce': nonce
        }
    });
    
    const submission = submissionsResponse.body.data.find(s => s.thread_id);
    const threadId = submission.thread_id;
    
    // Step 3: Approve the submission
    await click_dt_button_by_index(as.dt_submission_wrapper, 0);
    await t.typeText(as.approve_dialog_textarea, "Approved");
    await click_dialog_button_by_index(as.approve_dialog_parent, 1);
    
    // Step 4: Verify user can still view correspondence (read-only)
    const separator = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator}thread=${threadId}`);
    await t.expect(Selector('.bmltwf-correspondence-message').withText(adminMessage).exists).ok('Should be able to view correspondence after approval');
    await t.expect(Selector('.bmltwf-closed-notice').exists).ok('Should show closed notice');
    await t.expect(Selector('#bmltwf-correspondence-reply').visible).notOk('Reply section should be hidden after approval');
});



test('E2E_Correspondence_Grace_Period_Expired', async t => {
    // Use expired data as object (approved >60 days ago)
    const expiredData = {
        options: {
            bmltwf_email_from_address: "example@example.com",
            bmltwf_delete_closed_meetings: "unpublish",
            bmltwf_optional_location_nation: "hidden",
            bmltwf_optional_location_nation_displayname: "Nation",
            bmltwf_optional_location_sub_province: "hidden",
            bmltwf_optional_location_sub_province_displayname: "Sub Province",
            bmltwf_optional_location_province: "display",
            bmltwf_optional_location_province_displayname: "Province",
            bmltwf_optional_postcode: "display",
            bmltwf_optional_postcode_displayname: "Postcode",
            bmltwf_required_meeting_formats: "true",
            bmltwf_db_version: "1.1.18"
        },
        submissions: [{
            change_id: 100,
            submission_time: "2022-01-01 12:00:00",
            change_time: "2022-01-02 12:00:00",
            changed_by: "admin",
            change_made: "approved",
            submitter_name: "Test User",
            submission_type: "reason_new",
            submitter_email: "test@test.com",
            id: "0",
            serviceBodyId: "2",
            changes_requested: JSON.stringify({name: "Expired Meeting", startTime: "10:00", duration: "01:00", location_text: "Test Location", location_street: "123 Test St", location_municipality: "Test City", location_province: "NSW", location_postal_code_1: 2000, day: "1", serviceBodyId: 2, formatIds: ["1", "2"]}),
            action_message: "Approved"
        }],
        correspondence: [{
            change_id: 100,
            thread_id: "expired-thread-id-12345",
            message: "This is an expired correspondence message",
            from_submitter: 0,
            created_at: "2022-01-01 13:00:00",
            created_by: "Admin"
        }],
        service_bodies: [{
            serviceBodyId: "2",
            service_body_name: "a-level1",
            service_body_description: "",
            show_on_form: "1"
        }],
        service_bodies_access: [{
            serviceBodyId: "2",
            wp_uid: "1"
        }]
    };
    
    await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single, userVariables.admin_restore_json, myip(), "3001", "hidden", expiredData);
    
    // Verify correspondence is no longer accessible after 60-day grace period
    const separator = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator}thread=expired-thread-id-12345`);
    await t.expect(Selector('#bmltwf-correspondence-error').visible).ok('Should show error for expired correspondence');
    await t.expect(Selector('#bmltwf-correspondence-messages').visible).notOk('Should not show correspondence messages after grace period expires');
    await t.expect(Selector('#bmltwf-correspondence-error').innerText).contains('no longer available', 'Error should indicate correspondence is no longer available');
});
