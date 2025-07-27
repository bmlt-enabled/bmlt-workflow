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
import { bmltwf_admin, restore_from_backup, myip, set_language_single, setupCorrespondenceFeature } from './helpers/helper';
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
    
    // Get change ID from first submission and generate thread ID
    await t.click(cs.firstRow);
    const changeId = await Selector('table#dt-submission tbody tr').nth(0).find('td').nth(0).textContent;
    const threadId = `submission-${changeId}`;
    
    // Step 1: Admin initiates correspondence
    await t
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, adminMessage)
        .click(cs.sendButton)
        .wait(2000);
    
    // Verify status changed to correspondence_sent
    await t.expect(Selector('td').withText('correspondence_sent').exists).ok('Status should show correspondence_sent after admin sends message');
    
    // Step 2: User responds to correspondence
    await t.navigateTo(`${t.ctx.correspondencePageUrl}?thread=${threadId}`);
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userResponse)
        .click(cs.submitButton)
        .wait(2000);
    
    // Step 3: Return to admin page and verify status shows correspondence_received
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('td').withText('correspondence_received').exists).ok('Status should show correspondence_received after user responds');
});



test('E2E_Full_Correspondence_Cycle', async t => {
    const userMessage = 'Initial user inquiry';
    const adminResponse = 'Admin response to inquiry';
    const userFollowup = 'User follow-up message';
    let threadId;
    
    // Get change ID from first submission and generate thread ID
    await t.click(cs.firstRow);
    const changeId = await Selector('table#dt-submission tbody tr').nth(0).find('td').nth(0).textContent;
    threadId = `submission-${changeId}`;
    
    // Step 1: User submits initial correspondence
    await t.navigateTo(`${t.ctx.correspondencePageUrl}?thread=${threadId}`);
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userMessage)
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
    await t.navigateTo(`${t.ctx.correspondencePageUrl}?thread=${threadId}`);
    await t
        .expect(Selector('.bmltwf-correspondence-message').withText(userMessage).exists).ok('User message should appear on public page')
        .expect(Selector('.bmltwf-correspondence-message').withText(adminResponse).exists).ok('Admin response should appear on public page');
    
    // Step 6: User sends follow-up
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, userFollowup)
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
    
    // Get change IDs from first two submissions and generate thread IDs
    await t.click(Selector('table#dt-submission tbody tr').nth(0));
    const changeId1 = await Selector('table#dt-submission tbody tr').nth(0).find('td').nth(0).textContent;
    threadId1 = `submission-${changeId1}`;
    
    await t.click(Selector('table#dt-submission tbody tr').nth(1));
    const changeId2 = await Selector('table#dt-submission tbody tr').nth(1).find('td').nth(0).textContent;
    threadId2 = `submission-${changeId2}`;
    
    // Send correspondence to first submission
    await t.navigateTo(`${t.ctx.correspondencePageUrl}?thread=${threadId1}`);
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, message1)
        .click(cs.submitButton)
        .wait(2000);
    
    // Send correspondence to second submission
    await t.navigateTo(`${t.ctx.correspondencePageUrl}?thread=${threadId2}`);
    await t
        .click(cs.replyButton)
        .typeText(cs.replyTextarea, message2)
        .click(cs.submitButton)
        .wait(2000);
    
    // Verify messages appear only on their respective pages
    await t.navigateTo(`${t.ctx.correspondencePageUrl}?thread=${threadId1}`);
    await t
        .expect(Selector('.bmltwf-correspondence-message').withText(message1).exists).ok('Message 1 should appear on thread 1')
        .expect(Selector('.bmltwf-correspondence-message').withText(message2).exists).notOk('Message 2 should not appear on thread 1');
    
    await t.navigateTo(`${t.ctx.correspondencePageUrl}?thread=${threadId2}`);
    await t
        .expect(Selector('.bmltwf-correspondence-message').withText(message2).exists).ok('Message 2 should appear on thread 2')
        .expect(Selector('.bmltwf-correspondence-message').withText(message1).exists).notOk('Message 1 should not appear on thread 2');
});