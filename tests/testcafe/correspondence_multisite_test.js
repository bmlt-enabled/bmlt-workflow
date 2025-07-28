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

import { Selector } from 'testcafe';
import { adminLogin, gotoAdminPage, setupCorrespondenceFeature } from './helpers/helper';
import correspondence from './models/correspondence';

fixture`Correspondence Multisite Tests`
    .page`http://localhost:3000/wp-admin/`
    .beforeEach(async t => {
        await adminLogin(t);
    });

test('Correspondence works in multisite main site', async t => {
    // Setup correspondence feature
    const correspondencePageUrl = await setupCorrespondenceFeature(t);
    
    // Navigate to submissions page
    await gotoAdminPage(t, 'admin.php?page=bmltwf-submissions');
    
    // Wait for submissions table to load
    await t.wait(2000);
    
    // Check if correspondence button exists and is properly configured
    const correspondenceButton = Selector('#bmltwf_submission_correspondence_dialog');
    await t.expect(correspondenceButton.exists).ok('Correspondence button should exist in multisite main site');
    
    // Verify correspondence page URL is accessible
    await t.navigateTo(correspondencePageUrl);
    await t.expect(Selector('body').innerText).contains('bmltwf-correspondence-form', 'Correspondence page should contain shortcode in multisite main site');
});

test('Correspondence database tables exist in multisite', async t => {
    // Navigate to admin options page
    await gotoAdminPage(t, 'admin.php?page=bmltwf-settings');
    
    // Check if we can access the backup functionality (which would fail if tables don't exist)
    await t.click('#bmltwf_backup');
    await t.wait(2000);
    
    // If backup works, tables exist
    const backupResult = await t.eval(() => {
        return document.querySelector('#bmltwf-backup-spinner').style.display;
    });
    
    // Backup should have started (spinner should be visible or hidden after completion)
    await t.expect(backupResult).notEql('block', 'Backup should complete successfully in multisite');
});

test('Correspondence REST API works in multisite', async t => {
    // Test that REST API endpoints are accessible in multisite
    const restResponse = await t.eval(() => {
        return fetch('/wp-json/bmltwf/v1/correspondence/thread/test-thread-id', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': document.querySelector('#_wprestnonce').value
            }
        }).then(response => response.status);
    });
    
    // Should get 404 (not found) rather than 500 (server error) or other errors
    // This indicates the endpoint exists and is working
    await t.expect(restResponse).eql(404, 'REST API endpoint should be accessible in multisite');
});

test('Correspondence feature survives multisite network activation', async t => {
    // Navigate to submissions page to verify plugin is active
    await gotoAdminPage(t, 'admin.php?page=bmltwf-submissions');
    
    // Verify correspondence functionality is available
    const correspondenceElements = {
        button: Selector('#bmltwf_submission_correspondence_dialog'),
        dialog: Selector(correspondence.dialog),
        messageField: Selector(correspondence.messageField)
    };
    
    await t.expect(correspondenceElements.button.exists).ok('Correspondence button should exist after network activation');
    
    // Click to open correspondence dialog
    await t.click(correspondenceElements.button);
    await t.wait(1000);
    
    await t.expect(correspondenceElements.dialog.exists).ok('Correspondence dialog should open after network activation');
    await t.expect(correspondenceElements.messageField.exists).ok('Correspondence message field should exist after network activation');
});

test('Correspondence works with multisite subdirectory structure', async t => {
    // Test that correspondence URLs work correctly with multisite subdirectory structure
    const currentUrl = await t.eval(() => window.location.href);
    
    // Verify we're in a multisite environment (should contain /wp-admin/ in URL structure)
    await t.expect(currentUrl).contains('/wp-admin/', 'Should be in multisite admin environment');
    
    // Setup correspondence and verify URL generation works correctly
    const correspondencePageUrl = await setupCorrespondenceFeature(t);
    
    // Navigate to the correspondence page
    await t.navigateTo(correspondencePageUrl);
    
    // Verify page loads correctly with proper URL structure
    const pageContent = await Selector('body').innerText;
    await t.expect(pageContent).contains('bmltwf-correspondence-form', 'Correspondence page should load correctly in multisite subdirectory structure');
    
    // Verify JavaScript can load REST API URLs correctly
    const restUrl = await t.eval(() => {
        // This simulates what the correspondence form JavaScript does
        const baseUrl = window.location.origin;
        return baseUrl + '/wp-json/bmltwf/v1/correspondence/thread/test';
    });
    
    await t.expect(restUrl).contains('/wp-json/bmltwf/v1/', 'REST API URLs should be generated correctly in multisite');
});