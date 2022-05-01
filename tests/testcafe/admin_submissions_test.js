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
    // new meeting = row 0
    var row = 0;
    await click_table_row_column(as.dt_submission,row,0);
    // approve
    await click_dt_button_by_index(as.dt_submission_wrapper,0);

    await t
    .expect(as.approve_dialog_parent.visible).eql(true);

    await t
    .typeText(as.approve_dialog_textarea, 'I approve this request');
    // press ok button
    await click_dialog_button_by_index(as.approve_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(as.approve_dialog_parent.visible).eql(false);

    var column = 8;
    await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Approved');

});

test('Approve_Modify_Meeting', async t => {

    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
    // modify meeting = row 1
    var row = 1;
    await click_table_row_column(as.dt_submission,row,0);
    // approve
    await click_dt_button_by_index(as.dt_submission_wrapper,0);

    await t
    .expect(as.approve_dialog_parent.visible).eql(true);

    await t
    .typeText(as.approve_dialog_textarea, 'I approve this request');
    // press ok button
    await click_dialog_button_by_index(as.approve_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(as.approve_dialog_parent.visible).eql(false);

    // const s = Selector("#dt-submission tr:nth-child(1) td:nth-child(9)");
    var column = 8;
    await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Approved');

});

test('Approve_Close_Meeting', async t => {

    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
    // close meeting = row 2
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    // approve
    await click_dt_button_by_index(as.dt_submission_wrapper,0);

    await t
    .expect(as.approve_close_dialog_parent.visible).eql(true);

    await t
    .typeText(as.approve_close_dialog_textarea, 'I approve this request');
    // press ok button
    await click_dialog_button_by_index(as.approve_close_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(as.approve_close_dialog_parent.visible).eql(false);

    var column = 8;
    await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Approved');

});

test('Reject_New_Meeting', async t => {

    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
    // new meeting = row 0
    var row = 0;
    await click_table_row_column(as.dt_submission,row,0);
    // reject
    await click_dt_button_by_index(as.dt_submission_wrapper,1);

    await t
    .expect(as.reject_dialog_parent.visible).eql(true);

    await t
    .typeText(as.reject_dialog_textarea, 'I reject this request');
    // press ok button
    await click_dialog_button_by_index(as.reject_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(as.reject_dialog_parent.visible).eql(false);

    var column = 8;
    await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Rejected');

});

test('Reject_Modify_Meeting', async t => {

    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
    // modify meeting = row 1
    var row = 1;
    await click_table_row_column(as.dt_submission,row,0);
    // reject
    await click_dt_button_by_index(as.dt_submission_wrapper,1);

    await t
    .expect(as.reject_dialog_parent.visible).eql(true);

    await t
    .typeText(as.reject_dialog_textarea, 'I reject this request');
    // press ok button
    await click_dialog_button_by_index(as.reject_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(as.reject_dialog_parent.visible).eql(false);

    // const s = Selector("#dt-submission tr:nth-child(1) td:nth-child(9)");
    var column = 8;
    await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Rejected');

});

test('Reject_Close_Meeting', async t => {

    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
    // close meeting = row 2
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    // reject
    await click_dt_button_by_index(as.dt_submission_wrapper,1);

    await t
    .expect(as.reject_dialog_parent.visible).eql(true);

    await t
    .typeText(as.reject_dialog_textarea, 'I reject this request');
    // press ok button
    await click_dialog_button_by_index(as.reject_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(as.reject_dialog_parent.visible).eql(false);

    var column = 8;
    await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Rejected');

});


test('Submission_Buttons_Active_correctly', async t => {

    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
    // new meeting = row 0
    var row = 0;
    await click_table_row_column(as.dt_submission,row,0);
    // approve
    var g = as.dt_submission_wrapper.find('button').nth(0);
    await t.expect(g.hasAttribute('disabled')).notOk();
    // reject
    g = as.dt_submission_wrapper.find('button').nth(1);
    await t.expect(g.hasAttribute('disabled')).notOk();
    // quickedit
    g = as.dt_submission_wrapper.find('button').nth(2);
    await t.expect(g.hasAttribute('disabled')).notOk();

    // change meeting = row 1
    var row = 1;
    await click_table_row_column(as.dt_submission,row,0);
    // approve
    g = as.dt_submission_wrapper.find('button').nth(0);
    await t.expect(g.hasAttribute('disabled')).notOk();
    // reject
    g = as.dt_submission_wrapper.find('button').nth(1);
    await t.expect(g.hasAttribute('disabled')).notOk();
    // quickedit
    g = as.dt_submission_wrapper.find('button').nth(2);
    await t.expect(g.hasAttribute('disabled')).notOk();

    // close meeting = row 2
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    // approve
    g = as.dt_submission_wrapper.find('button').nth(0);
    await t.expect(g.hasAttribute('disabled')).notOk();
    // reject
    g = as.dt_submission_wrapper.find('button').nth(1);
    await t.expect(g.hasAttribute('disabled')).notOk();
    // quickedit
    g = as.dt_submission_wrapper.find('button').nth(2);
    await t.expect(g.hasAttribute('disabled')).ok();
    
    // reject a request then we check the buttons again
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    await click_table_row_column(as.dt_submission,row,0);
    // reject
    await click_dt_button_by_index(as.dt_submission_wrapper,1);

    await t
    .expect(as.reject_dialog_parent.visible).eql(true);

    await t
    .typeText(as.reject_dialog_textarea, 'I reject this request');
    // press ok button
    await click_dialog_button_by_index(as.reject_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(as.reject_dialog_parent.visible).eql(false);

    var column = 8;
    await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Rejected');

    // rejected request has no approve, reject, quickedit
    // close meeting = row 2
    var row = 2;
    await click_table_row_column(as.dt_submission,row,0);
    // approve
    g = as.dt_submission_wrapper.find('button').nth(0);
    await t.expect(g.hasAttribute('disabled')).ok();
    // reject
    g = as.dt_submission_wrapper.find('button').nth(1);
    await t.expect(g.hasAttribute('disabled')).ok();
    // quickedit
    g = as.dt_submission_wrapper.find('button').nth(2);
    await t.expect(g.hasAttribute('disabled')).ok();
    
});

// test('Quickedit_New_Meeting', async t => {

//     await t
//     .typeText(wordpress_login.user_login, userVariables.admin_logon)
//     .typeText(wordpress_login.user_pass, userVariables.admin_password)
//     .click(wordpress_login.wp_submit);
//     // new meeting = row 0
//     var row = 0;
//     await click_table_row_column(as.dt_submission,row,0);
//     // quickedit
//     await click_dt_button_by_index(as.dt_submission_wrapper,2);

//     await t
//     .expect(as.approve_dialog_parent.visible).eql(true);

//     await t
//     .typeText(as.approve_dialog_textarea, 'I approve this request');
//     // press ok button
//     await click_dialog_button_by_index(as.approve_dialog_parent,1);
//     // dialog closes after ok button
//     await t
//     .expect(as.approve_dialog_parent.visible).eql(false);

//     var column = 8;
//     await t .expect((as.dt_submission.child('tbody').child(row).child(column)).innerText).eql('Approved');

// });