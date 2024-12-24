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
        // Check if the product is a subscription renewal
        // if ( isset( $cart_item_data['subscription_renewal'] ) ) {
        //     // Add metadata to the order item
        //     wc_add_order_item_meta( $cart_item_key, 'subscription_renewal', true );
        // }

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
        // $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        // $file = fopen($log_file, 'a');
        // fwrite($file, json_encode($cart_item) . "\n\n");
        // fclose($file);

        // Output is:
        // {"renew_subscription":"true","subscription_id":"297176","user_id":"7202","via":"email","callback_url":null,"cache":"1440","wcsatt_data":{"active_subscription_scheme":"6_month"},"composite_data":{"1726502589":{"product_id":294119,"quantity":1,"attributes":{"attribute_pa_male-shoe-size":"8"},"variation_id":295095,"quantity_min":1,"quantity_max":1,"discount":"","optional":"no","static":"no","title":"Tennis shoes","composite_id":288859,"type":"variable"},"1727950985":{"product_id":294330,"quantity":1,"attributes":{"attribute_pa_shirt-size":"medium"},"variation_id":295105,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shirt","composite_id":288859,"type":"variable"},"1727951084":{"product_id":294337,"quantity":1,"attributes":{"attribute_pa_shorts-size":"medium"},"variation_id":295156,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shorts","composite_id":288859,"type":"variable"},"1726574400":{"product_id":289332,"quantity":1,"attributes":{"attribute_pa_format":"digital-only"},"variation_id":289333,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Tennishead magazine","composite_id":288859,"type":"variable"}},"composite_children":["9b19f620c1d57084529b52b681e1aa80","7fda2db46ea093bc292f25d1b7bed274","6186f7a209dbd0ee767c1f1309893d6d","2496ffbe6e9299c7c3e123d7d5ef4fb5"],"key":"57e5a94e0cbaef4c868b20463b341cc1","product_id":288859,"variation_id":0,"variation":[],"quantity":1,"data_hash":"aafe8dada1b35e5d8c0667a5de721a12","line_tax_data":{"subtotal":{"29":29.9975},"total":{"29":29.9975}},"line_subtotal":119.99,"line_subtotal_tax":30,"line_total":119.99,"line_tax":30,"data":{"scenarios_manager_view":{},"options_count":null}}
        // {"composite_parent":"57e5a94e0cbaef4c868b20463b341cc1","composite_data":{"1726502589":{"product_id":294119,"quantity":1,"attributes":{"attribute_pa_male-shoe-size":"8"},"variation_id":295095,"quantity_min":1,"quantity_max":1,"discount":"","optional":"no","static":"no","title":"Tennis shoes","composite_id":288859,"type":"variable"},"1727950985":{"product_id":294330,"quantity":1,"attributes":{"attribute_pa_shirt-size":"medium"},"variation_id":295105,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shirt","composite_id":288859,"type":"variable"},"1727951084":{"product_id":294337,"quantity":1,"attributes":{"attribute_pa_shorts-size":"medium"},"variation_id":295156,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shorts","composite_id":288859,"type":"variable"},"1726574400":{"product_id":289332,"quantity":1,"attributes":{"attribute_pa_format":"digital-only"},"variation_id":289333,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Tennishead magazine","composite_id":288859,"type":"variable"}},"composite_item":1726502589,"renew_subscription":"true","subscription_id":"297176","user_id":"7202","via":"email","callback_url":null,"cache":"1440","wcsatt_data":{"active_subscription_scheme":"6_month"},"key":"9b19f620c1d57084529b52b681e1aa80","product_id":294119,"variation_id":295095,"variation":{"attribute_pa_male-shoe-size":"8"},"quantity":1,"line_tax_data":{"subtotal":{"29":0},"total":{"29":0}},"line_subtotal":0,"line_subtotal_tax":0,"line_total":0,"line_tax":0,"data":{}}
        // {"composite_parent":"57e5a94e0cbaef4c868b20463b341cc1","composite_data":{"1726502589":{"product_id":294119,"quantity":1,"attributes":{"attribute_pa_male-shoe-size":"8"},"variation_id":295095,"quantity_min":1,"quantity_max":1,"discount":"","optional":"no","static":"no","title":"Tennis shoes","composite_id":288859,"type":"variable"},"1727950985":{"product_id":294330,"quantity":1,"attributes":{"attribute_pa_shirt-size":"medium"},"variation_id":295105,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shirt","composite_id":288859,"type":"variable"},"1727951084":{"product_id":294337,"quantity":1,"attributes":{"attribute_pa_shorts-size":"medium"},"variation_id":295156,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shorts","composite_id":288859,"type":"variable"},"1726574400":{"product_id":289332,"quantity":1,"attributes":{"attribute_pa_format":"digital-only"},"variation_id":289333,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Tennishead magazine","composite_id":288859,"type":"variable"}},"composite_item":1727950985,"renew_subscription":"true","subscription_id":"297176","user_id":"7202","via":"email","callback_url":null,"cache":"1440","wcsatt_data":{"active_subscription_scheme":"6_month"},"key":"7fda2db46ea093bc292f25d1b7bed274","product_id":294330,"variation_id":295105,"variation":{"attribute_pa_shirt-size":"medium"},"quantity":1,"line_tax_data":{"subtotal":{"29":0},"total":{"29":0}},"line_subtotal":0,"line_subtotal_tax":0,"line_total":0,"line_tax":0,"data":{}}
        // {"composite_parent":"57e5a94e0cbaef4c868b20463b341cc1","composite_data":{"1726502589":{"product_id":294119,"quantity":1,"attributes":{"attribute_pa_male-shoe-size":"8"},"variation_id":295095,"quantity_min":1,"quantity_max":1,"discount":"","optional":"no","static":"no","title":"Tennis shoes","composite_id":288859,"type":"variable"},"1727950985":{"product_id":294330,"quantity":1,"attributes":{"attribute_pa_shirt-size":"medium"},"variation_id":295105,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shirt","composite_id":288859,"type":"variable"},"1727951084":{"product_id":294337,"quantity":1,"attributes":{"attribute_pa_shorts-size":"medium"},"variation_id":295156,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shorts","composite_id":288859,"type":"variable"},"1726574400":{"product_id":289332,"quantity":1,"attributes":{"attribute_pa_format":"digital-only"},"variation_id":289333,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Tennishead magazine","composite_id":288859,"type":"variable"}},"composite_item":1727951084,"renew_subscription":"true","subscription_id":"297176","user_id":"7202","via":"email","callback_url":null,"cache":"1440","wcsatt_data":{"active_subscription_scheme":"6_month"},"key":"6186f7a209dbd0ee767c1f1309893d6d","product_id":294337,"variation_id":295156,"variation":{"attribute_pa_shorts-size":"medium"},"quantity":1,"line_tax_data":{"subtotal":{"29":0},"total":{"29":0}},"line_subtotal":0,"line_subtotal_tax":0,"line_total":0,"line_tax":0,"data":{}}
        // {"composite_parent":"57e5a94e0cbaef4c868b20463b341cc1","composite_data":{"1726502589":{"product_id":294119,"quantity":1,"attributes":{"attribute_pa_male-shoe-size":"8"},"variation_id":295095,"quantity_min":1,"quantity_max":1,"discount":"","optional":"no","static":"no","title":"Tennis shoes","composite_id":288859,"type":"variable"},"1727950985":{"product_id":294330,"quantity":1,"attributes":{"attribute_pa_shirt-size":"medium"},"variation_id":295105,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shirt","composite_id":288859,"type":"variable"},"1727951084":{"product_id":294337,"quantity":1,"attributes":{"attribute_pa_shorts-size":"medium"},"variation_id":295156,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Shorts","composite_id":288859,"type":"variable"},"1726574400":{"product_id":289332,"quantity":1,"attributes":{"attribute_pa_format":"digital-only"},"variation_id":289333,"quantity_min":1,"quantity_max":1,"discount":"","optional":"yes","static":"no","title":"Tennishead magazine","composite_id":288859,"type":"variable"}},"composite_item":1726574400,"renew_subscription":"true","subscription_id":"297176","user_id":"7202","via":"email","callback_url":null,"cache":"1440","wcsatt_data":{"active_subscription_scheme":"6_month"},"key":"2496ffbe6e9299c7c3e123d7d5ef4fb5","product_id":289332,"variation_id":289333,"variation":{"attribute_pa_format":"digital-only"},"quantity":1,"line_tax_data":{"subtotal":[],"total":[]},"line_subtotal":10,"line_subtotal_tax":0,"line_total":10,"line_tax":0,"data":{}}

        // If the cart item is the child composite product, then return the item data as is
        // if (isset($cart_item['composite_parent'])) {
        //     return $item_data;
        // }


        if (isset($cart_item['wsrcp_renew_subscription'])) {
            $item_data[] = [
                'key' => 'Renew Subscription',
                'value' => 'Yes',
            ];
        }

        // if (isset($cart_item['subscription_id'])) {
        //     $item_data[] = [
        //         'key' => 'Subscription ID',
        //         'value' => $cart_item['subscription_id'],
        //     ];
        // }

        // if (isset($cart_item['user_id'])) {
        //     $item_data[] = [
        //         'key' => 'User ID',
        //         'value' => $cart_item['user_id'],
        //     ];
        // }

        // if (isset($cart_item['via'])) {
        //     $item_data[] = [
        //         'key' => 'Via',
        //         'value' => $cart_item['via'],
        //     ];
        // }

        // if (isset($cart_item['callback_url'])) {
        //     $item_data[] = [
        //         'key' => 'Callback URL',
        //         'value' => $cart_item['callback_url'],
        //     ];
        // }

        // if (isset($cart_item['cache'])) {
        //     $item_data[] = [
        //         'key' => 'Cache',
        //         'value' => $cart_item['cache'],
        //     ];
        // }

        // try {
        //     wsrcp_log('Item Data: ' . json_encode($item_data));
        // } catch (\Exception $e) {
        //     echo "Error: " . $e->getMessage();
        // }

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

    public static function update_subscription_status_on_creation_bk( $subscription, $order, $renewal_order ) {
        // Get data from cookies
        $renew_subscription = $_COOKIE['wsrcp_renew_subscription'];
        $subscription_id = $_COOKIE['wsrcp_subscription_id'];
        $user_id = $_COOKIE['wsrcp_user_id'];
        $via = $_COOKIE['wsrcp_via'];
        $callback_url = $_COOKIE['wsrcp_callback_url'];
        $cache = $_COOKIE['wsrcp_cache'];

        // Save the data to the subscription
        $subscription->update_meta_data( 'wsrcp_renew_subscription', $renew_subscription );
        $subscription->update_meta_data( 'wsrcp_subscription_id', $subscription_id );
        $subscription->update_meta_data( 'wsrcp_user_id', $user_id );
        $subscription->update_meta_data( 'wsrcp_via', $via );
        $subscription->update_meta_data( 'wsrcp_callback_url', $callback_url );
        $subscription->update_meta_data( 'wsrcp_cache', $cache );

        return $subscription;
    }

    public static function update_subscription_status_on_creation_bk_2($subscription, $order, $cart_item_key)
    {
        // Check if the order has the wsrcp_renew_subscription meta
        $is_renewal = get_post_meta($order->get_id(), 'wsrcp_renew_subscription', true);

        // Log the order meta data
        $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        $file = fopen($log_file, 'a');
        // Add time date to the log + Add the current file name and line number
        fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . "\n");
        fwrite($file, json_encode($order) . "\n\n");
        fclose($file);

        if ($is_renewal) {
            // Set the subscription status to draft
            $subscription->update_status('draft');
        }
    }

    public static function update_subscription_status_on_creation_test( $subscription, $posted_data, $order, $cart ) {
        try {
            // Update subscription status to 'pending'
            $subscription->update_status( 'pending' );
    
            // Update subscription dates
            $new_start_date        = date( 'Y-m-d H:i:s', strtotime( '+10 days' ) ); // Example: 1 day from now
            $new_trial_end_date    = date( 'Y-m-d H:i:s', strtotime( '+20 days' ) ); // Example: 7 days from now
            $new_next_payment_date = date( 'Y-m-d H:i:s', strtotime( '+1 month' ) ); // Example: 1 month from now
            $new_end_date          = date( 'Y-m-d H:i:s', strtotime( '+1 year' ) ); // Example: 1 year from now
    
            $subscription->update_dates(
                array(
                    'start'        => $new_start_date,
                    'trial_end'    => $new_trial_end_date,
                    'next_payment' => $new_next_payment_date,
                    'end'          => $new_end_date,
                )
            );
    
            // Save the subscription to persist changes
            $subscription->save();
    
        } catch ( \Exception $e ) {
            // Log the error for debugging purposes
            error_log( 'Error updating subscription status or dates: ' . $e->getMessage() );
        }
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

    public static function woocommerce_checkout_subscription_created($subscription, $order, $recurring_cart)
    {
        $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        $file = fopen($log_file, 'a');
        // Add time date to the log + Add the current file name and line number
        fwrite($file, date('Y-m-d H:i:s') . ' == woocommerce_checkout_subscription_created ==' . __FILE__ . ':' . __LINE__ . "\n");
        fwrite($file, "Subscription ==> " . json_encode($subscription) . "\n\n");
        fwrite($file, "Order ==> " . json_encode($order) . "\n\n");
        fwrite($file, "Recurring Cart ==> " . json_encode($recurring_cart) . "\n\n");


        $cart_contents = $recurring_cart->get_cart_contents();
        $active_subscription_scheme = '6_month';

        if ( ! empty( $cart_contents ) ) {
            foreach ( $cart_contents as $cart_item_key => $cart_item ) {
                if ( isset( $cart_item['wcsatt_data']['active_subscription_scheme'] ) ) {
                    $active_subscription_scheme = $cart_item['wcsatt_data']['active_subscription_scheme'];
                    fwrite( $file, "Active Subscription Scheme: " . $active_subscription_scheme . "\n" );
                    print_better($active_subscription_scheme, 'Active Subscription Scheme');
                    // Now you have the value, you can use it:
                    // Example:
                    if ($active_subscription_scheme === '6_month') {
                        // Do something specific for 6-month subscriptions
                        fwrite( $file, "It is 6 month plan\n" );
                        print_better('It is 6 month plan', 'Subscription Plan');
                    } else if ($active_subscription_scheme === '1_year') {
                        // Do something specific for 12-month subscriptions
                        fwrite( $file, "It is 1 year plan\n" );
                        print_better('It is 1 year plan', 'Subscription Plan');
                    }
                    break; // Exit the loop after finding the first scheme
                }
            }
        } else {
            fwrite($file, "Cart is empty\n");
            print_better('Cart is empty', 'Cart');
        }
        // die();

        $subscription_meta = $subscription->get_meta_data();
        fwrite($file, "Subscription Meta ==> " . json_encode($subscription_meta) . "\n\n");
        print_better($subscription_meta, 'Subscription Meta');

        // Check if the order has the wsrcp_renew_subscription meta
        // $is_renewal = get_post_meta($order->get_id(), 'wsrcp_renew_subscription', true);
        // $is_renewal = get_post_meta($subscription->get_id(), 'wsrcp_renew_subscription', true);

        $line_items = $subscription->get_items();
        $first_line_item = reset($line_items);

        $is_renewal = $first_line_item->get_meta('wsrcp_renew_subscription');

        print_better($is_renewal, 'Is Renewal');
        fwrite($file, "Is Renewal ==> " . $is_renewal . " ==> Line: " . __LINE__ .  "\n\n");

        // die();

        if ($is_renewal) {
            fwrite($file, "In check is_renewal ==> Line: " . __LINE__ . "\n\n");
            // Set the subscription status to draft
            $subscription->update_status('pending');

            $start_date = $recurring_cart->start_date;
            $trial_end_date = $recurring_cart->trial_end_date;
            $next_payment_date = $recurring_cart->next_payment_date;
            $end_date = $recurring_cart->end_date;

            fwrite($file, "Start Date ==> " . $start_date . " ==> Line: " . __LINE__ .  "\n\n");
            fwrite($file, "Trial End Date ==> " . $trial_end_date . " ==> Line: " . __LINE__ .  "\n\n");
            fwrite($file, "Next Payment Date ==> " . $next_payment_date . " ==> Line: " . __LINE__ .  "\n\n");
            fwrite($file, "End Date ==> " . $end_date . " ==> Line: " . __LINE__ .  "\n\n");

            if ($is_renewal === 'true') {
                fwrite($file, "In 2nd check is_renewal true ==> Line: " . __LINE__ . "\n\n");
                $renewal_subs_id = $first_line_item->get_meta('wsrcp_subscription_id');
                fwrite($file, "Renewal Subscription ID ==> " . $renewal_subs_id . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_id, 'Renewal Subscription ID');

                $renewal_subscription = wcs_get_subscription($renewal_subs_id);
                fwrite($file, "Renewal Subscription ==> " . json_encode($renewal_subscription) . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subscription, 'Renewal Subscription');
                $renewal_subs_start_date = $renewal_subscription->get_date('start');
                fwrite($file, "Renewal Subscription Start Date ==> " . $renewal_subs_start_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
                $renewal_subs_trial_end_date = $renewal_subscription->get_date('trial_end');
                fwrite($file, "Renewal Subscription Trial End Date ==> " . $renewal_subs_trial_end_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_trial_end_date, 'Renewal Subscription Trial End Date');
                $renewal_subs_next_payment_date = $renewal_subscription->get_date('next_payment');
                fwrite($file, "Renewal Subscription Next Payment Date ==> " . $renewal_subs_next_payment_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_next_payment_date, 'Renewal Subscription Next Payment Date');
                $renewal_subs_end_date = $renewal_subscription->get_date('end');
                fwrite($file, "Renewal Subscription End Date ==> " . $renewal_subs_end_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_end_date, 'Renewal Subscription End Date');

                if ($renewal_subs_start_date) {
                    fwrite($file, "In 3rd check renewal_subs_start_date ==> Line: " . __LINE__ . "\n\n");
                    print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
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

                    print_better($start_date, 'Start Date ==> Line ' . __LINE__);
                    print_better($trial_end_date, 'Trial End Date ==> Line ' . __LINE__);
                    print_better($next_payment_date, 'Next Payment Date ==> Line ' . __LINE__);
                }
            }

            fwrite($file, "Before Update Dates, On Line " . __LINE__ . "\n\n");
            print_better($subscription, 'Subscription');

            // Set the start date for the subscription
            // $subscription->set_start_date($start_date);
            // $subscription->set_next_payment_date($next_payment_date);

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
            fwrite($file, "Update Dates ==> " . json_encode($update_dates) . " ==> Line: " . __LINE__ . "\n\n");

            $subscription->update_dates(
                $update_dates
            );
            fwrite($file, "Subscription ==> " . json_encode($subscription) . " ==> Line: " . __LINE__ . "\n\n");
            $subscription_id = $subscription->get_id();
            fwrite($file, "Subscription ID ==> " . $subscription_id . " ==> Line: " . __LINE__ . "\n\n");

            $subscription->save();

            // Update subscription dates
            $new_start_date        = date( 'Y-m-d H:i:s', strtotime( '+2 months' ) ); // Example: 1 day from now
            $new_trial_end_date    = date( 'Y-m-d H:i:s', strtotime( '+3 months' ) ); // Example: 7 days from now
            $new_next_payment_date = date( 'Y-m-d H:i:s', strtotime( '+2 years' ) ); // Example: 1 month from now
            $new_end_date          = date( 'Y-m-d H:i:s', strtotime( '+3 years' ) ); // Example: 1 year from now

            fwrite($file, "new_start_date " . $new_start_date . " ==> Line: " . __LINE__ . "\n\n");
            fwrite($file, "new_trial_end_date " . $new_trial_end_date . " ==> Line: " . __LINE__ . "\n\n");
            fwrite($file, "new_next_payment_date " . $new_next_payment_date . " ==> Line: " . __LINE__ . "\n\n");
            fwrite($file, "new_end_date " . $new_end_date . " ==> Line: " . __LINE__ . "\n\n");
    
            // $subscription->update_dates(
            //     array(
            //         'start'        => $new_start_date,
            //         'trial_end'    => $new_trial_end_date,
            //         'next_payment' => $new_next_payment_date,
            //         'end'          => $new_end_date,
            //     )
            // );
    
            // Save the subscription to persist changes
            // $subscription->save();

            // Write Raw query to update the start date, next payment date, and end date
            // try {
            //     global $wpdb;

            //     $subscription_id = $subscription->get_id();
            //     $table_name = $wpdb->prefix . 'postmeta';

            //     // $start_date = date( 'Y-m-d H:i:s', strtotime( $start_date ) );
            //     // $next_payment_date = date( 'Y-m-d H:i:s', strtotime( $next_payment_date ) );
            //     // $end_date = date( 'Y-m-d H:i:s', strtotime( $end_date ) );

            //     // Update start date
            //     $query = $wpdb->prepare(
            //         "UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s",
            //         $start_date,
            //         $subscription_id,
            //         '_schedule_start'
            //     );
            //     $result = $wpdb->query( $query );
            //     fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     fwrite( $file, "Result ==> " . $result . " ==> Line: " . __LINE__ . "\n\n" );

            //     $resutl_2 = $wpdb->get_results($query);
            //     fwrite( $file, "Result 2 ==> " . json_encode($resutl_2) . " ==> Line: " . __LINE__ . "\n\n" );

            //     // Log the last query error
            //     if ( $wpdb->last_error ) {
            //         fwrite( $file, "Last DB Error ==> " . $wpdb->last_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         // show the last query
            //         fwrite( $file, "Last Query ==> " . $wpdb->last_query . " ==> Line: " . __LINE__ . "\n\n" );
            //     }


            //     // clear the cache for the subscription post


            //     // Update next payment date
            //     $query = $wpdb->prepare(
            //         "UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s",
            //         $next_payment_date,
            //         $subscription_id,
            //         '_schedule_next_payment'
            //     );
            //     $result = $wpdb->query( $query );
            //     fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     fwrite( $file, "Result ==> " . $result . " ==> Line: " . __LINE__ . "\n\n" );

            //     // Log the last query error
            //     if ( $wpdb->last_error ) {
            //         fwrite( $file, "Last DB Error ==> " . $wpdb->last_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         // show the last query
            //         fwrite( $file, "Last Query ==> " . $wpdb->last_query . " ==> Line: " . __LINE__ . "\n\n" );
            //     }


            //     // Update end date
            //     $query = $wpdb->prepare(
            //         "UPDATE $table_name SET meta_value = %s WHERE post_id = $subscription_id AND meta_key = %s",
            //         $end_date,
            //         '_schedule_end'
            //     );
            //     $result = $wpdb->query( $query );
            //     // fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     // Log the result of the query to the log file with the extra information of failed or success
            //     fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     fwrite( $file, "Result ==> " . $result . " ==> Line: " . __LINE__ . "\n\n" );

            //     // Log the last query error
            //     if ( $wpdb->last_error ) {
            //         fwrite( $file, "Last DB Error ==> " . $wpdb->last_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         // show the last query
            //         fwrite( $file, "Last Query ==> " . $wpdb->last_query . " ==> Line: " . __LINE__ . "\n\n" );
            //     }

            //     // Clear cache for subscription post
            //     clean_post_cache( $subscription_id );

            //     fwrite($file, "After Update Dates via RAW SQL, On Line " . __LINE__ . "\n\n");
            // } catch (\Exception $e) {
            //     fwrite($file, "Error: " . $e->getMessage() . " ==> Line: " . __LINE__ . "\n\n");
            // }

            // try {
            //     // Include the WordPress wp-config.php file to import database credentials
            //     require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php' );

            //     // Create a new database connection
            //     $conn = new \mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

            //     // Check the connection
            //     if ( $conn->connect_error ) {
            //         fwrite( $file, "Connection failed: " . $conn->connect_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         fwrite( $file, "Connected successfully ==> Line: " . __LINE__ . "\n\n" );
            //     }

            //     // global $wpdb;
            //     // $table_name = $wpdb->prefix . 'postmeta';

            //     // Update the subscription start date
            //     // $sql = "UPDATE $table_name SET meta_value = '$start_date' WHERE post_id = $subscription_id AND meta_key = '_schedule_start'";
            //     // fwrite( $file, "SQL Query ==> " . $sql . " ==> Line: " . __LINE__ . "\n\n" );
            //     // if ( $conn->query( $sql ) === TRUE ) {
            //     //     fwrite( $file, "Record updated successfully ==> Line: " . __LINE__ . "\n\n" );
            //     // } else {
            //     //     fwrite( $file, "Error updating record: " . $conn->error . " ==> Line: " . __LINE__ . "\n\n" );
            //     // }

            //     // Update the subscription next payment date
            //     // $sql = "UPDATE $table_name SET meta_value = '$next_payment_date' WHERE post_id = $subscription_id AND meta_key = '_schedule_next_payment'";
            //     // if ( $conn->query( $sql ) === TRUE ) {
            //     //     fwrite( $file, "Record updated successfully ==> Line: " . __LINE__ . "\n\n" );
            //     // } else {
            //     //     fwrite( $file, "Error updating record: " . $conn->error . " ==> Line: " . __LINE__ . "\n\n" );
            //     // }

            //     // Update the subscription end date
            //     // $sql = "UPDATE $table_name SET meta_value = '$end_date' WHERE post_id = $subscription_id AND meta_key = '_schedule_end'";
            //     // if ( $conn->query( $sql ) === TRUE ) {
            //     //     fwrite( $file, "Record updated successfully ==> Line: " . __LINE__ . "\n\n" );
            //     // } else {
            //     //     fwrite( $file, "Error updating record: " . $conn->error . " ==> Line: " . __LINE__ . "\n\n" );
            //     // }

            //     // Close the connection
            //     $conn->close();
                
            // } catch (\Exception $e) {
            //     fwrite($file, "Error: " . $e->getMessage() . " ==> Line: " . __LINE__ . "\n\n");
            // }

            fwrite($file, "After Update Dates, On Line " . __LINE__ . "\n\n");
            print_better('After Update Dates, On Line ' . __LINE__);
            fclose($file);

            // die();

            // print_better($subscription, 'Subscription Updated');
        }


        // fclose($file);
    }

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
            // print_better($item->get_meta_data(), 'Item Meta');
            // print_better($item->get_meta('wsrcp_renew_subscription'), 'Renew Subscription');
            // die();
        }

        // print_better($wsrcp_renew_subscription, 'Renew Subscription');
        // print_better($wsrcp_subscription_id, 'Subscription ID');
        // print_better($wsrcp_user_id, 'User ID');
        // print_better($wsrcp_via, 'Via');
        // print_better($wsrcp_callback_url, 'Callback URL');
        // print_better($wsrcp_cache, 'Cache');
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


        fclose($file);
    }

    public static function update_subscription_status_on_creation($subscription, $posted_data, $order, $cart)
    {
        $log_file = 'wp-content/plugins/woocommerce-subscriptions-renewal-for-composite-products/logs.txt';
        $file = fopen($log_file, 'a');
        // Add time date to the log + Add the current file name and line number
        fwrite($file, date('Y-m-d H:i:s') . ' ' . __FILE__ . ':' . __LINE__ . "\n");
        fwrite($file, "Subscription ==> " . json_encode($subscription) . "\n\n");
        fwrite($file, "Posted Data ==> " . json_encode($posted_data) . "\n\n");
        fwrite($file, "Order ==> " . json_encode($order) . "\n\n");
        fwrite($file, "Cart ==> " . json_encode($cart) . "\n\n");
        // fclose($file);

        // print_better($subscription, 'Subscription');
        // print_better($posted_data, 'Posted Data');
        // print_better($order, 'Order');
        // print_better($cart, 'Cart');

        // die();

        $cart_contents = $cart->get_cart_contents();
        $active_subscription_scheme = '6_month';

        if ( ! empty( $cart_contents ) ) {
            foreach ( $cart_contents as $cart_item_key => $cart_item ) {
                if ( isset( $cart_item['wcsatt_data']['active_subscription_scheme'] ) ) {
                    $active_subscription_scheme = $cart_item['wcsatt_data']['active_subscription_scheme'];
                    fwrite( $file, "Active Subscription Scheme: " . $active_subscription_scheme . "\n" );
                    print_better($active_subscription_scheme, 'Active Subscription Scheme');
                    // Now you have the value, you can use it:
                    // Example:
                    if ($active_subscription_scheme === '6_month') {
                        // Do something specific for 6-month subscriptions
                        fwrite( $file, "It is 6 month plan\n" );
                        print_better('It is 6 month plan', 'Subscription Plan');
                    } else if ($active_subscription_scheme === '1_year') {
                        // Do something specific for 12-month subscriptions
                        fwrite( $file, "It is 1 year plan\n" );
                        print_better('It is 1 year plan', 'Subscription Plan');
                    }
                    break; // Exit the loop after finding the first scheme
                }
            }
        } else {
            fwrite($file, "Cart is empty\n");
            print_better('Cart is empty', 'Cart');
        }
        // die();

        $subscription_meta = $subscription->get_meta_data();
        fwrite($file, "Subscription Meta ==> " . json_encode($subscription_meta) . "\n\n");
        print_better($subscription_meta, 'Subscription Meta');

        // Check if the order has the wsrcp_renew_subscription meta
        // $is_renewal = get_post_meta($order->get_id(), 'wsrcp_renew_subscription', true);
        // $is_renewal = get_post_meta($subscription->get_id(), 'wsrcp_renew_subscription', true);

        $line_items = $subscription->get_items();
        $first_line_item = reset($line_items);

        $is_renewal = $first_line_item->get_meta('wsrcp_renew_subscription');

        print_better($is_renewal, 'Is Renewal');
        fwrite($file, "Is Renewal ==> " . $is_renewal . " ==> Line: " . __LINE__ .  "\n\n");

        // die();

        if ($is_renewal) {
            fwrite($file, "In check is_renewal ==> Line: " . __LINE__ . "\n\n");
            // Set the subscription status to draft
            $subscription->update_status('pending');

            $start_date = $cart->start_date;
            $trial_end_date = $cart->trial_end_date;
            $next_payment_date = $cart->next_payment_date;
            $end_date = $cart->end_date;

            fwrite($file, "Start Date ==> " . $start_date . " ==> Line: " . __LINE__ .  "\n\n");
            fwrite($file, "Trial End Date ==> " . $trial_end_date . " ==> Line: " . __LINE__ .  "\n\n");
            fwrite($file, "Next Payment Date ==> " . $next_payment_date . " ==> Line: " . __LINE__ .  "\n\n");
            fwrite($file, "End Date ==> " . $end_date . " ==> Line: " . __LINE__ .  "\n\n");

            if ($is_renewal === 'true') {
                fwrite($file, "In 2nd check is_renewal true ==> Line: " . __LINE__ . "\n\n");
                $renewal_subs_id = $first_line_item->get_meta('wsrcp_subscription_id');
                fwrite($file, "Renewal Subscription ID ==> " . $renewal_subs_id . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_id, 'Renewal Subscription ID');

                $renewal_subscription = wcs_get_subscription($renewal_subs_id);
                fwrite($file, "Renewal Subscription ==> " . json_encode($renewal_subscription) . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subscription, 'Renewal Subscription');
                $renewal_subs_start_date = $renewal_subscription->get_date('start');
                fwrite($file, "Renewal Subscription Start Date ==> " . $renewal_subs_start_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
                $renewal_subs_trial_end_date = $renewal_subscription->get_date('trial_end');
                fwrite($file, "Renewal Subscription Trial End Date ==> " . $renewal_subs_trial_end_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_trial_end_date, 'Renewal Subscription Trial End Date');
                $renewal_subs_next_payment_date = $renewal_subscription->get_date('next_payment');
                fwrite($file, "Renewal Subscription Next Payment Date ==> " . $renewal_subs_next_payment_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_next_payment_date, 'Renewal Subscription Next Payment Date');
                $renewal_subs_end_date = $renewal_subscription->get_date('end');
                fwrite($file, "Renewal Subscription End Date ==> " . $renewal_subs_end_date . " ==> Line: " . __LINE__ .  "\n\n");
                print_better($renewal_subs_end_date, 'Renewal Subscription End Date');

                if ($renewal_subs_start_date) {
                    fwrite($file, "In 3rd check renewal_subs_start_date ==> Line: " . __LINE__ . "\n\n");
                    print_better($renewal_subs_start_date, 'Renewal Subscription Start Date');
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

                    print_better($start_date, 'Start Date ==> Line ' . __LINE__);
                    print_better($trial_end_date, 'Trial End Date ==> Line ' . __LINE__);
                    print_better($next_payment_date, 'Next Payment Date ==> Line ' . __LINE__);
                }
            }

            fwrite($file, "Before Update Dates, On Line " . __LINE__ . "\n\n");
            print_better($subscription, 'Subscription');

            // Set the start date for the subscription
            // $subscription->set_start_date($start_date);
            // $subscription->set_next_payment_date($next_payment_date);

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
            fwrite($file, "Update Dates ==> " . json_encode($update_dates) . " ==> Line: " . __LINE__ . "\n\n");

            $subscription->update_dates(
                $update_dates
            );
            fwrite($file, "Subscription ==> " . json_encode($subscription) . " ==> Line: " . __LINE__ . "\n\n");
            $subscription_id = $subscription->get_id();

            $subscription->save();

            // Update subscription dates
            $new_start_date        = date( 'Y-m-d H:i:s', strtotime( '+2 months' ) ); // Example: 1 day from now
            $new_trial_end_date    = date( 'Y-m-d H:i:s', strtotime( '+3 months' ) ); // Example: 7 days from now
            $new_next_payment_date = date( 'Y-m-d H:i:s', strtotime( '+2 years' ) ); // Example: 1 month from now
            $new_end_date          = date( 'Y-m-d H:i:s', strtotime( '+3 years' ) ); // Example: 1 year from now

            fwrite($file, "new_start_date " . $new_start_date . " ==> Line: " . __LINE__ . "\n\n");
            fwrite($file, "new_trial_end_date " . $new_trial_end_date . " ==> Line: " . __LINE__ . "\n\n");
            fwrite($file, "new_next_payment_date " . $new_next_payment_date . " ==> Line: " . __LINE__ . "\n\n");
            fwrite($file, "new_end_date " . $new_end_date . " ==> Line: " . __LINE__ . "\n\n");
    
            // $subscription->update_dates(
            //     array(
            //         'start'        => $new_start_date,
            //         'trial_end'    => $new_trial_end_date,
            //         'next_payment' => $new_next_payment_date,
            //         'end'          => $new_end_date,
            //     )
            // );
    
            // Save the subscription to persist changes
            // $subscription->save();

            // Write Raw query to update the start date, next payment date, and end date
            // try {
            //     global $wpdb;

            //     $subscription_id = $subscription->get_id();
            //     $table_name = $wpdb->prefix . 'postmeta';

            //     // $start_date = date( 'Y-m-d H:i:s', strtotime( $start_date ) );
            //     // $next_payment_date = date( 'Y-m-d H:i:s', strtotime( $next_payment_date ) );
            //     // $end_date = date( 'Y-m-d H:i:s', strtotime( $end_date ) );

            //     // Update start date
            //     $query = $wpdb->prepare(
            //         "UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s",
            //         $start_date,
            //         $subscription_id,
            //         '_schedule_start'
            //     );
            //     $result = $wpdb->query( $query );
            //     fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     fwrite( $file, "Result ==> " . $result . " ==> Line: " . __LINE__ . "\n\n" );

            //     $resutl_2 = $wpdb->get_results($query);
            //     fwrite( $file, "Result 2 ==> " . json_encode($resutl_2) . " ==> Line: " . __LINE__ . "\n\n" );

            //     // Log the last query error
            //     if ( $wpdb->last_error ) {
            //         fwrite( $file, "Last DB Error ==> " . $wpdb->last_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         // show the last query
            //         fwrite( $file, "Last Query ==> " . $wpdb->last_query . " ==> Line: " . __LINE__ . "\n\n" );
            //     }


            //     // clear the cache for the subscription post


            //     // Update next payment date
            //     $query = $wpdb->prepare(
            //         "UPDATE $table_name SET meta_value = %s WHERE post_id = %d AND meta_key = %s",
            //         $next_payment_date,
            //         $subscription_id,
            //         '_schedule_next_payment'
            //     );
            //     $result = $wpdb->query( $query );
            //     fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     fwrite( $file, "Result ==> " . $result . " ==> Line: " . __LINE__ . "\n\n" );

            //     // Log the last query error
            //     if ( $wpdb->last_error ) {
            //         fwrite( $file, "Last DB Error ==> " . $wpdb->last_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         // show the last query
            //         fwrite( $file, "Last Query ==> " . $wpdb->last_query . " ==> Line: " . __LINE__ . "\n\n" );
            //     }


            //     // Update end date
            //     $query = $wpdb->prepare(
            //         "UPDATE $table_name SET meta_value = %s WHERE post_id = $subscription_id AND meta_key = %s",
            //         $end_date,
            //         '_schedule_end'
            //     );
            //     $result = $wpdb->query( $query );
            //     // fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     // Log the result of the query to the log file with the extra information of failed or success
            //     fwrite( $file, "Query ==> $query | Result: " . ( $result ? 'Success' : 'Failed' ) . " ==> Line: " . __LINE__ . "\n\n" );
            //     fwrite( $file, "Result ==> " . $result . " ==> Line: " . __LINE__ . "\n\n" );

            //     // Log the last query error
            //     if ( $wpdb->last_error ) {
            //         fwrite( $file, "Last DB Error ==> " . $wpdb->last_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         // show the last query
            //         fwrite( $file, "Last Query ==> " . $wpdb->last_query . " ==> Line: " . __LINE__ . "\n\n" );
            //     }

            //     // Clear cache for subscription post
            //     clean_post_cache( $subscription_id );

            //     fwrite($file, "After Update Dates via RAW SQL, On Line " . __LINE__ . "\n\n");
            // } catch (\Exception $e) {
            //     fwrite($file, "Error: " . $e->getMessage() . " ==> Line: " . __LINE__ . "\n\n");
            // }

            // try {
            //     // Include the WordPress wp-config.php file to import database credentials
            //     require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php' );

            //     // Create a new database connection
            //     $conn = new \mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

            //     // Check the connection
            //     if ( $conn->connect_error ) {
            //         fwrite( $file, "Connection failed: " . $conn->connect_error . " ==> Line: " . __LINE__ . "\n\n" );
            //     } else {
            //         fwrite( $file, "Connected successfully ==> Line: " . __LINE__ . "\n\n" );
            //     }

            //     // global $wpdb;
            //     // $table_name = $wpdb->prefix . 'postmeta';

            //     // Update the subscription start date
            //     // $sql = "UPDATE $table_name SET meta_value = '$start_date' WHERE post_id = $subscription_id AND meta_key = '_schedule_start'";
            //     // fwrite( $file, "SQL Query ==> " . $sql . " ==> Line: " . __LINE__ . "\n\n" );
            //     // if ( $conn->query( $sql ) === TRUE ) {
            //     //     fwrite( $file, "Record updated successfully ==> Line: " . __LINE__ . "\n\n" );
            //     // } else {
            //     //     fwrite( $file, "Error updating record: " . $conn->error . " ==> Line: " . __LINE__ . "\n\n" );
            //     // }

            //     // Update the subscription next payment date
            //     // $sql = "UPDATE $table_name SET meta_value = '$next_payment_date' WHERE post_id = $subscription_id AND meta_key = '_schedule_next_payment'";
            //     // if ( $conn->query( $sql ) === TRUE ) {
            //     //     fwrite( $file, "Record updated successfully ==> Line: " . __LINE__ . "\n\n" );
            //     // } else {
            //     //     fwrite( $file, "Error updating record: " . $conn->error . " ==> Line: " . __LINE__ . "\n\n" );
            //     // }

            //     // Update the subscription end date
            //     // $sql = "UPDATE $table_name SET meta_value = '$end_date' WHERE post_id = $subscription_id AND meta_key = '_schedule_end'";
            //     // if ( $conn->query( $sql ) === TRUE ) {
            //     //     fwrite( $file, "Record updated successfully ==> Line: " . __LINE__ . "\n\n" );
            //     // } else {
            //     //     fwrite( $file, "Error updating record: " . $conn->error . " ==> Line: " . __LINE__ . "\n\n" );
            //     // }

            //     // Close the connection
            //     $conn->close();
                
            // } catch (\Exception $e) {
            //     fwrite($file, "Error: " . $e->getMessage() . " ==> Line: " . __LINE__ . "\n\n");
            // }

            fwrite($file, "After Update Dates, On Line " . __LINE__ . "\n\n");
            print_better('After Update Dates, On Line ' . __LINE__);
            fclose($file);

            die();

            // print_better($subscription, 'Subscription Updated');
        }
    }

    // wcs_create_subscription
    public static function wcs_create_subscription($subscription)
    {
        // Get data from cookies
        $renew_subscription = $_COOKIE['wsrcp_renew_subscription'];
        $subscription_id = $_COOKIE['wsrcp_subscription_id'];
        $user_id = $_COOKIE['wsrcp_user_id'];
        $via = $_COOKIE['wsrcp_via'];
        $callback_url = $_COOKIE['wsrcp_callback_url'];
        $cache = $_COOKIE['wsrcp_cache'];

        $subscription_id = $subscription->get_id();

        // Write Raw query to update the start date, next payment date, and end date
        global $wpdb;
        $table_name = $wpdb->prefix . 'postmeta';
        $start_date = date('Y-m-d H:i:s', strtotime('+2 months'));
        $next_payment_date = date('Y-m-d H:i:s', strtotime('+2 years'));
        $end_date = date('Y-m-d H:i:s', strtotime('+3 years'));
        $query = "UPDATE $table_name SET meta_value = '$start_date' WHERE post_id = $subscription_id AND meta_key = '_schedule_start'";
    }
    
}