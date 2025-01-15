<?php

namespace WSRCP\Traits;

trait PaymentsTrait {
    protected function getPayementEarlier(int $subscription_id) {
        try {
            $subscription = wcs_get_subscription($subscription_id);
            
            // Create pending order
            $order = wc_create_order(array(
                'customer_id' => $subscription->get_customer_id(),
                'status' => 'pending',
                'payment_method' => $subscription->get_payment_method(),
                'payment_method_title' => $subscription->get_payment_method_title()
            ));

            // Copy items from subscription
            $items = $subscription->get_items();
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                $order->add_product(wc_get_product($product_id), 1);
            }

            // Set totals
            $order->set_total($subscription->get_total(), 'total');
            
            // Copy billing & shipping addresses
            $order->set_address($subscription->get_address('billing'), 'billing');
            $order->set_address($subscription->get_address('shipping'), 'shipping');

            // Add subscription relationship
            $order->add_meta_data('_subscription_renewal', $subscription_id);
            $order->add_meta_data('_wsrcp_payment_deducted_for_subscription', $subscription_id);

            // Add subscription meta
            $subscription->add_meta_data('_wsrcp_payment_deducted_via_order', $order->get_id());
            $subscription->save();

            // Save order
            $order->save();

            // Trigger payment
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            $payment_method = $subscription->get_payment_method();

            if (isset($available_gateways[$payment_method])) {
                $available_gateways[$payment_method]->process_payment($order->get_id());
            }

            wsrcp_log('Renewal order created for subscription ID: ' . $subscription_id . ' Order ID: ' . $order->get_id());
            
            return $order;

        } catch (\Exception $e) {
            wsrcp_log('Error creating renewal order for subscription ID: ' . $subscription_id . '. Error: ' . $e->getMessage());

            return false;
        }
    }
}