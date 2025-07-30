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
import { bmltwf_admin, restore_from_backup, myip, set_language_single, setupCorrespondenceFeature, get_table_row_col } from './helpers/helper';
import { cs } from './models/correspondence';
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
        .click(Selector('.ui-dialog-buttonset button').withText('Close'))
        .wait(2000);
    
    // Step 2: Verify status changed to correspondence_sent
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Sent", {timeout: 10000});
    
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
        .click(cs.submitButton)
        .wait(2000);
    
    // Step 5: Return to admin page and verify status shows correspondence_received
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Received", {timeout: 10000});
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
        .click(Selector('.ui-dialog-buttonset button').withText('Close'))
        .wait(2000);
    
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
        .click(cs.submitButton)
        .wait(2000);
    
    // Step 3: Verify admin sees correspondence_received status
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Received", {timeout: 10000});
    
    // Step 4: Admin responds to user's message
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminResponse)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'))
        .wait(2000);
    
    // Step 5: Verify status changed to correspondence_sent
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Sent", {timeout: 10000});
    
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
        .click(cs.submitButton)
        .wait(2000);
    
    // Step 8: Verify admin sees correspondence_received status again
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('table#dt-submission').child("tbody").child(0).child(8).innerText).eql("Correspondence Received", {timeout: 10000});
    
    // Step 9: Verify all four messages appear in admin correspondence history
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .expect(cs.correspondenceHistory.textContent).contains(adminInitialMessage)
        .expect(cs.correspondenceHistory.textContent).contains(userMessage)
        .expect(cs.correspondenceHistory.textContent).contains(adminResponse)
        .expect(cs.correspondenceHistory.textContent).contains(userFollowup);
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
        .click(Selector('.ui-dialog-buttonset button').withText('Close'))
        .wait(2000);
    
    // Step 2: Admin initiates correspondence for second submission
    const secondRowCell = await get_table_row_col(Selector('table#dt-submission'), 1, 0);
    await t.click(secondRowCell);
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage2)
        .click(cs.sendButton)
        .click(Selector('.ui-dialog-buttonset button').withText('Close'))
        .wait(2000);
    
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
        .click(cs.submitButton)
        .wait(2000);
    
    // Step 5: User responds to second submission
    const separator2 = t.ctx.correspondencePageUrl.includes('?') ? '&' : '?';
    await t.navigateTo(`${t.ctx.correspondencePageUrl}${separator2}thread=${threadId2}`);
    
    // Wait for correspondence to load and reply section to become visible
    await t.expect(Selector('#bmltwf-correspondence-reply').visible).ok('Reply section should be visible', {timeout: 10000});
    
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userResponse2)
        .click(cs.submitButton)
        .wait(2000);
    
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