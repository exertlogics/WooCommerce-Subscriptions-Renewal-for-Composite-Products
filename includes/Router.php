<?php

namespace WSRCP;

use WSRCP\Controllers\EmailController;
use WSRCP\Controllers\RenewSubscription;
use WSRCP\Traits\RenewalTrait;
use WSRCP\Traits\PaymentsTrait;
use Automattic\WooCommerce\Admin\Overrides\Order;

class Router
{
    use RenewalTrait, PaymentsTrait;

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

        add_action('woocommerce_scheduled_subscription_payment', [ 'WSRCP\Controllers\RenewSubscription', 'woocommerce_scheduled_subscription_payment' ], -1, 1);

        // Add hook to manage the fee to the checkout page or cart page for the order created for the deducted subscription
        add_action('woocommerce_cart_calculate_fees', [ 'WSRCP\Controllers\DeductedSubscription', 'add_fee_to_cart' ], 10, 1);

        // add_action('woocommerce_before_pay_action', [ 'WSRCP\Controllers\DeductedSubscription', 'handle_order_for_deducted_subscription' ], 10, 1);

        add_action('wsrcp_emails_before_ten_days', [$this, 'wsrcp_emails_before_ten_days_function']);
        add_action('wsrcp_deduct_payment_before_seven_days', [$this, 'wsrcp_deduct_payment_before_seven_days_function']);
        add_action('wsrcp_renew_subscriptions', [$this, 'wsrcp_renew_subscriptons_function']);
        

