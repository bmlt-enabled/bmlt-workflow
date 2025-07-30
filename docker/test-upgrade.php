<?php
// Minimal database upgrade test for Docker container
// Exit codes: 0 = success, 1 = failure

// Load WordPress and plugin
require_once('/var/www/html/wp-config.php');
require_once('/var/www/html/wp-content/plugins/bmlt-workflow/src/BMLTWF_Database.php');

function importTestData() {
    global $wpdb;
    
    // Disable foreign key checks
    $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop existing tables
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_correspondence");
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_service_bodies_access");
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_submissions");
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_service_bodies");
    $wpdb->query("DROP TABLE IF EXISTS wp_bmltwf_debug_log");
    
    // Re-enable foreign key checks
    $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
    
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
    
    // Verify tables exist before testing upgrade
    global $wpdb;
    $table_count = 0;
    $tables = ['wp_bmltwf_service_bodies', 'wp_bmltwf_submissions', 'wp_bmltwf_service_bodies_access'];
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $table_count++;
        }
    }
    
    if ($table_count < 3) {
        echo "UPGRADE_TEST_ERROR: Required tables not found after import (found $table_count/3)\n";
        exit(1);
    }
    
    $test_results = $database->testUpgrade('0.4.0');
    
    // Reset database state for next run
    update_option('bmltwf_db_version', '0.4.0');
    
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