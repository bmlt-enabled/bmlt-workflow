
// standard paths
const sitejsonurl = "/wp-json"
const admin_submissions_page = "/wp-admin/admin.php?page=bmltwf-submissions";
const admin_service_bodies_page = "/wp-admin/admin.php?page=bmltwf-service-bodies";
const admin_options_page = "/wp-admin/admin.php?page=bmltwf-settings";
const admin_logon_page = "/wp-admin/admin.php";
const formpage = "/index.php/testpage/";
const admin_backup_json = "/bmltwf/v1/options/backup";
const backuppath = "/index.php" + sitejsonurl + admin_backup_json;

const execSync = require('child_process').execSync;

// web addresses
const test_ip = execSync('aws ssm get-parameter --name bmltwf_test_hostip --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }).trim(); 
const siteurl_dev = "http://"+ test_ip + "/wordpressdev";
const siteurl_multidev = "http://"+test_ip+"/wordpressmultidev";
const siteurl_multinetworkdev = "http://"+test_ip+"/wordpressmultinetworkdev";

// usernames and passwords
const username_dev = execSync('aws ssm get-parameter --name bmltwf_test_wpuser_dev --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 
const password_dev = execSync('aws ssm get-parameter --name bmltwf_test_wppass_dev --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 

const username_multidev = execSync('aws ssm get-parameter --name bmltwf_test_wpuser_multidev --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 
const password_multidev = execSync('aws ssm get-parameter --name bmltwf_test_wppass_multidev --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 

const username_multinetworkdev = execSync('aws ssm get-parameter --name bmltwf_test_wpuser_multinetworkdev --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 
const password_multinetworkdev = execSync('aws ssm get-parameter --name bmltwf_test_wppass_multinetworkdev --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r', { encoding: 'utf-8' }); 


module.exports = 
{
    "browsers": "chrome",
    "userVariables": {

        "formpage": siteurl_dev + formpage,
        "admin_logon_page": siteurl_dev + admin_logon_page,
        "admin_submissions_page": siteurl_dev + admin_submissions_page,
        "admin_service_bodies_page": siteurl_dev + admin_service_bodies_page,
        "admin_options_page": siteurl_dev + admin_options_page,
        "admin_logon": username_dev,
        "admin_password": password_dev,
        "admin_backup_json" : siteurl_dev + backuppath,
// multisite
        "formpage_multidev": siteurl_multidev +formpage,
        "admin_logon_page_multidev": siteurl_multidev + admin_logon_page,
        "admin_submissions_page_multidev": siteurl_multidev + admin_submissions_page,
        "admin_service_bodies_page_multidev": siteurl_multidev + admin_service_bodies_page,
        "admin_options_page_multidev": siteurl_multidev + admin_options_page,
        "admin_logon_multidev": username_multidev,
        "admin_password_multidev": password_multidev,
        "admin_backup_json_multidev" : siteurl_multidev + backuppath,
// multisite network install
        "formpage_multinetworkdev": siteurl_multinetworkdev + formpage,
        "admin_logon_page_multinetworkdev": siteurl_multinetworkdev + admin_logon_page,
        "admin_submissions_page_multinetworkdev": siteurl_multinetworkdev + admin_submissions_page,
        "admin_service_bodies_page_multinetworkdev": siteurl_multinetworkdev + admin_service_bodies_page,
        "admin_options_page_multinetworkdev": siteurl_multinetworkdev + admin_options_page,
        "admin_logon_multinetworkdev": username_multinetworkdev,
        "admin_password_multinetworkdev": password_multinetworkdev,
        "admin_backup_json_multinetworkdev" : siteurl_multinetworkdev + backuppath,
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