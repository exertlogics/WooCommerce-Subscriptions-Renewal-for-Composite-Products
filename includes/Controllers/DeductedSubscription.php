<?php

namespace WSRCP\Controllers;

class DeductedSubscription
{
    public static function add_fee_to_cart($cart)
    {
        return $cart;
        
        // Get the regular cart total
        // $cart_total = $cart->get_total( 'edit' );
        // print_better($cart_total, 'Cart total');

        // $cart_subtotal = $cart->get_subtotal();
        // print_better($cart_subtotal, 'Cart subtotal');

        // $cart_recurring_total = $cart->get_recurring_total();
        // print_better($cart_recurring_total, 'Cart recurring total');
        
        // return $cart;
        // Get recurring totals for subscriptions (requires WooCommerce Subscriptions)
        // $recurring_totals = [];
        // if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
        //     $recurring_totals = WC_Subscriptions_Cart::get_recurring_cart_totals( $cart );
        // }
        // print_better($recurring_totals, 'Recurring totals');

        // // Loop through recurring totals and apply logic
        // foreach ( $recurring_totals as $recurring_total ) {
        //     print_better('Recurring total foreach', 'Recurring total foreach');
        //     if ( $recurring_total['total'] > 200 ) {
        //         $cart->add_fee( 'Engraving Fee', 10 );
        //         break; // Add the fee only once if any recurring total exceeds the threshold
        //     }
        // }

        // // Check regular cart total as well
        // if ( $cart_total > 200 ) {
        //     $cart->add_fee( 'Engraving Fee', 10 );
        // }

        // return $cart;
        
        // print_better($cart, 'Cart');
        // wsrcp_log('===================== Cart =====================');
        // wsrcp_log($cart);
        try {
            // wsrcp_die('Adding fee to cart', 'Adding fee to cart', 'info');
            wsrcp_log('add_fee_to_cart');
            wsrcp_log('Adding fee to cart');

            
            $wsrcp_select_items = $_COOKIE['wsrcp_select_items'] ?? null;
            wsrcp_log('Select items: ' . $wsrcp_select_items);
            // wsrcp_die('Select items: ' . $wsrcp_select_items, 'Select items', 'info');
            print_better($wsrcp_select_items, 'Select items');

            // return $cart;
            
            if ($wsrcp_select_items === 'true') {
                print_better('Select items is true', 'Select items');
                $subscription_id = $_COOKIE['wsrcp_subscription_id'] ?? null;
                $user_id = $_COOKIE['wsrcp_user_id'] ?? null;
                $via = $_COOKIE['wsrcp_via'] ?? null;
                $callback_url = $_COOKIE['wsrcp_callback_url'] ?? null;
                $cache = $_COOKIE['wsrcp_cache'] ?? null;

                wsrcp_log('Cookies data in cart:');
                wsrcp_log('Select items: ' . $wsrcp_select_items);
                wsrcp_log('Subscription ID: ' . $subscription_id);
                wsrcp_log('User ID: ' . $user_id);
                wsrcp_log('Via: ' . $via);
                wsrcp_log('Callback URL: ' . $callback_url);
                wsrcp_log('Cache: ' . $cache);

                print_better('Cookies data in cart:', 'Cookies data');
                print_better('Select items: ' . $wsrcp_select_items, 'Select items');
                print_better('Subscription ID: ' . $subscription_id, 'Subscription ID');
                print_better('User ID: ' . $user_id, 'User ID');
                print_better('Via: ' . $via, 'Via');
                print_better('Callback URL: ' . $callback_url, 'Callback URL');
                print_better('Cache: ' . $cache, 'Cache');

                // $subscription_id = $cart->get_meta('_wsrcp_subscription_id');
                $subscription = wcs_get_subscription($subscription_id);
                // print_better($subscription, 'Subscription');
                $subscription_total = $subscription->get_total();
                print_better($subscription_total, 'Subscription total');
                $subscription_subtotal = $subscription->get_subtotal();
                print_better($subscription_subtotal, 'Subscription subtotal');

                // Get the meta _wsrcp_force_renewal_in_progress_via_order from the subscription
                $forced_order_id = $subscription->get_meta('_wsrcp_force_renewal_in_progress_via_order');
                wsrcp_log('Forced order ID: ' . $forced_order_id);
                print_better($forced_order_id, 'Forced order ID');
                if ($forced_order_id) {
                    print_better('Forced order ID is true', 'Forced order ID');
                    $forced_order = wc_get_order($forced_order_id);
                    // print_better($forced_order, 'Forced order');
                    $forced_total = $forced_order->get_total();
                    print_better($forced_total, 'Forced total');
                    $forced_subtotal = $forced_order->get_subtotal();
                    print_better($forced_subtotal, 'Forced subtotal');
                    if (!$forced_order) {
                        print_better('There is no forced order for subscription ID: ' . $subscription_id, 'There is no forced order');
                        wsrcp_log('There is no forced order for subscription ID: ' . $subscription_id, 'error');
                        // return $cart;
                        wsrcp_die('There is no forced order for subscription ID: ' . $subscription_id, 'Error', 'error');
                    }

                    $forced_order_status = $forced_order->get_status();
                    print_better($forced_order_status, 'Forced order status');

                    if ($forced_order_status === 'completed') {
                        print_better('Forced order status is completed', 'Forced order status');
                        $forced_total = $forced_order->get_total();
                        print_better($forced_total, 'Forced total');
                        $forced_currency = $forced_order->get_currency();
                        print_better($forced_currency, 'Forced currency');

                        // If the $total is greater than the $forced_total, then the add the difference to the cart as a fee
                        $cart_total = $cart->get_total();
                        print_better($cart_total, 'Cart total');

                        // print_better($cart, 'Cart');
                        wsrcp_die('Cart total: ' . $cart_total, 'Cart total', 'info');

                        if ($cart_total > $forced_total) {
                            wsrcp_log('Cart total is greater than forced total');
                            print_better('Cart total is greater than forced total', 'Cart total');
                            
                            try {
                                $fee = $cart_total - $forced_total;
                                wsrcp_log('$fee = $cart_total - $forced_total: ' . $fee);
                                print_better($fee, 'Fee');

                                $cart->add_fee(__('Additional Fee', 'woocommerce'), $fee, true);
                            } catch (\Exception $e) {
                                wsrcp_log('Error adding fee to cart: ' . $e->getMessage(), 'error');
                                wsrcp_die('Error adding fee to cart: ' . $e->getMessage(), 'Error adding fee to cart', 'error');
                            }
                            
                            // return $cart;
                            wsrcp_die('Cart total is greater than forced total', 'Cart total is greater than forced total', 'info');
                        }

                        if ($cart_total < $forced_total) {
                            wsrcp_log('Cart total is less than forced total');
                            print_better('Cart total is less than forced total', 'Cart total');
                            $fee = $forced_total - $cart_total;
                            print_better($fee, 'Fee 2');
                            $cart->add_fee(__('Discount Fee', 'woocommerce'), $fee, true);

                            // return $cart;
                            wsrcp_die('Cart total is less than forced total', 'Cart total is less than forced total', 'info');
                        }
                    }
                } else {
                    print_better('Forced order ID is false', 'Forced order ID');
                    // Redirect to the callback URL
                    wsrcp_log('There is no forced order ID for subscription ID: ' . $subscription_id, 'error');
                    // return $cart;
                    wsrcp_die('There is no forced order ID for subscription ID: ' . $subscription_id, 'Error', 'error');
                }

            }

            // return $cart;

            wsrcp_die('Adding fee to cart', 'Adding fee to cart', 'info');
        } catch (\Exception $e) {
            wsrcp_log('Error adding fee to cart: ' . $e->getMessage(), 'error');
            print_better($e->getMessage(), 'Error adding fee to cart');
            wsrcp_die('Error adding fee to cart: ' . $e->getMessage(), 'Error adding fee to cart', 'error');
        }
    }

