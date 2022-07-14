import { Selector } from 'testcafe';

class Admin_Service_Bodies {
    constructor () {
        this.wbw_submit = Selector('#wbw_submit');
    }
}

export const asb = new Admin_Service_Bodies();