<?php

use WSRCP\Helpers\Logger;

// Include all helpers in this file

// fetch print.php WordPress Helpers from github
if (WSRCP_MODE === 'development') {
    require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/print.php';
}

require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/auth.php';

// require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/logger.php';

// Global helper function
function wsrcp_log($message, $level = 'INFO') {
    Logger::getInstance()->log($message, $level);
}

function wsrcp_info($message) {
    Logger::getInstance()->info($message);
}

function wsrcp_error($message) {
    Logger::getInstance()->error($message);
}

function wsrcp_debug($message) {
    Logger::getInstance()->debug($message);
}

function wsrcp_warn($message) {
    Logger::getInstance()->warn($message);
}