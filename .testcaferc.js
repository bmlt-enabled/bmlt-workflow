// standard paths
const sitejsonurl = "/wp-json";
const admin_submissions_page = "/wp-admin/admin.php?page=bmltwf-submissions";
const admin_service_bodies_page = "/wp-admin/admin.php?page=bmltwf-service-bodies";
const admin_settings_page = "/wp-admin/admin.php?page=bmltwf-settings";
const admin_options_page = "/wp-admin/options.php";
const multisite_plugin = "/plugin";
const multisite_noplugin = "/noplugin";
const multisite_plugin2 = "/plugin2";
const admin_logon_page = "/wp-admin/admin.php";
const formpage = "/testpage/";
const admin_backup_json_path = "/bmltwf/v1/options/backup";
const backuppath = "/index.php" + sitejsonurl + admin_backup_json_path;
const admin_restore_json_path = "/bmltwf/v1/options/restore";
const restorepath = "/index.php" + sitejsonurl + admin_restore_json_path;

const execSync = require("child_process").execSync;

// web addresses
// const test_ip = execSync("aws ssm get-parameter --name bmltwf_test_hostip --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", { encoding: "utf-8" }).trim();
// const test_ip = 'localhost';
// const siteurl_single = "http://" + test_ip + ":8081";
// const siteurl_multisingle = "http://" + test_ip + ":8082";
// const siteurl_multinetwork = "http://" + test_ip + ":8083";
// const siteurl_single = "http://" + test_ip + "/wordpresssingle";
// const siteurl_multisingle = "http://" + test_ip + "/wordpressmultisingle";
// const siteurl_multinetwork = "http://" + test_ip + "/wordpressmultinetwork";
// const siteurl_wpsinglebmlt3x = "http://" + test_ip + "/wpsinglebmlt3x"
const siteurl_single = "http://wordpress-php8-singlesite"
const siteurl_multisingle = "http://wordpress-php8-multisitesingle";
const siteurl_multinetwork = "http://wordpress-php8-multinetwork";
const siteurl_wpsinglebmlt3x = "http://wordpress-php8-singlesite-bmlt3x"

// usernames and passwords
// const username_single = execSync("aws ssm get-parameter --name bmltwf_test_wpuser_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", { encoding: "utf-8" });
// const password_single = execSync("aws ssm get-parameter --name bmltwf_test_wppass_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", { encoding: "utf-8" });
// const username_submission_single = execSync("aws ssm get-parameter --name bmltwf_test_wp_submission_user_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", { encoding: "utf-8" });
// const password_submission_single = execSync("aws ssm get-parameter --name bmltwf_test_wp_submission_pass_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", { encoding: "utf-8" });
// const username_nopriv_single = execSync("aws ssm get-parameter --name bmltwf_test_wp_nopriv_user_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", { encoding: "utf-8" });
// const password_nopriv_single = execSync("aws ssm get-parameter --name bmltwf_test_wp_nopriv_pass_single --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", { encoding: "utf-8" });

// const username_multisingle = execSync("aws ssm get-parameter --name bmltwf_test_wpuser_multisingle --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", {
//   encoding: "utf-8",
// });
// const password_multisingle = execSync("aws ssm get-parameter --name bmltwf_test_wppass_multisingle --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", {
//   encoding: "utf-8",
// });

// const username_multinetwork = execSync("aws ssm get-parameter --name bmltwf_test_wpuser_multinetwork --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", {
//   encoding: "utf-8",
// });
// const password_multinetwork = execSync("aws ssm get-parameter --name bmltwf_test_wppass_multinetwork --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", {
//   encoding: "utf-8",
// });

// const username_wpsinglebmlt3x = execSync("aws ssm get-parameter --name bmltwf_test_wpuser_bmlt3x --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", {
//   encoding: "utf-8",
// });
// const password_wpsinglebmlt3x = execSync("aws ssm get-parameter --name bmltwf_test_wppass_bmlt3x --profile nb --region ap-southeast-2 --with-decryption | jq .Parameter.Value -r", {
//   encoding: "utf-8",
// });


const username_single = 'admin';
const password_single = 'admin';
const username_submission_single = 'submission';
const password_submission_single = 'submission';
const username_nopriv_single = 'nopriv';
const password_nopriv_single = 'nopriv';
const username_multisingle = 'submission';
const password_multisingle = 'submission';
const username_multinetwork ='submission';
const password_multinetwork = 'submission';
const username_wpsinglebmlt3x = 'submission';
const password_wpsinglebmlt3x ='submission';

