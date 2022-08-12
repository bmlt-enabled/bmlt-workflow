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
class Admin_Submissions {
    constructor () {
        // datatables
        this.dt_submission = Selector('#dt-submission');
        this.dt_submission_wrapper = Selector("#dt-submission_wrapper");
        // dialogs
        this.approve_dialog = Selector("#bw_submission_approve_dialog");
        this.approve_dialog_parent = this.approve_dialog.parent();
        this.approve_dialog_textarea = Selector("#bw_submission_approve_dialog_textarea");
        this.approve_close_dialog = Selector("#bw_submission_approve_close_dialog");
        this.approve_close_dialog_parent = this.approve_close_dialog.parent();
        this.approve_close_dialog_textarea = Selector("#bw_submission_approve_close_dialog_textarea");
        this.reject_dialog = Selector("#bw_submission_reject_dialog");
        this.reject_dialog_parent = this.reject_dialog.parent();
        this.reject_dialog_textarea = Selector("#bw_submission_reject_dialog_textarea");
        this.quickedit_dialog = Selector("#bw_submission_quickedit_dialog");
    }
}

export const as = new Admin_Submissions();