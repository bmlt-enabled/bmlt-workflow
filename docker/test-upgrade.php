<?php
// Minimal database upgrade test for Docker container
// Exit codes: 0 = success, 1 = failure

// Load WordPress and plugin
require_once('/var/www/html/wp-config.php');
require_once('/var/www/html/wp-content/plugins/bmlt-workflow/src/BMLTWF_Database.php');

function importTestData() {
    global $wpdb;
    
    // Drop existing tables
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_service_bodies_access");
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_submissions");
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_service_bodies");
    
    // Import SQL files as-is
    $sql_files = glob('/sql-import/*.sql');
    foreach ($sql_files as $sql_file) {
        $sql = file_get_contents($sql_file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^(START TRANSACTION|SET time_zone|COMMIT)/', $statement)) {
                $wpdb->query($statement);
            }
        }
    }
    
    // Set old version - let upgrade handle all the fixes
    update_option('bmltwf_db_version', '0.4.0');
}

try {
    // Import test data first
    importTestData();
    
    $database = new \bmltwf\BMLTWF_Database();
    $test_results = $database->testUpgrade('0.4.0');
    
    if ($test_results['success']) {
        echo "UPGRADE_TEST_PASSED\n";
        exit(0);
    } else {
        echo "UPGRADE_TEST_FAILED: " . implode(', ', $test_results['errors']) . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "UPGRADE_TEST_ERROR: " . $e->getMessage() . "\n";
    exit(1);
}