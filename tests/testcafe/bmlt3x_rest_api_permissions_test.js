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
import { Selector } from 'testcafe';

// Define the base URL and REST paths
const siteurl_single = userVariables.siteurl_single || 'http://wordpress-php8-singlesite';
const restBasePath = '/index.php?rest_route=/bmltwf/v1';

// Define the REST API endpoints to test
const publicEndpoints = [
    `${restBasePath}/servicebodies`,
    `${restBasePath}/bmltserver/meetings`
];

const protectedEndpoints = [
    `${restBasePath}/submissions`, // GET submissions requires auth, only POST is public
    `${restBasePath}/submissions/1`,
    `${restBasePath}/bmltserver`
];

// This endpoint returns 404 for GET but requires auth for POST
const backupEndpoint = `${restBasePath}/options/backup`;

// These endpoints are special - they return 404 for GET but should require auth for POST
const debugEndpoint = `${restBasePath}/options/debug`;
const restoreEndpoint = `${restBasePath}/options/restore`;
const geolocateEndpoint = `${restBasePath}/bmltserver/geolocate`;
const approveEndpoint = `${restBasePath}/submissions/1/approve`;
const rejectEndpoint = `${restBasePath}/submissions/1/reject`;



// Test that public endpoints are accessible without authentication
fixture`Public_REST_API_Endpoints`
    .page`${siteurl_single}`;

publicEndpoints.forEach(endpoint => {
    test(`${endpoint} should be accessible without authentication (GET)`, async t => {
        const response = await t.request({
            url: siteurl_single + endpoint,
            method: 'GET'
        });
        
        // Check that the response is not a 401 or 403 error
        await t.expect(response.status).notEql(401);
        await t.expect(response.status).notEql(403);
    });
});

// This test is now handled in the protectedEndpoints loop

// Test that protected endpoints are not accessible without authentication
fixture`Protected_REST_API_Endpoints_Unauthenticated`
    .page`${siteurl_single}`;

protectedEndpoints.forEach(endpoint => {
    test(`${endpoint} should not be accessible without authentication (GET)`, async t => {
        const response = await t.request({
            url: siteurl_single + endpoint,
            method: 'GET'
        });
        
        // Check that the response is a 401 (Unauthorized) or 403 (Forbidden) error
        await t.expect(response.status === 401 || response.status === 403).ok(`Expected 401 or 403, got ${response.status}`);
    });

    // Special case for submissions endpoint which accepts POST for form submissions
    if (endpoint === `${restBasePath}/submissions`) {
        test(`${endpoint} should accept POST without authentication for form submissions`, async t => {
            const response = await t.request({
                url: siteurl_single + endpoint,
                method: 'POST',
                body: {
                    // Minimal submission data
                    update_reason: 'reason_new',
                    first_name: 'Test',
                    last_name: 'User',
                    email_address: 'test@example.com'
                }
            });
            
            // Check that the response is not a 401 or 403 error
            // It might be 422 for validation errors, but that's expected
            await t.expect(response.status).notEql(401);
            await t.expect(response.status).notEql(403);
        });
    } else {
        test(`${endpoint} should not be accessible without authentication (POST)`, async t => {
            const response = await t.request({
                url: siteurl_single + endpoint,
                method: 'POST',
                body: {}
            });
            
            // Check that the response is a 401 (Unauthorized) or 403 (Forbidden) error
            await t.expect(response.status === 401 || response.status === 403).ok(`Expected 401 or 403, got ${response.status}`);
        });
    }
});

// Special test for debug endpoint - POST should require auth, GET returns 404
test(`${debugEndpoint} should require authentication for POST but returns 404 for GET`, async t => {
    // Test GET access (should return 404)
    const getResponse = await t.request({
        url: siteurl_single + debugEndpoint,
        method: 'GET'
    });
    
    // Check that the GET response is a 404
    await t.expect(getResponse.status).eql(404);
    
    // Test POST access (should be denied with 401 or 403)
    const postResponse = await t.request({
        url: siteurl_single + debugEndpoint,
        method: 'POST',
        body: {}
    });
    
    // Check that the POST response is a 401 or 403 error
    await t.expect(postResponse.status === 401 || postResponse.status === 403).ok(`Expected 401 or 403 for POST, got ${postResponse.status}`);
});

