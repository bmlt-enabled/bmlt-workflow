import { Selector, Role } from 'testcafe';
import { userVariables } from '../../../.testcaferc';
import { wordpress_login } from './wordpress_login';


export const wbw_admin = Role(userVariables.admin_logon_page, async t => {
    await t
    .typeText(wordpress_login.user_login, userVariables.admin_logon)
    .typeText(wordpress_login.user_pass, userVariables.admin_password)
    .click(wordpress_login.wp_submit);
});

class Admin_Options {
    constructor () {
        this.backup_button = Selector("#wbw_backup");
        this.restore_button = Selector("#wbw_restore");
        this.wbw_file_selector = Selector("#wbw_file_selector");
        // dialogs
        this.restore_warning_dialog = Selector("#wbw_restore_warning_dialog");
        this.restore_warning_dialog_parent = this.restore_warning_dialog.parent();
        this.wbw_fso_email_address = Selector("#wbw_fso_email_address");
        this.wbw_optional_location_nation = Selector("#wbw_optional_location_nation");
        this.wbw_optional_location_sub_province = Selector("#wbw_optional_location_sub_province");
        this.wbw_delete_closed_meetings = Selector("#wbw_delete_closed_meetings");
        this.wbw_email_from_address = Selector("#wbw_email_from_address");
        this.submit = Selector('#submit');
    }
}

export const ao = new Admin_Options();