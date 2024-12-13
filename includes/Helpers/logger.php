<?php

$log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';

// Create a log file if it doesn't exist
if (!file_exists($log_file)) {
    $file = fopen($log_file, 'w');
    fclose($file);
}

// Check if the log file is writable, if not, make it writable
if (!is_writable($log_file)) {
    chmod($log_file, 0777);
}

// Function to add log entries
function wsrcp_log($log_entry) {
    $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
    $file = fopen($log_file, 'a');
    fwrite($file, $log_entry . "\n");
    fclose($file);
}