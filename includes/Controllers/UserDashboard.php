<?php

namespace WSRCP\Controllers;

class UserDashboard
{
    public static function modify_action_buttons($actions, $subscription, $user_id)
    {
        // remove subscription_renewal_early action
        unset($actions['subscription_renewal_early']);

        // add renew_subscription action
        $actions['renew_subscription'] = [
            'url' => add_query_arg('renew_subscription', 'true'),
            'name' => 'Renew Subscription',
            'action' => 'renew_subscription',
        ];

        // print_r the $actions in logs.txt file in plugins directory
        // create logs.txt file in plugins directory if not exists
        // $log_file = plugin_dir_path(WSRCP_PLUGIN_FILE) . 'logs.txt';
        // if (!file_exists($log_file)) {
        //     file_put_contents($log_file, '');
        // }
        file_put_contents(plugin_dir_path(WSRCP_PLUGIN_FILE) . 'logs.txt', print_r($actions, true));

        

        return $actions;
    }
}