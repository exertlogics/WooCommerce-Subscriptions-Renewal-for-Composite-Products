<?php

namespace WSRCP;

use WSRCP\Controllers\RenewSubscription;

class Router
{
    public function __construct()
    {
        add_action('template_redirect', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        $this->register_renewal_route();
    }

    public function register_renewal_route()
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (isset($_GET['renew_subscription'])) {
            RenewSubscription::renew_subscription();
            wp_die('Renew Subscription Route');
        }

        // if (strpos($_SERVER['REQUEST_URI'], 'renew_subscription') !== false) {
        //     RenewSubscription::renew_subscription();
        //     wp_die('Renew Subscription Router 2');
        // }
    }
}