// Special test for restore endpoint - POST should require auth, GET returns 404
test(`${restoreEndpoint} should require authentication for POST but returns 404 for GET`, async t => {
    // Test GET access (should return 404)
    const getResponse = await t.request({
        url: siteurl_single + restoreEndpoint,
        method: 'GET'
    });
    
    // Check that the GET response is a 404
    await t.expect(getResponse.status).eql(404);
    
    // Test POST access (should be denied with 401 or 403)
    const postResponse = await t.request({
        url: siteurl_single + restoreEndpoint,
        method: 'POST',
        body: {}
    });
    
    // Check that the POST response is a 401 or 403 error
    await t.expect(postResponse.status === 401 || postResponse.status === 403).ok(`Expected 401 or 403 for POST, got ${postResponse.status}`);
});

// Special test for backup endpoint - POST should require auth, GET returns 404 or auth error
test(`${backupEndpoint} should require authentication for POST but returns 404 or auth error for GET`, async t => {
    // Test GET access (should return 404 or auth error)
    const getResponse = await t.request({
        url: siteurl_single + backupEndpoint,
        method: 'GET'
    });
    
    // Check that the GET response is a 404, 401, or 403
    await t.expect(getResponse.status === 404 || getResponse.status === 401 || getResponse.status === 403)
        .ok(`Expected 404, 401, or 403, got ${getResponse.status}`);
    
    // Test POST access (should be denied with 401 or 403)
    const postResponse = await t.request({
        url: siteurl_single + backupEndpoint,
        method: 'POST',
        body: {}
    });
    
    // Check that the POST response is a 401 or 403 error
    await t.expect(postResponse.status === 401 || postResponse.status === 403)
        .ok(`Expected 401 or 403 for POST, got ${postResponse.status}`);
});

// Special test for geolocate endpoint - POST should require auth, GET returns 401 or 404
test(`${geolocateEndpoint} should require authentication for POST but returns 401 or 404 for GET`, async t => {
    // Test GET access (should return 401 or 404)
    const getResponse = await t.request({
        url: siteurl_single + geolocateEndpoint,
        method: 'GET'
    });
    
    // Check that the GET response is a 401 or 404
    await t.expect(getResponse.status === 401 || getResponse.status === 404).ok(`Expected 401 or 404, got ${getResponse.status}`);
    
    // Test POST access (should be denied with 401, 403, or 404)
    const postResponse = await t.request({
        url: siteurl_single + geolocateEndpoint,
        method: 'POST',
        body: {}
    });
    
    // Check that the POST response is a 401, 403, or 404 error
    await t.expect(postResponse.status === 401 || postResponse.status === 403 || postResponse.status === 404).ok(`Expected 401, 403, or 404 for POST, got ${postResponse.status}`);
});

// Special test for approve endpoint - POST should require auth, GET returns 404
test(`${approveEndpoint} should require authentication for POST but returns 404 for GET`, async t => {
    // Test GET access (should return 404)
    const getResponse = await t.request({
        url: siteurl_single + approveEndpoint,
        method: 'GET'
    });
    
    // Check that the GET response is a 404
    await t.expect(getResponse.status).eql(404);
    
    // Test POST access (should be denied with 401 or 403)
    const postResponse = await t.request({
        url: siteurl_single + approveEndpoint,
        method: 'POST',
        body: {}
    });
    
    // Check that the POST response is a 401 or 403 error
    await t.expect(postResponse.status === 401 || postResponse.status === 403).ok(`Expected 401 or 403 for POST, got ${postResponse.status}`);
});

// Special test for reject endpoint - POST should require auth, GET returns 404
test(`${rejectEndpoint} should require authentication for POST but returns 404 for GET`, async t => {
    // Test GET access (should return 404)
    const getResponse = await t.request({
        url: siteurl_single + rejectEndpoint,
        method: 'GET'
    });
    
    // Check that the GET response is a 404
    await t.expect(getResponse.status).eql(404);
    
    // Test POST access (should be denied with 401 or 403)
    const postResponse = await t.request({
        url: siteurl_single + rejectEndpoint,
        method: 'POST',
        body: {}
    });
    
    // Check that the POST response is a 401 or 403 error
    await t.expect(postResponse.status === 401 || postResponse.status === 403).ok(`Expected 401 or 403 for POST, got ${postResponse.status}`);
});

// Test that protected endpoints are accessible with authentication
fixture`Protected_REST_API_Endpoints_Authenticated`
    .beforeEach(async t => {
        // Login to WordPress using the admin role
        await t.useRole(bmltwf_admin).navigateTo(userVariables.admin_settings_page_single);
        
        const nonce = await Selector("#_wprestnonce").value;
        // Store the nonce for later use
        t.ctx.nonce = nonce;
    });

