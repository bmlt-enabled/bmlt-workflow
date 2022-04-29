import { Selector } from 'testcafe';

class Admin_Submissions {
    constructor () {

        this.dt_submission = Selector('#dt-submission');

    }
}

export const as = new Admin_Submissions();