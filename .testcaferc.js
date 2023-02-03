// standard paths
const sitejsonurl = "/wp-json";
const admin_submissions_page = "/wp-admin/admin.php?page=bmltwf-submissions";
const admin_service_bodies_page = "/wp-admin/admin.php?page=bmltwf-service-bodies";
const admin_settings_page = "/wp-admin/admin.php?page=bmltwf-settings";
const admin_options_page = "/wp-admin/options.php";
const multisite_plugin = "/plugin";
const multisite_noplugin = "/noplugin";
const multisite_plugin2 = "/plugin2";
const admin_logon_page = "/wp-login.php";
const croutonpage = "?page_id=4";
const formpage = "?page_id=5";
const formpage2 = "/testpage/";

const admin_backup_json_path = "/index.php?rest_route=/bmltwf/v1/options/backup";
const backuppath = "/index.php" + sitejsonurl + admin_backup_json_path;
const admin_restore_json_path = "/index.php?rest_route=/bmltwf/v1/options/restore";
const restorepath = "/index.php" + sitejsonurl + admin_restore_json_path;

const execSync = require("child_process").execSync;

// web addresses
const siteurl_single = "http://wordpress-php8-singlesite"
const siteurl_multisingle = "http://wordpress-php8-multisitesingle:81/wordpress-php8-multisitesingle";
const siteurl_multinetwork = "http://wordpress-php8-multinetwork:82/wordpress-php8-multinetwork";
const siteurl_wpsinglebmlt3x = "http://wordpress-php8-singlesite-bmlt3x:83"

const username_single = 'admin';
const password_single = 'admin';
const username_submission_single = 'submitpriv';
const password_submission_single = 'submitpriv';
const username_nopriv_single = 'nopriv';
const password_nopriv_single = 'nopriv';
const username_multisingle = 'admin';
const password_multisingle = 'admin';
const username_multinetwork ='admin';
const password_multinetwork = 'admin';
const username_wpsinglebmlt3x = 'admin';
const password_wpsinglebmlt3x ='admin';

module.exports = {
  browsers: "chrome",
  screenshots: {
    path: "./tests/testcafe/screenshots/",
    takeOnFails: true,
    thumbnails: false
  },
  userVariables: {
    formpage: siteurl_single + '/index.php' + formpage,
    admin_logon_page_single: siteurl_single + admin_logon_page,
    admin_submissions_page_single: siteurl_single + admin_submissions_page,
    admin_service_bodies_page_single: siteurl_single + admin_service_bodies_page,
    admin_settings_page_single: siteurl_single + admin_settings_page,
    admin_options_page_single: siteurl_single + admin_options_page,
    admin_logon_single: username_single,
    admin_password_single: password_single,
    submission_reviewer_user: username_submission_single,
    submission_reviewer_pass: password_submission_single,
    submission_reviewer_nopriv_user: username_nopriv_single,
    submission_reviewer_nopriv_pass: password_nopriv_single,
    admin_backup_json: siteurl_single + admin_backup_json_path,
    admin_restore_json: siteurl_single + admin_restore_json_path,

    // multisite
    formpage_multisingle: siteurl_multisingle + multisite_plugin + formpage2,
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
    admin_restore_json_multisingle_plugin: siteurl_multisingle + multisite_plugin + admin_restore_json_path,

    // multisite network install
    formpage_multinetwork: siteurl_multinetwork + formpage2,
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
    admin_restore_json_multinetwork_plugin: siteurl_multinetwork + multisite_plugin + admin_restore_json_path,
    admin_restore_json_multinetwork_plugin2: siteurl_multinetwork + multisite_plugin2 + admin_restore_json_path,
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
    admin_restore_json_wpsinglebmlt3x: siteurl_wpsinglebmlt3x + admin_restore_json_path,

    // // test case resetters
    blank_bmlt: "export AUTO_GEOCODING_ON=true; export MEETING_STATES_ON=false; docker compose -f ../bmlt2x/docker/docker-compose.yml --env-file ../bmlt2x/docker/bmlt.env down; docker compose -f ../bmlt2x/docker/docker-compose.yml --env-file ../bmlt2x/docker/bmlt.env up --detach",
    blank_bmlt3x: "export AUTO_GEOCODING_ON=true; export MEETING_STATES_ON=false; docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env down; docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env up --detach",
    auto_geocoding_off: "export AUTO_GEOCODING_ON=false; docker compose -f ../bmlt2x/docker/docker-compose.yml --env-file ../bmlt2x/docker/bmlt.env down; docker compose -f ../bmlt2x/docker/docker-compose.yml --env-file ../bmlt2x/docker/bmlt.env up --detach",
    reset_bmlt2x_with_states_on: "export MEETING_STATES_ON=true; docker compose -f ../bmlt2x/docker/docker-compose.yml stop bmlt2x; docker compose -f ../bmlt2x/docker/docker-compose.yml up --detach bmlt2x",
    reset_bmlt2x_with_states_off: "docker compose -f ../bmlt2x/docker/docker-compose.yml stop bmlt2x; docker compose -f ../bmlt2x/docker/docker-compose.yml up --detach bmlt2x",
    bmlt3x_auto_geocoding_off: "export AUTO_GEOCODING_ON=false; docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env down; docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env up --detach",
    reset_bmlt3x_with_states_on: "export MEETING_STATES_ON=true; docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env stop bmlt3x; docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env up --detach bmlt3x",
    reset_bmlt3x_with_states_off: "docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env stop bmlt3x; docker compose -f ../bmlt3x/docker/docker-compose.yml --env-file ../bmlt3x/docker/bmlt.env up --detach bmlt3x",

    crouton_page: siteurl_single+croutonpage,
    waitfor: "sh docker/wait-for.sh",
    bmlt2x_login_page: "http://localhost:8000/main_server/index.php",
    bmlt3x_login_page: "http://localhost:8001/main_server/index.php",

    crouton2x: siteurl_single+"/crouton2x.php",
    crouton3x: siteurl_single+"/crouton3x.php"
  },
};
