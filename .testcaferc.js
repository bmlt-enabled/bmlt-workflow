
// standard paths
const sitejsonurl = "/wp-json"
const admin_submissions_page = "/wp-admin/admin.php?page=bmltwf-submissions";
const admin_service_bodies_page = "/wp-admin/admin.php?page=bmltwf-service-bodies";
const admin_settings_page = "/wp-admin/admin.php?page=bmltwf-settings";
const admin_options_page = "/wp-admin/options.php";
const multisite_plugin = "/plugin";
const multisite_noplugin = "/noplugin";
const multisite_plugin2 = "/plugin2";
const admin_logon_page = "/wp-admin/admin.php";
const formpage = "/index.php/testpage/";
const admin_backup_json = "/bmltwf/v1/options/backup";
const backuppath = "/index.php" + sitejsonurl + admin_backup_json;

const execSync = require('child_process').execSync;

// web addresses
const test_ip = execSync('aws ssm get-parameter --name bmltwf_test_hostip --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }).trim(); 
const siteurl_single = "http://"+ test_ip + "/wordpresssingle";
const siteurl_multisingle = "http://"+test_ip+"/wordpressmultisingle";
const siteurl_multinetwork = "http://"+test_ip+"/wordpressmultinetwork";

// usernames and passwords
const username_single = execSync('aws ssm get-parameter --name bmltwf_test_wpuser_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 
const password_single = execSync('aws ssm get-parameter --name bmltwf_test_wppass_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 

const username_multisingle = execSync('aws ssm get-parameter --name bmltwf_test_wpuser_multisingle --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 
const password_multisingle = execSync('aws ssm get-parameter --name bmltwf_test_wppass_multisingle --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 

const username_multinetwork = execSync('aws ssm get-parameter --name bmltwf_test_wpuser_multinetwork --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 
const password_multinetwork = execSync('aws ssm get-parameter --name bmltwf_test_wppass_multinetwork --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 


module.exports = 
{
    "browsers": "chrome",
    "userVariables": {

        "formpage": siteurl_single + formpage,
        "admin_logon_page": siteurl_single + admin_logon_page,
        "admin_submissions_page": siteurl_single + admin_submissions_page,
        "admin_service_bodies_page": siteurl_single + admin_service_bodies_page,
        "admin_settings_page": siteurl_single + admin_settings_page,
        "admin_options_page": siteurl_single + admin_options_page,
        "admin_logon": username_single,
        "admin_password": password_single,
        "admin_backup_json" : siteurl_single + backuppath,
// multisite
        "formpage_multisingle": siteurl_multisingle +formpage,
        "admin_logon_page_multisingle": siteurl_multisingle + admin_logon_page,
        "admin_submissions_page_multisingle": siteurl_multisingle + admin_submissions_page,
        "admin_service_bodies_page_multisingle": siteurl_multisingle + admin_service_bodies_page,
        "admin_settings_page_multisingle_plugin": siteurl_multisingle + multisite_plugin + admin_settings_page,
        "admin_settings_page_multisingle_noplugin": siteurl_multisingle + multisite_noplugin + admin_settings_page,
        "admin_options_page_multisingle": siteurl_multisingle + admin_options_page,
        "admin_options_page_multisingle_plugin": siteurl_multisingle + multisite_plugin + admin_options_page,
        "admin_options_page_multisingle_noplugin": siteurl_multisingle + multisite_noplugin + admin_options_page,
        "admin_logon_multisingle": username_multisingle,
        "admin_password_multisingle": password_multisingle,
        "admin_backup_json_multisingle" : siteurl_multisingle + backuppath,
// multisite network install
        "formpage_multinetwork": siteurl_multinetwork + formpage,
        "admin_logon_page_multinetwork": siteurl_multinetwork + admin_logon_page,
        "admin_submissions_page_multinetwork": siteurl_multinetwork + admin_submissions_page,
        "admin_service_bodies_page_multinetwork": siteurl_multinetwork + admin_service_bodies_page,
        "admin_settings_page_multinetwork": siteurl_multinetwork + admin_settings_page,
        "admin_settings_page_multisingle_plugin": siteurl_multisingle + multisite_plugin + admin_settings_page,
        "admin_settings_page_multisingle_plugin2": siteurl_multisingle + multisite_plugin2 + admin_settings_page,
        "admin_options_page_multinetwork": siteurl_multinetwork + admin_options_page,
        "admin_options_page_multinetwork_plugin": siteurl_multisingle + multisite_plugin + admin_options_page,
        "admin_options_page_multinetwork_plugin2": siteurl_multisingle + multisite_plugin2 + admin_options_page,
        "admin_logon_multinetwork": username_multinetwork,
        "admin_password_multinetwork": password_multinetwork,
        "admin_backup_json_multinetwork" : siteurl_multinetwork + backuppath,
// test case resetters
        "admin_submission_reset": "http://"+test_ip+"/github/db_submissions.php",
        "blank_bmlt": "http://"+test_ip+"/github/blank_bmlt.php",
        "blank_submission": "http://"+test_ip+"/github/blank_submission.php",
        "blank_service_bodies": "http://"+test_ip+"/github/blank_service_bodies.php",
        "e2e_test": "http://"+test_ip+"/github/e2e_test.php",
        "bmlt_states_on": "http://"+test_ip+"/github/bmlt_states_on.php",
        "bmlt_states_off": "http://"+test_ip+"/github/bmlt_states_off.php",

        "crouton_page": "http://"+test_ip+"/flop/21-2/"
    }
}