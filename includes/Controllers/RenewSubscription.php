<?php

namespace WSRCP\Controllers;

class RenewSubscription
{
    public static function renew_subscription()
    {
        print_r('Renew Subscription Controller');
        return true;

        $order_id = $_GET['order_id'];
        $order = wc_get_order($order_id);

        if ($order->get_meta('renewed')) {
            return;
        }

        $renewal_order = wc_create_order([
            'customer_id' => $order->get_customer_id(),
            'status' => 'pending',
        ]);

        $renewal_order->add_product($order->get_items(), $order->get_items());
        $renewal_order->calculate_totals();

        $order->update_meta_data('renewed', true);
        $order->save();
    }
}