        // Enable error reporting
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ERROR);


    }

    public function wsrcp_emails_before_ten_days_function() {
        // Call the check_for_upcoming_renewals function
        $router = new Router();
        $router->send_renewal_emails_before_ten_days();
    }

    public function wsrcp_deduct_payment_before_seven_days_function() {
        // Call the check_for_upcoming_renewals function
        $router = new Router();
        $router->deduct_payment_before_seven_days();
    }

    public function wsrcp_renew_subscriptons_function() {
        // Call the check_for_upcoming_renewals function
        $router = new Router();
        $router->renew_subscriptions_now();
    }

    public function register_routes()
    {
        $this->register_renewal_route();
        $this->send_renewal_email();
        $this->order_meta();
        $this->subscription_meta();
        $this->deduct_payment();

        // Check the subscriptions which have the next payment date within 10 days
        $this->check_for_upcoming_renewals();

        $this->create_product();

        $this->display_logs();
    }

    public function display_logs()
    {
        if (isset($_GET['display_logs'])) {
            // $logs = wsrcp_get_logs() ?? [];

            $log_file = WSRCP_PLUGIN_PATH . 'wsrcp.log';
            $logs = file_get_contents($log_file) ?? [];
            
            // print_better($logs, 'Logs');
            // wsrcp_die('Logs', 'Logs', 'success');

            // print_r($logs);
            // die();
            
            $logs = explode("\n", $logs);
            if (empty($logs)) {
                echo '<div style="font-family: monospace; color: gray;">No logs available.</div>';
            } else {
                echo '<div style="font-family: monospace; white-space: pre-wrap; background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px;">';
                foreach ($logs as $log) {
                    // Extract components of the log using a regex pattern
                    if (preg_match('/^\[(.*?)\]\s\[(.*?)\]\s(.*?):(\d+)\s(.*)$/', $log, $matches)) {
                        $timestamp = $matches[1];
                        $level = $matches[2];
                        $file = $matches[3];
                        $line = $matches[4];
                        $message = $matches[5];

                        // Determine color based on log level
                        $levelColor = match (strtoupper($level)) {
                            'INFO' => '#61afef',
                            'WARNING' => '#e5c07b',
                            'ERROR' => '#e06c75',
                            'DEBUG' => '#98c379',
                            default => '#d4d4d4',
                        };

                        // Print the formatted log
                        echo "<div style='margin-bottom: 8px;'>";
                        echo "<span style='color: #569cd6;'>[$timestamp]</span> ";
                        echo "<span style='color: $levelColor;'>[$level]</span> ";
                        echo "<span style='color: #9cdcfe;'>{$file}:{$line}</span> ";
                        echo "<span style='color: #dcdcdc;'>{$message}</span>";
                        echo "</div>";
                    } else {
                        // If log format doesn't match, print as is
                        echo "<div style='color: #dcdcdc;'>{$log}</div>";
                    }
                }
                echo '</div>';
            }

            wsrcp_die('Logs', 'Logs', 'success');
        }
    }


    public function create_product()
    {
        if (isset($_GET['create_product'])) {
            $title = "WSRCP Subscription Renewal Product";
            $price = 10;
            $isVisible = false;
            $status = "published";
            $sku = "wsrcp-renewal-product";
            $password = "wsrcp-renewal-product";

            $product = wc_get_product_id_by_sku($sku);

            if ($product) {
                print_better($product, 'Product already exists');
                wsrcp_die('Product already exists', 'Product already exists', 'error');
            }

            $product = new \WC_Product();
            $product->set_name($title);
            $product->set_price($price);
            $product->set_regular_price($price);
            $product->set_sale_price($price);
            $product->set_sku($sku);
            $product->set_manage_stock(false);
            $product->set_stock_quantity(0);
            $product->set_stock_status('instock');
            $product->set_backorders('no');
            $product->set_sold_individually(true);
            $product->set_tax_status('none');
            $product->set_tax_class('');
            $product->set_status($status);
            $product->set_catalog_visibility('hidden');
            $product_id = $product->save();

            // Now, password-protect the product by updating the underlying post
            wp_update_post(array(
                'ID'           => $product_id,
                'post_password' => $password,
            ));


            print_better($product, 'Product Created');

            wsrcp_die('Product created successfully', 'Product created successfully', 'success');
        }
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

        if (isset($_GET['wsrcp_select_items'])) {
            RenewSubscription::selectItems();
            return true;
        }
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

            // die( __FILE__ . ':' . __LINE__ . ' - Function: ' . __FUNCTION__ );
            wsrcp_die('Subscription Meta', 'Subscription Meta', 'success');
        }
    }

    public function order_meta()
    {
        if (isset($_GET['server_time'])) 
        {
            print_better(date('Y-m-d H:i:s'), 'Server Time');

            // // Set the default timezone if needed
            // date_default_timezone_set('Your/Timezone'); // Replace 'Your/Timezone' with your desired timezone (e.g., 'America/New_York')

            // // Get the current server time and format it
            // $formattedTime = date('F j, Y h:i a');

            // // Display the formatted time
            // echo $formattedTime;

            wsrcp_die('Server Time', 'Server Time', 'success');
        }

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

        if (isset($_GET['order'])) 
        {
            print_better($_GET['order'], 'Order');
            // wsrcp_die('Order', 'Order', 'success');

            $order = wc_get_order($_GET['order']);
            $order_meta = $order->get_meta_data();

            print_better($order_meta, 'Order Meta');

            wsrcp_die('Order Meta', 'Order Meta', 'success');

        }

        if (isset($_GET['order_meta'])) 
        {
            print_better($_GET['order_meta'], 'Order Meta');
            wsrcp_die('Order Meta', 'Order Meta', 'success');

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

        if (!isset($_GET['check_for_upcoming_renewals'])) {
            return;
        }

        $renewalable_subscriptions = $this->getRenewableSubscriptions() ?? [];
        print_better($renewalable_subscriptions, 'All Renewable Subscriptions');

        // Get the subscriptions which dont have days_pending >= 7
        $renewalable_subscriptions_emailable = array_filter($renewalable_subscriptions, function($subscription) {
            return $subscription['days_pending'] >= 7;
        });
        print_better($renewalable_subscriptions_emailable, 'Renewable Subscriptions Email-able : Not 7 Days');


        $renewalable_subscriptions_deductable = array_filter($renewalable_subscriptions, function($subscription) {
            return $subscription['days_pending'] < 7;
        });
        print_better($renewalable_subscriptions_deductable, 'Renewable Subscriptions Deductable : Less than 7 Days');

        wsrcp_die('Check for Upcoming Renewals', 'Check for Upcoming Renewals', 'success');

    }

    public function send_renewal_emails_before_ten_days()
    {
        try {
            $renewalable_subscriptions = $this->getRenewableSubscriptions() ?? [];

            // Get the subscriptions which dont have days_pending >= 7 days, but <= 10 days
            $renewalable_subscriptions_emailable = array_filter($renewalable_subscriptions, function($subscription) {
                return $subscription['days_pending'] >= 7;
            });

            // Send Renewal Email to the users of the subscriptions
            foreach ($renewalable_subscriptions_emailable as $subscription) {
                $subscription_id = $subscription['subscription_id'];
                wsrcp_log('Processing renewal email for subscription ID: ' . $subscription_id);
                EmailController::process_renewal_email($subscription_id);
            }

            wsrcp_log('Upcoming Renewals Checked and Renewal Emails Sent');
            
            // exit;
        } catch (\Exception $e) {
            wsrcp_die('Error while checking for upcoming renewals and sending emails. Error: ' . $e->getMessage(), 'Error', 'error');
        }

    }

    public function renew_subscriptions_now()
    {
        try {
            $renewalable_subscriptions = $this->getRenewableSubscriptions() ?? [];

        } catch (\Exception $e) {
            wsrcp_error('Error while renewing subscriptions. Error: ' . $e->getMessage());
        }
    }

    public function deduct_payment_before_seven_days()
    {
        try {
            $renewalable_subscriptions = $this->getRenewableSubscriptions() ?? [];

            // // Get the subscriptions which dont have days_pending >= 7 days, but <= 10 days
            // $renewalable_subscriptions_emailable = array_filter($renewalable_subscriptions, function($subscription) {
            //     return $subscription['days_pending'] >= 7;
            // });

            // // Send Renewal Email to the users of the subscriptions
            // foreach ($renewalable_subscriptions_emailable as $subscription) {
            //     $subscription_id = $subscription['subscription_id'];
            //     wsrcp_log('Processing renewal email for subscription ID: ' . $subscription_id);
            //     EmailController::process_renewal_email($subscription_id);
            // }

            // Get the subscriptions which have days_pending < 7 days
            $renewalable_subscriptions_deductable = array_filter($renewalable_subscriptions, function($subscription) {
                return $subscription['days_pending'] < 7;
            });

            // Deduct Payment for the subscriptions
            foreach ($renewalable_subscriptions_deductable as $subscription) {
                $subscription_id = $subscription['subscription_id'];
                wsrcp_log('Deducting payment for subscription ID: ' . $subscription_id);
                $order = $this->getPayementEarlier($subscription_id);
                // if (!$order) {
                //     wsrcp_die('Error while deducting payment for subscription ID: ' . $subscription_id, 'Error', 'error');
                // }
                // wsrcp_die('Payment deducted for subscription ID: ' . $subscription_id, 'Payment Deducted', 'success');
            }

            wsrcp_log('Payment Deducted for Upcoming Renewals');
            
            // exit;
        } catch (\Exception $e) {
            wsrcp_die('Error while deducting the payment. Error: ' . $e->getMessage(), 'Error', 'error');
        }

    }

    public function deduct_payment()
    {
        try {
            if (isset($_GET['deduct_payment'])) 
            {

                // if (!is_superadmin()) {
                //     wsrcp_die('You are not allowed to access this route');
                // }

                // print_better($_GET['deduct_payment'], 'Deduct Payment for Subscription ID');
                wsrcp_log('Deduct Payment for Subscription ID: ' . $_GET['deduct_payment']);

                if (empty($_GET['deduct_payment']) || !isset($_GET['deduct_payment'])) {
                    // wsrcp_log('REQUIRED Subsription ID! Please provide a subscription ID to deduct payment');
                    wsrcp_die('Please provide a subscription ID to deduct payment', 'Subscription ID is required', 'error');
                }

                if (!is_numeric($_GET['deduct_payment'])) {
                    // wsrcp_log('INVALID Subsription ID! Please provide a valid subscription ID to deduct payment');
                    wsrcp_die('Please provide a valid subscription ID to deduct payment', 'Invalid Subscription ID', 'error');
                }

                $order = $this->getPayementEarlier($_GET['deduct_payment']);
                print_better($order, 'Order');

                if (!$order) {
                    wsrcp_die('Error while deducting payment for subscription ID: ' . $_GET['deduct_payment'], 'Error', 'error');
                }

                print_better($order, 'New Order Created');

                wsrcp_die('Implement the payment deduction logic here', 'Payment Deducted', 'success');

                // EmailController::process_renewal_email($_GET['deduct_payment']);
            }
        } catch (\Exception $e) {
            wsrcp_die('Error while deducting payment for subscription ID: ' . $_GET['deduct_payment'] . '. Error: ' . $e->getMessage(), 'Error', 'error');
        }
    }
}

// add_filter('cron_schedules', 'wsrcp_twice_daily_cron_schedule');
// function wsrcp_twice_daily_cron_schedule($schedules) {
//     $schedules['twice_daily'] = array(
//         'interval' => 12 * HOUR_IN_SECONDS, // 12 hours
//         'display' => __('Twice Daily')
//     );
//     return $schedules;
// }

// add_action('wp', 'wsrcp_schedule_check_for_upcoming_renewals_cron');
// function wsrcp_schedule_check_for_upcoming_renewals_cron() {
//     if (!wp_next_scheduled('wsrcp_check_for_upcoming_renewals')) {
//         wp_schedule_event(time(), 'twice_daily', 'wsrcp_check_for_upcoming_renewals');
//     }
// }

