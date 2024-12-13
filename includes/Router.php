<?php

namespace WSRCP;

use WSRCP\Controllers\EmailController;
use WSRCP\Controllers\RenewSubscription;

class Router
{
    public function __construct()
    {
        add_action('template_redirect', [$this, 'register_routes']);
        add_filter('wcs_view_subscription_actions', [ 'WSRCP\Controllers\UserDashboard', 'modify_action_buttons' ], 15, 3);

        // remove woocommerce_add_to_cart_validation filter for can_add_product_to_cart function from class-wc-subscriptions-cart-validator.php
        // remove_filter('woocommerce_add_to_cart_validation', [ 'WC_Subscriptions_Cart_Validator', 'can_add_product_to_cart' ], 999999999);
        // remove_filter( 'woocommerce_add_to_cart_validation', ['WC_Subscriptions_Cart_Validator', 'can_add_product_to_cart'], 1099 );

        // Add the hook to add metedata to order item when adding to cart
        add_action('woocommerce_add_cart_item_data', [ 'WSRCP\Controllers\RenewSubscription', 'add_metadata_to_order_item' ], 10, 3);

        // Hook to view the renewal subscription metadata when viewing the cart details on cart or checkout page
        add_filter('woocommerce_get_item_data', [ 'WSRCP\Controllers\RenewSubscription', 'view_renewal_subscription_metadata' ], 10, 2);
    }

    public function register_routes()
    {
        $this->register_renewal_route();
        $this->send_renewal_email();
    }

    public function register_renewal_route()
    {
        // if (!is_user_logged_in()) {
        //     return;
        // }

        if (isset($_GET['renew_subscription'])) {
            RenewSubscription::save_data_to_cookies();
            return true;
        }

        // if (strpos($_SERVER['REQUEST_URI'], 'renew_subscription') !== false) {
        //     RenewSubscription::renew_subscription();
        //     wp_die('Renew Subscription Router 2');
        // }
    }

    public function send_renewal_email()
    {
        if (isset($_GET['send_renewal_email'])) {

            if (!is_superadmin()) {
                wp_die('You are not allowed to access this route');
            }

            EmailController::process_renewal_email();
        }
    }
}