    public static function handle_order_for_deducted_subscription($order_id)
    {
        wsrcp_log('handle_order_for_deducted_subscription' . $order_id);
        wsrcp_log('Handling order for deducted subscription: ' . $order_id);

        $order = wc_get_order($order_id);
        $total = $order->get_total();
        $currency = $order->get_currency();

        // Get cookies data
        // setcookie('wsrcp_select_items',   $wsrcp_select_items,    $validity_time, '/');
        //     setcookie('wsrcp_subscription_id',      $subscription_id,       $validity_time, '/');
        //     setcookie('wsrcp_user_id',              $user_id,               $validity_time, '/');
        //     setcookie('wsrcp_via',                  $via,                   $validity_time, '/');
        //     setcookie('wsrcp_callback_url',         $callback_url,          $validity_time, '/');
        //     setcookie('wsrcp_cache',                $cache,                 $validity_time, '/');

        $wsrcp_select_items = $_COOKIE['wsrcp_select_items'] ?? null;
        wsrcp_log('Select items: ' . $wsrcp_select_items);

        if ($wsrcp_select_items === 'true') {
            $subscription_id = $_COOKIE['wsrcp_subscription_id'] ?? null;
            $user_id = $_COOKIE['wsrcp_user_id'] ?? null;
            $via = $_COOKIE['wsrcp_via'] ?? null;
            $callback_url = $_COOKIE['wsrcp_callback_url'] ?? null;
            $cache = $_COOKIE['wsrcp_cache'] ?? null;

            wsrcp_log('Cookies data in order:');
            wsrcp_log('Select items: ' . $wsrcp_select_items);
            wsrcp_log('Subscription ID: ' . $subscription_id);
            wsrcp_log('User ID: ' . $user_id);
            wsrcp_log('Via: ' . $via);
            wsrcp_log('Callback URL: ' . $callback_url);
            wsrcp_log('Cache: ' . $cache);

            $subscription_id = $order->get_meta('_wsrcp_subscription_id');
            $subscription = wcs_get_subscription($subscription_id);

            // Get the meta _wsrcp_force_renewal_in_progress_via_order from the subscription
            $forced_order_id = $subscription->get_meta('_wsrcp_force_renewal_in_progress_via_order');
            if ($forced_order_id) {
                $forced_order = wc_get_order($forced_order_id);
                if (!$forced_order) {
                    wsrcp_log('There is no forced order for subscription ID: ' . $subscription_id, 'error');
                    wsrcp_die('There is no forced order for subscription ID: ' . $subscription_id, 'Error', 'error');
                }

                $forced_order_status = $forced_order->get_status();

                if ($forced_order_status === 'completed') {
                    $forced_total = $forced_order->get_total();
                    $forced_currency = $forced_order->get_currency();

                    // If the $total is greater than the $forced_total, then the add the difference to the order as a fee
                    if ($total > $forced_total) {
                        $fee = $total - $forced_total;
                        $order->add_fee(__('Additional Fee', 'woocommerce'), $fee, true);
                    }
                }
            } else {
                // Redirect to the callback URL
                wsrcp_log('There is no forced order ID for subscription ID: ' . $subscription_id, 'error');
                wsrcp_die('There is no forced order ID for subscription ID: ' . $subscription_id, 'Error', 'error');
            }

            // Delete cookies
            // setcookie('wsrcp_select_items', '', time() - 3600, '/');
            // setcookie('wsrcp_subscription_id', '', time() - 3600, '/');
            // setcookie('wsrcp_user_id', '', time() - 3600, '/');
            // setcookie('wsrcp_via', '', time() - 3600, '/');
            // setcookie('wsrcp_callback_url', '', time() - 3600, '/');
            // setcookie('wsrcp_cache', '', time() - 3600, '/');
        }

        wsrcp_die('Order ID: ' . $order_id, 'Order for deducted subscription', 'info');
    }
}