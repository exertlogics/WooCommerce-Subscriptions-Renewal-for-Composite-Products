<?php
// Ensure no output before setting cookies

namespace WSRCP\Controllers;

class RenewSubscription
{
    public static function save_data_to_cookies()
    {
        $renew_subscription = $_GET['renew_subscription'];
        $subscription_id = $_GET['subscription_id'];
        $user_id = $_GET['user_id'];
        $via = $_GET['via'];
        $callback_url = $_GET['callback_url'];
        $cache = (int) $_GET['cache'];
        $cache = $_GET['cache'] ? $_GET['cache'] : 0;

        // Now save all these values to cookies
        $validity_time = time() + (24 * 60 * 60); // 24 hours
        setcookie('wsrcp_renew_subscription',   $renew_subscription,    $validity_time, '/');
        setcookie('wsrcp_subscription_id',      $subscription_id,       $validity_time, '/');
        setcookie('wsrcp_user_id',              $user_id,               $validity_time, '/');
        setcookie('wsrcp_via',                  $via,                   $validity_time, '/');
        setcookie('wsrcp_callback_url',         $callback_url,          $validity_time, '/');
        setcookie('wsrcp_cache',                $cache,                 $validity_time, '/');

        // Output JavaScript to show an alert
        $user = get_user_by('id', $user_id);
        $html = '';
        $html .= '<div class="alert success">';
        $html .= '<span class="closebtn" onclick="this.parentElement.style.display=\'none\';">&times;</span>';
        $html .= '<strong>Welcome back ' . $user->first_name . '!</strong> We\'re happy to see you back. Please select the products you would like to receive this month below and proceed to checkout.';
        $html .= '</div>';

        echo $html;

        return true;
    }

    public static function allow_duplicate_subscriptions( $validation, $product_id, $quantity ) {

        // return true;
        wc_add_notice(__('This product is out of stock and cannot be added to the cart.', 'woocommerce'), 'error');
        return true;
        // Check if the product is a subscription renewal
        // if ( wcs_is_subscription_product( $product_id ) && isset( WC()->cart ) ) {
        //     foreach ( WC()->cart->get_cart() as $cart_item ) {
        //         if ( isset( $cart_item['subscription_renewal'] ) && $cart_item['product_id'] === $product_id ) {
        //             // Allow adding the same subscription product again
        //             return true;
        //         }
        //     }
        // }
        // return $validation;
    }

    public static function allow_multiple_subscription_renewals( $cart_item_data, $product_id ) {
        // Check if the product has subscription meta
        if ( get_post_meta( $product_id, '_subscription_price', true ) ) {
            // Allow adding multiple instances of subscription products
            unset( $cart_item_data['subscription_renewal'] );
        }
        return $cart_item_data;
    }
    
    
    public static function allow_duplicate_subscription_in_cart( $passed, $product_id, $quantity, $variation_id = null, $cart_item_data = null ) {
        // Check if the product has subscription meta
        if ( get_post_meta( $product_id, '_subscription_price', true ) ) {
            // Skip duplicate checking for subscription products
            return true;
        }
        return $passed;
    }
    
    
    public static function avoid_conflict_on_renewal( $cart_item_data, $product_id ) {
        // Check if the product has subscription meta
        if ( get_post_meta( $product_id, '_subscription_price', true ) ) {
            // Ensure duplicate renewals can be added
            unset( $cart_item_data['subscription_renewal'] );
        }
        return $cart_item_data;
    }
    
    public static function add_metadata_to_order_item( $cart_item_data, $product_id, $variation_id ) {

        $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        $file = fopen($log_file, 'a');
        // Add time, date, file, line number to the log
        fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . "\n");
        fwrite($file, json_encode($cart_item_data) . "\n\n");
        fclose($file);

        // Return if the composite product's children are being added to the cart
        if (isset($cart_item_data['composite_parent'])) {
            return $cart_item_data;
        }

