<?php

namespace WSRCP;

use WSRCP\Controllers\RenewSubscription;

class Router
{
    private RenewSubscription $renew_subscription;
    private array $get;

    public function __construct()
    {
        $this->renew_subscription = new RenewSubscription();
        $this->get = $_GET;

        add_action('template_redirect', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        $this->register_renewal_route();
    }

    public function register_renewal_route()
    {
        if (is_page('renew-subscription')) {
            wp_die('Renew Subscription');
            $this->renew_subscription->renew_subscription();
        }
        
        if (isset($this->get['renew_subscription'])) {
            $this->renew_subscription->renew_subscription();
        }
    }
}