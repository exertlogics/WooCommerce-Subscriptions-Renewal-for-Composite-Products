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
        $html .= '<strong>Welcome back ' . $user->first_name . ' 👋!</strong> We are happy to see you back. Please select the composite product and it\'s items to renew your current subscription.';
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

        // Get data from cookies
        $renew_subscription = $_COOKIE['wsrcp_renew_subscription'];
        $subscription_id = $_COOKIE['wsrcp_subscription_id'];
        $user_id = $_COOKIE['wsrcp_user_id'];
        $via = $_COOKIE['wsrcp_via'];
        $callback_url = $_COOKIE['wsrcp_callback_url'];
        $cache = $_COOKIE['wsrcp_cache'];

        // Save the data to the order item
        $custom_data = [
            'renew_subscription' => $renew_subscription,
            'subscription_id' => $subscription_id,
            'user_id' => $user_id,
            'via' => $via,
            'callback_url' => $callback_url,
            'cache' => $cache,
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
        if (isset($cart_item['composite_parent'])) {
            return $item_data;
        }


        if (isset($cart_item['renew_subscription'])) {
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
    
}