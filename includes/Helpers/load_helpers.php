<?php

// Include all helpers in this file

// fetch print.php WordPress Helpers from github
if (WSRCP_MODE === 'development') {
    require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/print.php';
}

require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/auth.php';

// require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/logger.php';