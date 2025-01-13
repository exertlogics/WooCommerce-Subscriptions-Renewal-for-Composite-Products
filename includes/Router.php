<?php

namespace WSRCP;

use Automattic\WooCommerce\Admin\Overrides\Order;
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

        // Hook to access the checkout process
        add_action('woocommerce_checkout_create_order_line_item', [ 'WSRCP\Controllers\RenewSubscription', 'add_metadata_to_order_item_checkout' ], 10, 4);

        add_filter( 'woocommerce_subscription_can_date_be_updated', [ 'WSRCP\Controllers\RenewSubscription', 'force_update_start_and_next_payment_dates'], 10, 3 );

        // do_action( 'subscriptions_activated_for_order', $order_id );
        add_action('subscriptions_activated_for_order', [ 'WSRCP\Controllers\RenewSubscription', 'subscriptions_activated_for_order' ], 10, 1);

        add_action('woocommerce_scheduled_subscription_payment', [ 'WSRCP\Controllers\RenewSubscription', 'woocommerce_scheduled_subscription_payment' ], 10, -1);

    }

    public function register_routes()
    {
        $this->register_renewal_route();
        $this->send_renewal_email();
        $this->order_meta();
        $this->subscription_meta();

        // Check the subscriptions which have the next payment date within 10 days
        $this->check_for_upcoming_renewals();
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

            EmailController::process_renewal_email($_GET['send_renewal_email']);
        }
    }

    public function subscription_meta()
    {
        if (isset($_GET['subscription_meta'])) 
        {
            $subscription_id = $_GET['subscription_meta'];
            print_better($subscription_id, 'Subscription ID');
            // die( __FILE__ . ':' . __LINE__ . ' - Function: ' . __FUNCTION__ );

            $subscription = wcs_get_subscription($subscription_id);
            // print_better($subscription, 'Subscription');
            // die( __FILE__ . ':' . __LINE__ . ' - Function: ' . __FUNCTION__ );

            $subscription_meta = $subscription->get_meta_data();
            print_better($subscription_meta, 'Subscription Meta');

            die( __FILE__ . ':' . __LINE__ . ' - Function: ' . __FUNCTION__ );
        }
    }

    public function order_meta()
    {
        if (isset($_GET['subscription_renew'])) 
        {
            $subscription_id = $_GET['subscription_renew'];
            do_action('woocommerce_scheduled_subscription_payment', $subscription_id);

            print_better('Subscription Renewed', 'Subscription Renewed');

            die( __FILE__ . ':' . __LINE__ . ' - Function: ' . __FUNCTION__ );
        }
        
        // Handle: ?subscription_update=297517&next_payment_date=2025-01-18
        if (isset($_GET['subscription_update'])) 
        {
            $subscription_id = $_GET['subscription_update'];
            $subscription = wcs_get_subscription($subscription_id);

            // $subscription->update_status('on-hold');
            // $subscription_status = $subscription->get_status();
            // print_better($subscription_status, 'Subscription Status');

            $next_payment_date = $_GET['next_payment_date'];
            if (empty($next_payment_date) || $next_payment_date === '0000-00-00' || !isset($next_payment_date)) {
                $next_payment_date = date('Y-m-d');
            }
            $next_payment_date = date('Y-m-d H:i:s', strtotime($next_payment_date));
            print_better($next_payment_date, 'Next Payment Date');
            // die();

            try {
                $subscription->update_dates(
                    array(
                        'next_payment' => $next_payment_date,
                        // 'next_payment' => date('Y-m-d H:i:s', strtotime('+1 day')),
                        'trial_end' => date('Y-m-d H:i:s', strtotime($next_payment_date . ' - 16 days')),
                        'start' => date('Y-m-d H:i:s', strtotime($next_payment_date . ' - 20 days')),
                    )
                );
            } catch (\Exception $e) {
                print_better($e->getMessage(), 'Error');
            }

            print_better('Subscription Updated', 'Subscription Updated');

            $subscription_next_payment_date = $subscription->get_date('next_payment');
            print_better($subscription_next_payment_date, 'Subscription Next Payment Date');

            die( __FILE__ . ':' . __LINE__ . ' - Function: ' . __FUNCTION__ );
        }

        if (isset($_GET['order_meta'])) 
        {
            // die( __FILE__ . ':' . __LINE__ . ' - Function: ' . __FUNCTION__ );

            $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
            $file = fopen($log_file, 'a');
            // Add time date to the log + Add the current file name and line number
            fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . "\n");
            fwrite($file, "Order ID ==> " . $_GET['order_meta'] . "\n\n");
            fclose($file);

            // die("died with logging");

            $order = wc_get_order($_GET['order_meta']);
            $items = $order->get_items();

            // Find the subscription for the order
            $subscription = reset(wcs_get_subscriptions_for_order($order->get_id()));
            // print_better($subscription, 'Subscription');
            $subscription_id = $subscription->get_id();
            print_better($subscription_id, 'Subscription ID');

            $meta = [];
            $wsrcp_renew_subscription = false;
            $wsrcp_subscription_id = '';
            $wsrcp_user_id = '';
            $wsrcp_via = '';
            $wsrcp_callback_url = '';
            $wsrcp_cache = '';

            foreach ($items as $item) {
                $meta[] = $item->get_meta_data();

                if ($item->get_meta('wsrcp_renew_subscription'))
                {
                    // print_better('In the if statement');
                    $wsrcp_renew_subscription = $item->get_meta('wsrcp_renew_subscription');
                    $wsrcp_subscription_id = $item->get_meta('wsrcp_subscription_id');
                    $wsrcp_user_id = $item->get_meta('wsrcp_user_id');
                    $wsrcp_via = $item->get_meta('wsrcp_via');
                    $wsrcp_callback_url = $item->get_meta('wsrcp_callback_url');
                    $wsrcp_cache = $item->get_meta('wsrcp_cache');
                }
                // print_better($item->get_meta_data(), 'Item Meta');
                // print_better($item->get_meta('wsrcp_renew_subscription'), 'Renew Subscription');
                // die();
            }

            print_better($wsrcp_renew_subscription, 'Renew Subscription');
            print_better($wsrcp_subscription_id, 'Subscription ID');
            print_better($wsrcp_user_id, 'User ID');
            print_better($wsrcp_via, 'Via');
            print_better($wsrcp_callback_url, 'Callback URL');
            print_better($wsrcp_cache, 'Cache');

            // print_better($subscription, "Subscription");
            // print_better($meta, "Meta");
            // die();

            // get the start date of the subscription
            $start_date = $subscription->get_date('start');
            print_better($start_date, 'Start Date');

            // Next Payment date
            $next_payment_date = $subscription->get_date('next_payment_date');
            print_better($next_payment_date, 'Next payment Date');

            $renewal_subscription = wcs_get_subscription($wsrcp_subscription_id);
            // print_better($renewal_subscription, 'renewal_subscription');

            $renewal_subs_items = $subscription->get_items();
            // fwrite($file, "Subscription Meta ==> " . json_encode($renewal_subs_items) . "\n\n");
            // print_better($renewal_subs_items, 'Renewal Subscription Meta');

            $renewal_subs_item = reset($renewal_subs_items);
            // print_better($renewal_subs_item, 'Renewal Subscription Item Primary');

            // Get the meta data named _wcsatt_scheme
            $active_subscription_scheme = $renewal_subs_item->get_meta('_wcsatt_scheme');
            print_better($active_subscription_scheme, 'Renewal Subscription Item Primary Meta');

            // die();

            $renewal_subs_start_date = $renewal_subscription->get_date('start');
            // fwrite($file, "Renewal Subscription Start Date ==> " . $renewal_subs_start_date . " ==> Line: " . __LINE__ .  "\n\n");
            print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
            $renewal_subs_trial_end_date = $renewal_subscription->get_date('trial_end');
            // fwrite($file, "Renewal Subscription Trial End Date ==> " . $renewal_subs_trial_end_date . " ==> Line: " . __LINE__ .  "\n\n");
            print_better($renewal_subs_trial_end_date, 'Renewal Subscription Trial End Date');
            $renewal_subs_next_payment_date = $renewal_subscription->get_date('next_payment');
            // fwrite($file, "Renewal Subscription Next Payment Date ==> " . $renewal_subs_next_payment_date . " ==> Line: " . __LINE__ .  "\n\n");
            print_better($renewal_subs_next_payment_date, 'Renewal Subscription Next Payment Date');
            $renewal_subs_end_date = $renewal_subscription->get_date('end');
            // fwrite($file, "Renewal Subscription End Date ==> " . $renewal_subs_end_date . " ==> Line: " . __LINE__ .  "\n\n");
            print_better($renewal_subs_end_date, 'Renewal Subscription End Date');





            $active_subscription_scheme = '6_month';

            if ( ! empty( $cart_contents ) ) {
                foreach ( $cart_contents as $cart_item_key => $cart_item ) {
                    if ( isset( $cart_item['wcsatt_data']['active_subscription_scheme'] ) ) {
                        $active_subscription_scheme = $cart_item['wcsatt_data']['active_subscription_scheme'];
                        // fwrite( $file, "Active Subscription Scheme: " . $active_subscription_scheme . "\n" );
                        print_better($active_subscription_scheme, 'Active Subscription Scheme');
                        // Now you have the value, you can use it:
                        // Example:
                        if ($active_subscription_scheme === '6_month') {
                            // Do something specific for 6-month subscriptions
                            // fwrite( $file, "It is 6 month plan\n" );
                            print_better('It is 6 month plan', 'Subscription Plan');
                        } else if ($active_subscription_scheme === '1_year') {
                            // Do something specific for 12-month subscriptions
                            // fwrite( $file, "It is 1 year plan\n" );
                            print_better('It is 1 year plan', 'Subscription Plan');
                        }
                        break; // Exit the loop after finding the first scheme
                    }
                }
            } else {
                // fwrite($file, "Cart is empty\n");
                print_better('Cart is empty', 'Cart');
            }




            if ($renewal_subs_start_date) {
                
                print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
                $start_date = $renewal_subs_next_payment_date;
                
                $end_date = date( 'Y-m-d H:i:s', strtotime( '+3 years' ) );
                
                $next_payment_date = $renewal_subs_next_payment_date;
                if ($active_subscription_scheme === '6_month') {
                    $next_payment_date = date('Y-m-d H:i:s', strtotime($start_date . ' + 6 months'));
                } else if ($active_subscription_scheme === '1_year') {
                    $next_payment_date = date('Y-m-d H:i:s', strtotime($start_date . ' + 1 year'));
                }
                
                $trial_end_date = date('Y-m-d H:i:s', strtotime($start_date . ' + 3 days'));

                print_better($start_date, 'Start Date ==> Line ' . __LINE__);
                print_better($trial_end_date, 'Trial End Date ==> Line ' . __LINE__);
                print_better($next_payment_date, 'Next Payment Date ==> Line ' . __LINE__);
            }

            // $update_dates = array(
            //     'start'        => $start_date,
            //     'trial_end'    => $trial_end_date,
            //     'next_payment' => $next_payment_date,
            //     'end'          => $end_date,
            // );
            $update_dates = array(
                // next payment date is today
                'next_payment' => date('Y-m-d H:i:s'),
            );
            // fwrite($file, "Update Dates ==> " . json_encode($update_dates) . " ==> Line: " . __LINE__ . "\n\n");

            $subscription->update_dates(
                $update_dates
            );
            // fwrite($file, "Subscription ==> " . json_encode($subscription) . " ==> Line: " . __LINE__ . "\n\n");
            $subscription_id = $subscription->get_id();

            // Update the status of the subscription to draft
            $subscription->update_status('on-hold'); // This is not working
            $subscription_status = $subscription->get_status();
            // fwrite($file, "Subscription Status ==> " . $subscription_status . " ==> Line: " . __LINE__ . "\n\n");
            print_better($subscription_status, 'Subscription Status');

            // Update subscription dates
            $new_start_date        = date( 'Y-m-d H:i:s', strtotime( '+2 months' ) ); // Example: 1 day from now
            $new_trial_end_date    = date( 'Y-m-d H:i:s', strtotime( '+3 months' ) ); // Example: 7 days from now
            $new_next_payment_date = date( 'Y-m-d H:i:s', strtotime( '+2 years' ) ); // Example: 1 month from now
            $new_end_date          = date( 'Y-m-d H:i:s', strtotime( '+3 years' ) ); // Example: 1 year from now

            // Get the start date of the subscription
            $start_date = $subscription->get_date('start');
            print_better($start_date, 'Updated Start Date');

            die();

            // $subscription_meta = $subscription->get_meta_data();
            $subscription_meta = [];
            foreach ($subscription as $sub) {
                $subscription_meta[] = $sub->get_meta_data();
            }
            // print_better($subscription_meta, 'Subscription Meta');

            // die(json_encode($meta));
            print_better($meta, 'Order Meta');
            die();
        }
    }

    public function check_for_upcoming_renewals()
    {
        // if (!is_superadmin()) {
        //     return;
        // }

        if (!isset($_GET['check_for_upcoming_renewals'])) {
            return;
        }

        $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        $file = fopen($log_file, 'a');
        // Add time date to the log + Add the current file name and line number
        fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . " in function: " . __FUNCTION__ . "\n");

        // Get all the subscriptions
        $subscriptions = wcs_get_subscriptions(array(
            'status' => 'active',
            'limit' => -1,
        ));

        // print_better($subscriptions, 'Subscriptions');
        // die();

        foreach ($subscriptions as $subscription) {
            // print_better($subscription, 'Subscription');
            // die();

            $next_payment_date = $subscription->get_date('next_payment');
            // fwrite($file, "Subscription ID ==> " . $subscription->get_id() . " ==> Next Payment Date ==> " . $next_payment_date . "\n");
            // print_better($next_payment_date, 'Next Payment Date');
            // die();

            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            $next_payment_date = new \DateTime($next_payment_date);
            $next_payment_date->setTime(0, 0, 0);

            $interval = $today->diff($next_payment_date);
            $days = $interval->format('%R%a');

            // fwrite($file, "Subscription ID ==> " . $subscription->get_id() . " ==> Days ==> " . $days . "\n\n");
            // print_better($days, 'Days');

            // print_better(
            //     [
            //         'Next Payment Date' => $next_payment_date,
            //         'Days Pending' => $days,
            //     ],
            //     'Subscription ID: ' . $subscription->get_id()
            // );

            // die();

            if ($days <= 10) {
                print_better(
                    [
                        'Next Payment Date' => $next_payment_date,
                        'Days Pending' => $days,
                    ],
                    'Subscription ID: ' . $subscription->get_id()
                );
                // print_better($subscription, 'Subscription');
                // die();

                // $order_id = $subscription->get_parent_id();
                // $order = wc_get_order($order_id);
                // // print_better($order, 'Order');
                // // die();

                // $user_id = $order->get_user_id();
                // // print_better($user_id, 'User ID');
                // // die();

                // $subscription_id = $subscription->get_id();
                // // print_better($subscription_id, 'Subscription ID');
                // // die();

                // $renewal_subscription = wcs_get_subscription($subscription_id);
                // // print_better($renewal_subscription, 'Renewal Subscription');
                // // die();

                // $renewal_subs_items = $renewal_subscription->get_items();
                // // print_better($renewal_subs_items, 'Renewal Subscription Items');
                // // die();

                // $renewal_subs_item = reset($renewal_subs_items);
                // // print_better($renewal_subs_item, 'Renewal Subscription Item');
                // // die();

                // $active_subscription_scheme = $renewal_subs_item->get_meta('_wcsatt_scheme');
                // // print_better($active_subscription_scheme, 'Active Subscription Scheme');
                // // die();

                // $renewal_subs_start_date = $renewal_subscription->get_date('start');
                // // print
                // // _better($renewal_subs_start_date, 'Renewal Subscription Start Date');
                // // die();

            }

        }

        fclose($file);

        die();
    }
}