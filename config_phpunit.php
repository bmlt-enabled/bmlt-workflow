<?php
define('WBW_DEBUG', false);
define('WBW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WBW_PLUGIN_URL', plugin_dir_url(__FILE__));

// get us through the header
if (!defined('ABSPATH')) {
    define('ABSPATH', '99999999999');
}
