import { ao, wbw_admin } from './models/admin_options';
import { userVariables } from '../../.testcaferc';
import { RequestLogger } from 'testcafe';

import { 
    click_dialog_button_by_index,
}

from './helpers/helper.js';

const url = 'http://54.153.167.239/flop/wp-json/wbw/v1/options/backup';

const logger = RequestLogger({ url, method: 'post' }, {
    logResponseHeaders: true,
    logResponseBody:    true
});

fixture `admin_options_fixture`
    .beforeEach(async t => {
        var http = require('http');
        // pre fill the submissions
        http.get(userVariables.admin_submission_reset);
        // // reset bmlt to reasonable state
        // http.get(userVariables.blank_bmlt);
        await t.useRole(wbw_admin)
        .navigateTo(userVariables.admin_options_page)
    })
    .requestHooks(logger);
    ;


test('Backup', async t => {
    await t
        .click(ao.backup_button)
        // .expect(logger.contains(r => r.response.statusCode === 200)).ok();
    // debugger;

    // console.log(logger.requests);
    var f=JSON.parse(logger.requests[0].response.body.toString())
    var backup = JSON.parse(f.backup);
    await t.expect(f.message).eql('Backup Successful')
    .expect(backup.options.wbw_db_version).eql("0.4.0")
    .expect(backup.options.wbw_bmlt_server_address).eql("http:\/\/54.153.167.239\/blank_bmlt\/main_server\/");
    // find a specific meeting
    let obj = backup.submissions.find(o => o.id === '94');

    await t.expect(obj.submitter_name).eql("first last")
    .expect(obj.submission_type).eql("reason_change");

});

test('Restore', async t => {
    await t
    .setFilesToUpload(ao.wbw_file_selector, [
        './uploads/restoretest1.json',
    ])
    // .click(ao.restore_button)
    // .debug()
    .expect(ao.restore_warning_dialog_parent.visible).eql(true);
    // click ok
    await click_dialog_button_by_index(ao.restore_warning_dialog_parent,1);
    // dialog closes after ok button
    await t
    .expect(ao.restore_warning_dialog_parent.visible).eql(false);

});
