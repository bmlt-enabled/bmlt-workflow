import { Selector } from 'testcafe';

class Admin_Submissions {
    constructor () {
        // datatables
        this.dt_submission = Selector('#dt-submission');
        this.dt_submission_wrapper = Selector("#dt-submission_wrapper");
        // dialogs
        this.approve_dialog = Selector("#wbw_submission_approve_dialog");
        this.approve_dialog_parent = this.approve_dialog.parent();
        this.approve_dialog_textarea = Selector("#wbw_submission_approve_dialog_textarea");
        this.approve_close_dialog = Selector("#wbw_submission_approve_close_dialog");
        this.approve_close_dialog_parent = this.approve_close_dialog.parent();
        this.approve_close_dialog_textarea = Selector("#wbw_submission_approve_close_dialog_textarea");
    }
}

export const as = new Admin_Submissions();