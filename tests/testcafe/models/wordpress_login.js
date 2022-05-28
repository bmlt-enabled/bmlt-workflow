import { Selector } from 'testcafe';

class Wordpress_Login {
    constructor () {

        this.user_login = Selector('#user_login');
        this.user_pass = Selector('#user_pass');
        this.wp_submit = Selector('#wp-submit');
    }
}

export const wordpress_login = new Wordpress_Login();