module.exports = {
  browsers: "chrome",
  userVariables: {
    formpage: siteurl_single + '/index.php' + formpage,
    admin_logon_page: siteurl_single + admin_logon_page,
    admin_submissions_page: siteurl_single + admin_submissions_page,
    admin_service_bodies_page: siteurl_single + admin_service_bodies_page,
    admin_settings_page: siteurl_single + admin_settings_page,
    admin_options_page: siteurl_single + admin_options_page,
    admin_logon: username_single,
    admin_password: password_single,
    submission_reviewer_user: username_submission_single,
    submission_reviewer_pass: password_submission_single,
    submission_reviewer_nopriv_user: username_nopriv_single,
    submission_reviewer_nopriv_pass: password_nopriv_single,
    admin_backup_json: siteurl_single + admin_backup_json_path,
    admin_restore_json: siteurl_single + admin_restore_json_path,

    // multisite
    formpage_multisingle: siteurl_multisingle + multisite_plugin + formpage,
    admin_logon_page_multisingle: siteurl_multisingle + admin_logon_page,
    admin_submissions_page_multisingle: siteurl_multisingle + admin_submissions_page,
    admin_submissions_page_multisingle_plugin: siteurl_multisingle + multisite_plugin + admin_submissions_page,
    admin_submissions_page_multisingle_noplugin: siteurl_multisingle + multisite_noplugin + admin_submissions_page,
    admin_service_bodies_page_multisingle: siteurl_multisingle + admin_service_bodies_page,
    admin_service_bodies_page_multisingle_plugin: siteurl_multisingle + multisite_plugin + admin_service_bodies_page,
    admin_service_bodies_page_multisingle_noplugin: siteurl_multisingle + multisite_noplugin + admin_service_bodies_page,
    admin_settings_page_multisingle: siteurl_multisingle + admin_settings_page,
    admin_settings_page_multisingle_plugin: siteurl_multisingle + multisite_plugin + admin_settings_page,
    admin_settings_page_multisingle_noplugin: siteurl_multisingle + multisite_noplugin + admin_settings_page,
    admin_options_page_multisingle: siteurl_multisingle + admin_options_page,
    admin_options_page_multisingle_plugin: siteurl_multisingle + multisite_plugin + admin_options_page,
    admin_options_page_multisingle_noplugin: siteurl_multisingle + multisite_noplugin + admin_options_page,
    admin_logon_multisingle: username_multisingle,
    admin_password_multisingle: password_multisingle,
    admin_backup_json_multisingle: siteurl_multisingle + multisite_plugin + backuppath,
    // multisite network install
    formpage_multinetwork: siteurl_multinetwork + formpage,
    admin_logon_page_multinetwork: siteurl_multinetwork + admin_logon_page,
    admin_submissions_page_multinetwork: siteurl_multinetwork + admin_submissions_page,
    admin_submissions_page_multinetwork_plugin: siteurl_multinetwork + multisite_plugin + admin_submissions_page,
    admin_submissions_page_multinetwork_plugin2: siteurl_multinetwork + multisite_plugin2 + admin_submissions_page,
    admin_service_bodies_page_multinetwork: siteurl_multinetwork + admin_service_bodies_page,
    admin_service_bodies_page_multinetwork_plugin: siteurl_multinetwork + multisite_plugin + admin_service_bodies_page,
    admin_service_bodies_page_multinetwork_plugin2: siteurl_multinetwork + multisite_plugin2 + admin_service_bodies_page,
    admin_settings_page_multinetwork: siteurl_multinetwork + admin_settings_page,
    admin_settings_page_multinetwork_plugin: siteurl_multinetwork + multisite_plugin + admin_settings_page,
    admin_settings_page_multinetwork_plugin2: siteurl_multinetwork + multisite_plugin2 + admin_settings_page,
    admin_options_page_multinetwork: siteurl_multinetwork + admin_options_page,
    admin_options_page_multinetwork_plugin: siteurl_multinetwork + multisite_plugin + admin_options_page,
    admin_options_page_multinetwork_plugin2: siteurl_multinetwork + multisite_plugin2 + admin_options_page,
    admin_logon_multinetwork: username_multinetwork,
    admin_password_multinetwork: password_multinetwork,
    admin_backup_json_multinetwork: siteurl_multinetwork + multisite_plugin + backuppath,
    // bmlt3x
    formpage_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + formpage,
    admin_logon_page_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + admin_logon_page,
    admin_submissions_page_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + admin_submissions_page,
    admin_service_bodies_page_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + admin_service_bodies_page,
    admin_settings_page_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + admin_settings_page,
    admin_options_page_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + admin_options_page,
    admin_logon_wpsinglebmlt3x: username_wpsinglebmlt3x,
    admin_password_wpsinglebmlt3x: password_wpsinglebmlt3x,
    admin_backup_json_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + multisite_plugin + backuppath,

    // // test case resetters
    // admin_submission_reset: "http://" + test_ip + "/github/db_submissions.php",
    // blank_bmlt: "http://" + test_ip + "/github/blank_bmlt.php",
    blank_bmlt: "docker-compose -f /testroot/bmlt-workflow/docker/bmlt2x.yml --env-file /testroot/bmlt-workflow/docker/bmlt.env restart bmlt2x db2x",
    // blank_bmlt3x: "http://" + test_ip + "/github/blank_bmlt3x.php",
    blank_bmlt3x: "docker-compose -f /testroot/bmlt-workflow/docker/bmlt3x.yml --env-file /testroot/bmlt-workflow/docker/bmlt.env restart bmlt3x db3x",
    // blank_submission: "http://" + test_ip + "/github/blank_submission.php",
    blank_submission: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-singlesite db-php8-singlesite",
    // blank_submission_multisingle: "http://" + test_ip + "/github/blank_submission_multisingle.php",
    blank_submission_multisingle: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-multisitesingle db-php8-multisitesingle",
    // blank_submission_multinetwork: "http://" + test_ip + "/github/blank_submission_multinetwork.php",
    blank_submission_multinetwork: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-multinetwork db-php8-multinetwork",
    // blank_submission_wpsinglebmlt3x: "http://" + test_ip + "/github/blank_submission_wpsinglebmlt3x.php",
    blank_submission_wpsinglebmlt3x: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-singlesite-bmlt3x db-php8-singlesite-bmlt3x",
    // blank_service_bodies: "http://" + test_ip + "/github/blank_service_bodies.php",
    // blank_service_bodies_multisingle: "http://" + test_ip + "/github/blank_service_bodies_multisingle.php",
    // blank_service_bodies_multinetwork: "http://" + test_ip + "/github/blank_service_bodies_multinetwork.php",
    // blank_service_bodies_wpsinglebmlt3xmultinetwork: "http://" + test_ip + "/github/blank_service_bodies_wpsinglebmlt3x.php",
    blank_service_bodies: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-singlesite db-php8-singlesite",
    blank_service_bodies_multisingle: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-multisitesingle db-php8-multisitesingle",
    blank_service_bodies_multinetwork: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-multinetwork db-php8-multinetwork",
    blank_service_bodies_wpsinglebmlt3x: "docker-compose -f /testroot/bmlt-workflow/docker/docker-compose.yml restart wordpress-php8-singlesite-bmlt3x db-php8-singlesite-bmlt3x",
    // e2e_test: "http://" + test_ip + "/github/e2e_test.php",
    // bmlt_states_on: "http://" + test_ip + "/github/bmlt_states_on.php",
    // bmlt_states_off: "http://" + test_ip + "/github/bmlt_states_off.php",
    // auto_geocoding_on: "http://" + test_ip + "/github/auto_geocoding_on.php",
    // auto_geocoding_off: "http://" + test_ip + "/github/auto_geocoding_off.php",
    // bmlt3x_states_on: "http://" + test_ip + "/github/bmlt3x_states_on.php",
    // bmlt3x_states_off: "http://" + test_ip + "/github/bmlt3x_states_off.php",
    // bmlt3x_auto_geocoding_on: "http://" + test_ip + "/github/bmlt3x_auto_geocoding_on.php",
    // bmlt3x_auto_geocoding_off: "http://" + test_ip + "/github/bmlt3x_auto_geocoding_off.php",

    crouton_page: siteurl_single+"/index.php/crouton/",
    crouton3x_page: siteurl_wpsinglebmlt3x+"/index.php/crouton/",
    // bmlt_address: "http://" + test_ip + "/blank_bmlt/main_server/",
  },
};
