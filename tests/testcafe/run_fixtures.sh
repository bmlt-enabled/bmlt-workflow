#!/bin/sh
testcafe --fixture bmlt2x_e2e_test_fixture
testcafe --fixture bmlt2x_geocoding_tests_fixture
testcafe --fixture bmlt3x_admin_options_fixture
testcafe --fixture bmlt3x_admin_submissions_permissions_fixture
testcafe --fixture bmlt3x_admin_submissions_fixture
testcafe --fixture bmlt3x_e2e_test_fixture
testcafe --fixture bmlt3x_geocoding_tests_fixture
testcafe --fixture bmlt3x_meeting_update_form_fixture
testcafe --fixture bmlt3x_multisite_single_e2e_test_fixture
testcafe --fixture bmlt3x_multisite_tests_fixture