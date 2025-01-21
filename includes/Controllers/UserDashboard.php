<?php

namespace WSRCP\Controllers;

class UserDashboard
{
    public static function modify_action_buttons($actions, $subscription, $user_id)
    {
        unset($actions['subscription_renewal_early']);

        $items = $subscription->get_items();
        $subscription_product = reset($items);
        $subscription_product_id = $subscription_product->get_product_id();

        $forced_order_id = $subscription->get_meta('_wsrcp_force_renewal_in_progress_via_order');
        $forced_order = wc_get_order($forced_order_id) ?? null;

        if ($forced_order) {
            $forced_order_status = $forced_order->get_status();

            if ($forced_order_status === 'failed') {
                // Add notice for failed payment
                $notice = 'Payment failed for this subscription. Please pay again to continue.';
                wc_add_notice($notice, 'error');

                // Ensure notices are displayed
                // add_action('woocommerce_before_my_account', 'wc_print_notices', 10);

                // Force print notices if hook hasn't run yet
                if (!did_action('woocommerce_before_my_account')) {
                    wc_print_notices();
                }

                // Add a 'Pay now' action button
                $actions['renew_subscription'] = [
                    'url' => $forced_order->get_checkout_payment_url(),
                    'name' => __('Pay now', 'woocommerce-subscriptions'),
                    'action' => 'renew_subscription',
                ];
                return $actions;
            }

            if ($forced_order_status === 'completed') {
                $actions['renew_subscription'] = [
                    'url' => get_permalink($subscription_product_id) .
                        '?wsrcp_select_items=true&subscription_id=' . 
                        $subscription->get_id() . 
                        '&user_id=' . $user_id . 
                        '&via=user-dashboard&callback_url=' . urlencode(home_url('/my-account/subscriptions/' . $subscription->get_id() . '/')) . 
                        '&cache=0',
                    'name' => __('Select Items', 'woocommerce-subscriptions'),
                    'action' => 'renew_subscription',
                ];
                return $actions;
            }
        }

        // Default action for renew subscription
        $actions['renew_subscription'] = [
            'url' => get_permalink($subscription_product_id) .
                '?renew_subscription=true&subscription_id=' . 
                $subscription->get_id() . 
                '&user_id=' . $user_id . 
                '&via=user-dashboard&callback_url=' . urlencode(home_url('/my-account/subscriptions/' . $subscription->get_id() . '/')) . 
                '&cache=0',
            'name' => __('Renew now', 'woocommerce-subscriptions'),
            'action' => 'renew_subscription',
        ];

        return $actions;
    }

    // public static function show_subscription_notices()
    // {
    //     // Hook into a point where notices can be shown
    //     add_action('woocommerce_account_my-account_endpoint', function () {
    //         wc_print_notices(); // Print WooCommerce notices on the desired page
    //     });
    // }
}
