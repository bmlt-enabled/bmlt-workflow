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
import { cs } from './models/correspondence';
import { Selector } from 'testcafe';

fixture`Correspondence_Public_Access`
    .page`${userVariables.siteurl_single}`;

test('Public_Correspondence_Page_Loads', async t => {
    // Navigate to correspondence page with thread parameter
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=test-thread-id`);
    
    await t
        .expect(cs.correspondenceForm.exists).ok('Correspondence form should exist')
        .expect(cs.correspondenceTextarea.exists).ok('Message textarea should exist')
        .expect(cs.submitButton.exists).ok('Submit button should exist');
});

test('Submit_Public_Correspondence', async t => {
    const testMessage = 'Public correspondence test message';
    
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=test-thread-id`);
    
    await t
        .typeText(cs.correspondenceTextarea, testMessage)
        .click(cs.submitButton)
        .expect(cs.successMessage.exists).ok('Success message should appear');
});

test('Public_Correspondence_Updates_Admin_Status', async t => {
    const testMessage = 'Public to admin test message';
    const threadId = 'test-thread-id';
    
    await t.navigateTo(`${userVariables.siteurl_single}/correspondence/?thread=${threadId}`);
    await t
        .typeText(cs.correspondenceTextarea, testMessage)
        .click(cs.submitButton)
        .wait(2000);
    
    await t.navigateTo(userVariables.admin_submissions_page_single);
    await t.expect(Selector('td').withText('correspondence_received').exists).ok('Status should show correspondence_received');
});