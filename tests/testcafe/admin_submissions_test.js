import { Selector } from 'testcafe';
import { as } from './models/admin_submissions';
import { wordpress_login } from './models/wordpress_login';

import { 
    click_table_row_column,
    select_dropdown_by_id,
    select_dropdown_by_text,
    select_dropdown_by_value,
    select_table_by_row
} 
from './helpers/helper.js';
import { userVariables } from '../../.testcaferc';

fixture `admin_submissions_fixture`
    .page(userVariables.admin_submissions_page)
    .beforeEach(async t => {
        var http = require('http');
        await http.get(userVariables.admin_submission_reset);
    });

test('Approve_New_Meeting', async t => {

    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
    await click_table_row_column(as.dt_submission,1,1);

});
