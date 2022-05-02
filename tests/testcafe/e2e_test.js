import { Selector } from 'testcafe';
import { as } from './models/admin_submissions';
import { wordpress_login } from './models/wordpress_login';

import { 
    click_table_row_column,
    click_dt_button_by_index,
    click_dialog_button_by_index,
    get_table_row_col,
    select_dropdown_by_id,
    select_dropdown_by_text,
    select_dropdown_by_value,
}

from './helpers/helper.js';
import { userVariables } from '../../.testcaferc';

fixture `e2e_test_fixture`
    .page(userVariables.admin_submissions_page)
    .beforeEach(async t => {
        var http = require('http');
        await http.get(userVariables.admin_submission_reset);
    });

test('Approve_New_Meeting', async t => {

});

