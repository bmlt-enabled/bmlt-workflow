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
import { bmltwf_admin, restore_from_backup, myip, set_language_single } from './helpers/helper';
import { cs } from './models/correspondence_selectors';
import { Selector } from 'testcafe';

fixture`Correspondence_E2E_Workflow`
    .beforeEach(async t => {
        await restore_from_backup(bmltwf_admin, userVariables.admin_settings_page_single,userVariables.admin_restore_json,myip(),"3001","hidden");
        await set_language_single(t, "en_EN");

        await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_submissions_page_single);

    });

test('E2E_User_Submits_Correspondence_Admin_Sees_Status_Change', async t => {
    const testMessage = 'User correspondence message for E2E test';
    let threadId;
    
    // Get thread ID from first submission
    await t.click(cs.firstRow);
    threadId = await cs.correspondenceModal.getAttribute('data-thread-id');
    
    // Navigate to public correspondence page and submit message
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId}`);
    await t
        .typeText(cs.correspondenceTextarea, testMessage)
        .click(cs.submitButton)
        .wait(2000);
    
    // Return to admin page and verify status shows correspondence_received
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('td').withText('correspondence_received').exists).ok('Status should show correspondence_received after user submission');
});

test('E2E_Admin_Sends_Correspondence_Public_Page_Shows_Message', async t => {
    const adminMessage = 'Admin response for E2E test';
    let threadId;
    
    // Admin sends correspondence
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage)
        .click(cs.sendButton)
        .wait(2000);
    
    // Get thread ID
    threadId = await cs.correspondenceModal.getAttribute('data-thread-id');
    
    // Verify status changed to correspondence_sent
    await t.expect(Selector('td').withText('correspondence_sent').exists).ok('Status should show correspondence_sent after admin sends message');
    
    // Navigate to public page and verify message appears
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId}`);
    await t.expect(Selector('.correspondence-message').withText(adminMessage).exists).ok('Admin message should appear on public correspondence page');
});

test('E2E_Full_Correspondence_Cycle', async t => {
    const userMessage = 'Initial user inquiry';
    const adminResponse = 'Admin response to inquiry';
    const userFollowup = 'User follow-up message';
    let threadId;
    
    // Get thread ID from first submission
    await t.click(cs.firstRow);
    threadId = await cs.correspondenceModal.getAttribute('data-thread-id');
    
    // Step 1: User submits initial correspondence
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId}`);
    await t
        .typeText(cs.correspondenceTextarea, userMessage)
        .click(cs.submitButton)
        .wait(2000);
    
    // Step 2: Verify admin sees correspondence_received status
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('td').withText('correspondence_received').exists).ok('Status should show correspondence_received');
    
    // Step 3: Admin responds to correspondence
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminResponse)
        .click(cs.sendButton)
        .wait(2000);
    
    // Step 4: Verify status changed to correspondence_sent
    await t.expect(Selector('td').withText('correspondence_sent').exists).ok('Status should show correspondence_sent');
    
    // Step 5: Verify both messages appear on public page
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId}`);
    await t
        .expect(Selector('.correspondence-message').withText(userMessage).exists).ok('User message should appear on public page')
        .expect(Selector('.correspondence-message').withText(adminResponse).exists).ok('Admin response should appear on public page');
    
    // Step 6: User sends follow-up
    await t
        .typeText(cs.correspondenceTextarea, userFollowup)
        .click(cs.submitButton)
        .wait(2000);
    
    // Step 7: Verify admin sees correspondence_received status again
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('td').withText('correspondence_received').exists).ok('Status should show correspondence_received after user follow-up');
    
    // Step 8: Verify all three messages appear in admin correspondence history
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .expect(cs.correspondenceHistory.textContent).contains(userMessage)
        .expect(cs.correspondenceHistory.textContent).contains(adminResponse)
        .expect(cs.correspondenceHistory.textContent).contains(userFollowup);
});

test('E2E_Multiple_Submissions_Independent_Correspondence', async t => {
    const message1 = 'Message for first submission';
    const message2 = 'Message for second submission';
    let threadId1, threadId2;
    
    // Get thread IDs from first two submissions
    await t.click(Selector('table#dt-submission tbody tr').nth(0));
    threadId1 = await cs.correspondenceModal.getAttribute('data-thread-id');
    await t.pressKey('esc');
    
    await t.click(Selector('table#dt-submission tbody tr').nth(1));
    threadId2 = await cs.correspondenceModal.getAttribute('data-thread-id');
    await t.pressKey('esc');
    
    // Send correspondence to first submission
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId1}`);
    await t
        .typeText(cs.correspondenceTextarea, message1)
        .click(cs.submitButton)
        .wait(2000);
    
    // Send correspondence to second submission
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId2}`);
    await t
        .typeText(cs.correspondenceTextarea, message2)
        .click(cs.submitButton)
        .wait(2000);
    
    // Verify messages appear only on their respective pages
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId1}`);
    await t
        .expect(Selector('.correspondence-message').withText(message1).exists).ok('Message 1 should appear on thread 1')
        .expect(Selector('.correspondence-message').withText(message2).exists).notOk('Message 2 should not appear on thread 1');
    
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId2}`);
    await t
        .expect(Selector('.correspondence-message').withText(message2).exists).ok('Message 2 should appear on thread 2')
        .expect(Selector('.correspondence-message').withText(message1).exists).notOk('Message 1 should not appear on thread 2');
});