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

        $actions['renew_subscription'] = [
            'url' => get_permalink($subscription_product_id) .
                '?renew_subscription=true&subscription_id=' . 
                $subscription->get_id() . 
                '&user_id=' . $user_id . 
                '&via=user-dashboard&callback_url=' . urlencode(home_url('/my-account/subscriptions/' . $subscription->get_id() . '/' )) . 
                '&cache=0',
            'name' => __( 'Renew now', 'woocommerce-subscriptions' ),
            'action' => 'renew_subscription',
        ];

        // create logs.txt file in plugins directory if not exists
        // $log_file = plugin_dir_path(WSRCP_PLUGIN_FILE) . 'logs.txt';
        // if (!file_exists($log_file)) {
        //     file_put_contents($log_file, '');
        // }
        // file_put_contents(plugin_dir_path(WSRCP_PLUGIN_FILE) . 'logs.txt', print_r($actions, true));

        return $actions;
    }
}