        // Get data from cookies
        $renew_subscription = $_COOKIE['wsrcp_renew_subscription'];
        $subscription_id = $_COOKIE['wsrcp_subscription_id'];
        $user_id = $_COOKIE['wsrcp_user_id'];
        $via = $_COOKIE['wsrcp_via'];
        $callback_url = $_COOKIE['wsrcp_callback_url'];
        $cache = $_COOKIE['wsrcp_cache'];

        // Save the data to the order item
        $custom_data = [
            'wsrcp_renew_subscription' => $renew_subscription,
            'wsrcp_subscription_id' => $subscription_id,
            'wsrcp_user_id' => $user_id,
            'wsrcp_via' => $via,
            'wsrcp_callback_url' => $callback_url,
            'wsrcp_cache' => $cache,
        ];

        $cart_item_data = array_merge( $cart_item_data, $custom_data );

        return $cart_item_data;
    }

    public static function view_renewal_subscription_metadata( $item_data, $cart_item ) 
    {

        if (isset($cart_item['wsrcp_renew_subscription'])) {
            $item_data[] = [
                'key' => 'Renew Subscription',
                'value' => 'Yes',
            ];
        }

        return $item_data;
    }

    public static function add_metadata_to_order_item_checkout( $item, $cart_item_key, $values, $order ) {
        // Get data from cookies
        $renew_subscription = $values['wsrcp_renew_subscription'];
        $subscription_id = $values['wsrcp_subscription_id'];
        $user_id = $values['wsrcp_user_id'];
        $via = $values['wsrcp_via'];
        $callback_url = $values['wsrcp_callback_url'];
        $cache = $values['wsrcp_cache'];

        // Save the data to the order item
        $item->add_meta_data( 'wsrcp_renew_subscription', $renew_subscription );
        $item->add_meta_data( 'wsrcp_subscription_id', $subscription_id );
        $item->add_meta_data( 'wsrcp_user_id', $user_id );
        $item->add_meta_data( 'wsrcp_via', $via );
        $item->add_meta_data( 'wsrcp_callback_url', $callback_url );
        $item->add_meta_data( 'wsrcp_cache', $cache );

        return $item;
    }

    /**
     * Force updating of 'start' and 'next_payment' dates for subscriptions.
     *
     * @param bool          $can_date_be_updated Whether the date can be updated.
     * @param string        $date_type           The type of date being checked.
     * @param WC_Subscription $subscription      The subscription object.
     * @return bool Modified value to allow date updates.
     */
    public static function force_update_start_and_next_payment_dates( $can_date_be_updated, $date_type, $subscription ) {
        if ( in_array( $date_type, array( 'start', 'next_payment' ), true ) ) {
            // Always allow updating 'start' and 'next_payment' dates
            return true;
        }

        // Return the original value for other date types
        return $can_date_be_updated;
    }

    /**
     * Update the new subscription status to 'on-hold' and update the dates
     *
     * @param int $order The order ID
     */
    public static function subscriptions_activated_for_order($order)
    {
        $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        $file = fopen($log_file, 'a');
        // Add time date to the log + Add the current file name and line number
        fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . "\n");
        fwrite($file, "Subscription Activated for Order ==> " . json_encode($order) . "\n\n");
        // fclose($file);

        $order = wc_get_order($order);
        // $order = wc_get_order($_GET['order_meta']);
        $items = $order->get_items();

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
                fwrite($file, "In the if statement\n\n");
                $wsrcp_renew_subscription = $item->get_meta('wsrcp_renew_subscription');
                $wsrcp_subscription_id = $item->get_meta('wsrcp_subscription_id');
                $wsrcp_user_id = $item->get_meta('wsrcp_user_id');
                $wsrcp_via = $item->get_meta('wsrcp_via');
                $wsrcp_callback_url = $item->get_meta('wsrcp_callback_url');
                $wsrcp_cache = $item->get_meta('wsrcp_cache');
            }
            
        }

        fwrite($file, "Renew Subscription ==> " . $wsrcp_renew_subscription . "\n\n");
        fwrite($file, "Subscription ID ==> " . $wsrcp_subscription_id . "\n\n");
        fwrite($file, "User ID ==> " . $wsrcp_user_id . "\n\n");
        fwrite($file, "Via ==> " . $wsrcp_via . "\n\n");
        fwrite($file, "Callback URL ==> " . $wsrcp_callback_url . "\n\n");
        fwrite($file, "Cache ==> " . $wsrcp_cache . "\n\n");
        // die();

        // Find the subscription for the order
        $subscription = reset(wcs_get_subscriptions_for_order($order->get_id()));
        // print_better($subscription, 'Subscription');
        $subscription_id = $subscription->get_id();
        fwrite($file, "Subscription ID 2 ==> " . $subscription_id . "\n\n");

        // get the start date of the subscription
        $start_date = $subscription->get_date('start');
        // print_better($start_date, 'Start Date');
        fwrite($file, "Start Date ==> " . $start_date . " on Line: " . __LINE__ . "\n\n");

        // Next Payment date
        $next_payment_date = $subscription->get_date('next_payment_date');
        // print_better($next_payment_date, 'Next payment Date');
        fwrite($file, "Next Payment Date ==> " . $next_payment_date . " on Line: " . __LINE__ . "\n\n");

        $renewal_subscription = wcs_get_subscription($wsrcp_subscription_id);
        // print_better($renewal_subscription, 'renewal_subscription');
        fwrite($file, "Renewal Subscription ==> " . json_encode($renewal_subscription) . " on Line: " . __LINE__ . "\n\n");

        $renewal_subs_items = $subscription->get_items();
        // fwrite($file, "Subscription Meta ==> " . json_encode($renewal_subs_items) . "\n\n");
        // print_better($renewal_subs_items, 'Renewal Subscription Meta');

        $renewal_subs_item = reset($renewal_subs_items);
        // print_better($renewal_subs_item, 'Renewal Subscription Item Primary');

        // Get the meta data named _wcsatt_scheme
        $active_subscription_scheme = $renewal_subs_item->get_meta('_wcsatt_scheme');
        // print_better($active_subscription_scheme, 'Renewal Subscription Item Primary Meta');
        fwrite($file, "Active Subscription Scheme ==> " . $active_subscription_scheme . " on Line: " . __LINE__ . "\n\n");

        // die();

        $renewal_subs_start_date = $renewal_subscription->get_date('start');
        fwrite($file, "Renewal Subscription Start Date ==> " . $renewal_subs_start_date . " ==> Line: " . __LINE__ .  "\n\n");
        // print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
        $renewal_subs_trial_end_date = $renewal_subscription->get_date('trial_end');
        fwrite($file, "Renewal Subscription Trial End Date ==> " . $renewal_subs_trial_end_date . " ==> Line: " . __LINE__ .  "\n\n");
        // print_better($renewal_subs_trial_end_date, 'Renewal Subscription Trial End Date');
        $renewal_subs_next_payment_date = $renewal_subscription->get_date('next_payment');
        fwrite($file, "Renewal Subscription Next Payment Date ==> " . $renewal_subs_next_payment_date . " ==> Line: " . __LINE__ .  "\n\n");
        // print_better($renewal_subs_next_payment_date, 'Renewal Subscription Next Payment Date');
        $renewal_subs_end_date = $renewal_subscription->get_date('end');
        fwrite($file, "Renewal Subscription End Date ==> " . $renewal_subs_end_date . " ==> Line: " . __LINE__ .  "\n\n");
        // print_better($renewal_subs_end_date, 'Renewal Subscription End Date');





        $active_subscription_scheme = '6_month';

        if ( ! empty( $cart_contents ) ) {
            foreach ( $cart_contents as $cart_item_key => $cart_item ) {
                if ( isset( $cart_item['wcsatt_data']['active_subscription_scheme'] ) ) {
                    $active_subscription_scheme = $cart_item['wcsatt_data']['active_subscription_scheme'];
                    fwrite( $file, "Active Subscription Scheme: " . $active_subscription_scheme . "\n" );
                    // print_better($active_subscription_scheme, 'Active Subscription Scheme');
                    // Now you have the value, you can use it:
                    // Example:
                    if ($active_subscription_scheme === '6_month') {
                        // Do something specific for 6-month subscriptions
                        fwrite( $file, "It is 6 month plan\n" );
                        // print_better('It is 6 month plan', 'Subscription Plan');
                    } else if ($active_subscription_scheme === '1_year') {
                        // Do something specific for 12-month subscriptions
                        fwrite( $file, "It is 1 year plan\n" );
                        // print_better('It is 1 year plan', 'Subscription Plan');
                    }
                    break; // Exit the loop after finding the first scheme
                }
            }
        } else {
            fwrite($file, "Cart is empty\n");
            // print_better('Cart is empty', 'Cart');
        }




        if ($renewal_subs_start_date) {
            fwrite($file, "In 3rd check renewal_subs_start_date ==> Line: " . __LINE__ . "\n\n");
            // print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
            $start_date = $renewal_subs_next_payment_date;
            // $end_date = $renewal_subs_end_date;
            $end_date = date( 'Y-m-d H:i:s', strtotime( '+3 years' ) );
            // $next_payment_date = date('Y-m-d H:i:s', strtotime($start_date . ' + ' . $cart->next_payment_date));
            // if $active_subscription_scheme === '6_month' then add 6 months to the start date
            // if $active_subscription_scheme === '1_year' then add 1 year to the start date
            $next_payment_date = $renewal_subs_next_payment_date;
            if ($active_subscription_scheme === '6_month') {
                $next_payment_date = date('Y-m-d H:i:s', strtotime($start_date . ' + 6 months'));
            } else if ($active_subscription_scheme === '1_year') {
                $next_payment_date = date('Y-m-d H:i:s', strtotime($start_date . ' + 1 year'));
            }
            // $trial_end_date = $renewal_subs_trial_end_date;
            // $trial_end_date = date( 'Y-m-d H:i:s', strtotime( '+3 days' ) );
            // $trial_end_date will be the $start_date + 3 days
            $trial_end_date = date('Y-m-d H:i:s', strtotime($start_date . ' + 3 days'));

            fwrite($file, "Start Date ==> " . $start_date . " ==> Line: " . __LINE__ . "\n\n");
            fwrite($file, "Trial End Date ==> " . $trial_end_date . " ==> Line: " . __LINE__ .  "\n\n");
            fwrite($file, "Next Payment Date ==> " . $next_payment_date . " ==> Line: " . __LINE__ .  "\n\n");

            // print_better($start_date, 'Start Date ==> Line ' . __LINE__);
            // print_better($trial_end_date, 'Trial End Date ==> Line ' . __LINE__);
            // print_better($next_payment_date, 'Next Payment Date ==> Line ' . __LINE__);
        }

        // // Add 1 month to the start date
        // $new_start_date = date('Y-m-d H:i:s', strtotime($start_date . ' +1 month'));
        // print_better($new_start_date, 'New Start Date');

        // // Update the start date of the subscription
        // $subscription->update_dates([
        //     'start' => $new_start_date
        // ]);

        $update_dates = array(
            'start'        => $start_date,
            'trial_end'    => $trial_end_date,
            'next_payment' => $next_payment_date,
            'end'          => $end_date,

            // convert the dates to date time objects
            // 'start'        => wc_string_to_datetime($start_date),
            // 'trial_end'    => wc_string_to_datetime($trial_end_date),
            // 'next_payment' => wc_string_to_datetime($next_payment_date),
            // 'end'          => wc_string_to_datetime($end_date),
        );
        // fwrite($file, "Update Dates ==> " . json_encode($update_dates) . " ==> Line: " . __LINE__ . "\n\n");

        $subscription->update_dates(
            $update_dates
        );

        $subscription->update_status('on-hold');
        $subscription_status = $subscription->get_status();
        fwrite($file, "Subscription Status ==> " . $subscription_status . " ==> Line: " . __LINE__ . "\n\n");

        // fwrite($file, "Subscription ==> " . json_encode($subscription) . " ==> Line: " . __LINE__ . "\n\n");
        $subscription_id = $subscription->get_id();

        // $subscription->save();

        // Update subscription dates
        // $new_start_date        = date( 'Y-m-d H:i:s', strtotime( '+2 months' ) ); // Example: 1 day from now
        // $new_trial_end_date    = date( 'Y-m-d H:i:s', strtotime( '+3 months' ) ); // Example: 7 days from now
        // $new_next_payment_date = date( 'Y-m-d H:i:s', strtotime( '+2 years' ) ); // Example: 1 month from now
        // $new_end_date          = date( 'Y-m-d H:i:s', strtotime( '+3 years' ) ); // Example: 1 year from now

        $new_start_date        = $subscription->get_date('start');
        $new_trial_end_date    = $subscription->get_date('trial_end');
        $new_next_payment_date = $subscription->get_date('next_payment');
        $new_end_date          = $subscription->get_date('end');

        fwrite($file, "new_start_date " . $new_start_date . " ==> Line: " . __LINE__ . "\n\n");
        fwrite($file, "new_trial_end_date " . $new_trial_end_date . " ==> Line: " . __LINE__ . "\n\n");
        fwrite($file, "new_next_payment_date " . $new_next_payment_date . " ==> Line: " . __LINE__ . "\n\n");
        fwrite($file, "new_end_date " . $new_end_date . " ==> Line: " . __LINE__ . "\n\n");


        // Add the $subscription_id to the $renewal_subscription meta data for future reference
        $renewal_subscription->update_meta_data('_wsrcp_new_subscription_id', $subscription_id);
        $renewal_subscription->save();

        fwrite($file, "Meta Data for Renewal Subscription ==> " . $renewal_subscription . " is added as _wsrcp_new_subscription_id ==>" . $subscription_id . " ==> Line: " . __LINE__ . "\n\n");


        fclose($file);
    }

    // public static function woocommerce_scheduled_subscription_payment($subscription_id)
    // {
    //     $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
    //     $file = fopen($log_file, 'a');
    //     // Add time date to the log + Add the current file name and line number
    //     fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . " in " . __FUNCTION__ . "\n");
    //     fwrite($file, "Scheduled Subscription Renewal for ==> #" . $subscription_id . "\n\n");
    //     fclose($file);
    // }
    public static function woocommerce_scheduled_subscription_payment($subscription_id)
    {
        $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        $file = fopen($log_file, 'a');
        
        // Add time date to the log + Add the current file name and line number
        fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . " in " . __FUNCTION__ . "\n");
        fwrite($file, "Scheduled Subscription Renewal for ==> #" . $subscription_id . "\n");

        // Retrieve the meta value for '_wsrcp_new_subscription_id'
        $new_subscription_id = get_post_meta($subscription_id, '_wsrcp_new_subscription_id', true);

        if (!empty($new_subscription_id)) {
            fwrite($file, "Meta '_wsrcp_new_subscription_id' => " . $new_subscription_id . "\n");

            // Load the subscriptions
            $old_subscription = wcs_get_subscription($subscription_id);
            $new_subscription = wcs_get_subscription($new_subscription_id);

            if ($old_subscription && $new_subscription) {
                // Set the old subscription to on-hold
                $old_subscription->update_status('on-hold');
                fwrite($file, "Old subscription #{$subscription_id} status changed to 'on-hold'.\n");

                // Set the new subscription to active
                $new_subscription->update_status('active');
                fwrite($file, "New subscription #{$new_subscription_id} status changed to 'active'.\n");

                // Update the subscription dates
                $old_subscription->update_dates(
                    [
                        'next_payment' => null,
                        'end'          => null,
                    ]
                );

                // Cancel the scheduled payment for the old subscription
                WC()->queue()->cancel_all(
                    'woocommerce_scheduled_subscription_payment',
                    ['subscription_id' => $subscription_id],
                    'woocommerce-subscriptions'
                );
                fwrite($file, "Scheduled payment for old subscription #{$subscription_id} canceled.\n");

            
            } else {
                fwrite($file, "Failed to load one or both subscriptions.\n");
            }
        } else {
            fwrite($file, "Meta '_wsrcp_new_subscription_id' is not set or empty.\n");
        }

        fwrite($file, "\n");
        fclose($file);
    }

    
}