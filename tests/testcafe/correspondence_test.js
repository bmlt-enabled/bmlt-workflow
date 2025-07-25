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
import { bmltwf_admin } from './helpers/helper';
import { cs } from './models/correspondence';

fixture`Correspondence_Feature`
    .page`${userVariables.admin_submissions_page_single}`
    .beforeEach(async t => {
        await bmltwf_admin(t);
    });

test('Correspondence_Button_Visibility', async t => {
    await t
        .click(cs.firstRow)
        .expect(cs.correspondenceButton.exists).ok('Correspondence button should exist');
});

test('Correspondence_Modal_Opens', async t => {
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .expect(cs.correspondenceModal.visible).ok('Correspondence modal should open');
});

test('Send_Correspondence_Message', async t => {
    const testMessage = 'Test correspondence message';
    
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, testMessage)
        .click(cs.sendButton)
        .expect(cs.correspondenceHistory.textContent).contains(testMessage);
});

test('Admin_Send_Correspondence_Updates_Status', async t => {
    const testMessage = 'Admin correspondence test';
    
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, testMessage)
        .click(cs.sendButton)
        .wait(2000)
        .expect(Selector('td').withText('correspondence_sent').exists).ok('Status should show correspondence_sent');
});

test('Admin_Message_Appears_On_Public_Page', async t => {
    const testMessage = 'Admin to public message';
    let threadId;
    
    await t
        .click(cs.firstRow)
        .click(cs.correspondenceButton)
        .typeText(cs.correspondenceTextarea, testMessage)
        .click(cs.sendButton);
    
    threadId = await cs.correspondenceModal.getAttribute('data-thread-id');
    
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId}`);
    await t.expect(Selector('.correspondence-message').withText(testMessage).exists).ok('Admin message should appear on public page');
});