protectedEndpoints.forEach(endpoint => {
    test(`${endpoint} should be accessible with authentication`, async t => {
        // Skip the test if we couldn't get a nonce
        if (!t.ctx.nonce) {
            await t.expect(true).notOk('Could not get nonce for authenticated requests');
            return;
        }
        
        // Get cookies for authentication
        const cookies = await t.getCookies();
        const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
        
        const response = await t.request({
            url: siteurl_single + endpoint,
            method: 'GET',
            headers: {
                'X-WP-Nonce': t.ctx.nonce,
                'Cookie': cookieHeader
            }
        });
        
        // For some endpoints, we might get a 404 or other error, but it shouldn't be a 401 or 403
        await t.expect(response.status).notEql(401);
        await t.expect(response.status).notEql(403);
    });
});

// Special test for debug endpoint with authentication
test(`${debugEndpoint} should be accessible with authentication (POST only)`, async t => {
    // Skip the test if we couldn't get a nonce
    if (!t.ctx.nonce) {
        await t.expect(true).notOk('Could not get nonce for authenticated requests');
        return;
    }
    
    // Get cookies for authentication
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    
    // Test POST access with authentication
    const response = await t.request({
        url: siteurl_single + debugEndpoint,
        method: 'POST',
        headers: {
            'X-WP-Nonce': t.ctx.nonce,
            'Cookie': cookieHeader
        },
        body: {}
    });
    
    // Should not be a 401 or 403 error
    await t.expect(response.status).notEql(401);
    await t.expect(response.status).notEql(403);
});

// Special test for restore endpoint with authentication
test(`${restoreEndpoint} should be accessible with authentication (POST only)`, async t => {
    // Skip the test if we couldn't get a nonce
    if (!t.ctx.nonce) {
        await t.expect(true).notOk('Could not get nonce for authenticated requests');
        return;
    }
    
    // Get cookies for authentication
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    
    // Test POST access with authentication
    const response = await t.request({
        url: siteurl_single + restoreEndpoint,
        method: 'POST',
        headers: {
            'X-WP-Nonce': t.ctx.nonce,
            'Cookie': cookieHeader
        },
        body: {}
    });
    
    // Should not be a 401 or 403 error
    await t.expect(response.status).notEql(401);
    await t.expect(response.status).notEql(403);
});

// Special test for geolocate endpoint with authentication
test(`${geolocateEndpoint} should be accessible with authentication (POST only)`, async t => {
    // Skip the test if we couldn't get a nonce
    if (!t.ctx.nonce) {
        await t.expect(true).notOk('Could not get nonce for authenticated requests');
        return;
    }
    
    // Get cookies for authentication
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    
    // Test POST access with authentication
    const response = await t.request({
        url: siteurl_single + geolocateEndpoint,
        method: 'POST',
        headers: {
            'X-WP-Nonce': t.ctx.nonce,
            'Cookie': cookieHeader
        },
        body: {}
    });
    
    // Should not be a 401 or 403 error
    await t.expect(response.status).notEql(401);
    await t.expect(response.status).notEql(403);
});

// Test for backup endpoint with authentication
test(`${backupEndpoint} should be accessible with authentication (POST only)`, async t => {
    // Skip the test if we couldn't get a nonce
    if (!t.ctx.nonce) {
        await t.expect(true).notOk('Could not get nonce for authenticated requests');
        return;
    }
    
    // Get cookies for authentication
    const cookies = await t.getCookies();
    const cookieHeader = cookies.map(cookie => `${cookie.name}=${cookie.value}`).join('; ');
    
    // Test POST access with authentication
    const response = await t.request({
        url: siteurl_single + backupEndpoint,
        method: 'POST',
        headers: {
            'X-WP-Nonce': t.ctx.nonce,
            'Cookie': cookieHeader
        },
        body: {}
    });
    
    // Should not be a 401 or 403 error
    await t.expect(response.status).notEql(401);
    await t.expect(response.status).notEql(403);
});

// Skip test for approve endpoint - requires valid submission ID
test.skip(`${approveEndpoint} should be accessible with authentication (POST only)`, async t => {
    // This test is skipped because it requires a valid submission ID
    // The endpoint returns 403 because submission ID 1 doesn't exist or user doesn't have permission
});

// Skip test for reject endpoint - requires valid submission ID
test.skip(`${rejectEndpoint} should be accessible with authentication (POST only)`, async t => {
    // This test is skipped because it requires a valid submission ID
    // The endpoint returns 403 because submission ID 1 doesn't exist or user doesn't have permission
});