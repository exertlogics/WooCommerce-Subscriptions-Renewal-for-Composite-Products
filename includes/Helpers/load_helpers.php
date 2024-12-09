<?php

// Include all helpers in this file

// fetch print.php WordPress Helpers from github
if (WSRCP_MODE === 'development') {
    $wordpress_print_helpers = file_get_contents('https://raw.githubusercontent.com/imgul/awesome-code-utils/refs/heads/main/php/wordpress/print.php');
    eval($wordpress_print_helpers);
}