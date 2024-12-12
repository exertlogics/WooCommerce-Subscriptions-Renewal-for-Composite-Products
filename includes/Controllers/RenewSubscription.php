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
}