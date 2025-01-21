<?php

namespace WSRCP\Traits;

trait PaymentsTrait {
    use ProductTrait;

    protected function getPayementEarlier(int $subscription_id) 
    {
        wsrcp_log('Creating renewal order for subscription ID: ' . $subscription_id);
        
        try {
            // wsrcp_log('In Try Block');
            // Delete all the products with slug wsrcp-renewal-product
            // $products = wc_get_products(array(
            //     'status' => 'any',
            //     'type' => 'simple',
            //     'slug' => 'wsrcp-renewal-product'
            // ));
            // foreach ($products as $product) {
            //     $product->delete(true);
            // }

            // wsrcp_die('Deleted all the products with SKU wsrcp-renewal-product');
            
            $subscription = wcs_get_subscription($subscription_id);
            // wsrcp_log('Subscription Found for ID: ' . $subscription_id);
            
            // Create pending order
            $order = wc_create_order(array(
                'customer_id' => $subscription->get_customer_id(),
                'status' => 'pending',
                'payment_method' => $subscription->get_payment_method(),
                'payment_method_title' => $subscription->get_payment_method_title()
            ));
            // wsrcp_log('Order Created for Subscription ID: ' . $subscription_id . ' Order ID: ' . $order->get_id());

            // Copy items from subscription
            // $items = $subscription->get_items();
            // foreach ($items as $item) {
            //     $product_id = $item->get_product_id();
            //     $order->add_product(wc_get_product($product_id), 1);
            // }

            // Instead of copying the items from the subscription, we will not add any items to the order, as we are only creating the order to trigger the payment

            // Create a custom line item to match the subscription total
            // $order->add_product(wc_get_product($subscription->get_product_id()), 1, array(
            //     'total' => $subscription->get_total(),
            //     'subtotal' => $subscription->get_total(),
            //     'quantity' => 1
            // ));  

            // Create a custom line item to match the subscription total
            // $fake_product = new \WC_Product();
            // $fake_product->set_name('Subscription Renewal');
            // $fake_product->set_id(-1);
            // $fake_product->set_price($subscription->get_total());
            // $fake_product->set_regular_price($subscription->get_total());
            // $fake_product->set_sale_price($subscription->get_total());
            // $fake_product->set_sku('subscription-renewal');
            // $fake_product->set_manage_stock(false);
            // $fake_product->set_stock_quantity(0);
            // $fake_product->set_stock_status('instock');
            // $fake_product->set_backorders('no');
            // $fake_product->set_sold_individually(true);
            // $fake_product->set_tax_status('none');
            // $fake_product->set_tax_class('');
            // $fake_product->set_status('publish');
            // $fake_product->set_catalog_visibility('hidden');
            // $fake_product->save();
            
            // $order->add_product($fake_product, 1);

            // Fetch the existing password-protected product
            // print_better('Subscription Product ID');
            $protected_product = $this->getProduct();
            // wsrcp_log('Protected Product: ' . $protected_product);
            // print_better($protected_product, 'Protected Product');
            // wsrcp_die('Protected Product');
            
            // Add the password-protected product to the order
            $order->add_product($protected_product, 1, array(
                'total' => $subscription->get_total(),
                'subtotal' => $subscription->get_total(),
                'quantity' => 1
            ));
            // wsrcp_log('Product added to order: ' . $order->get_id());

            // Set totals
            $order->set_total($subscription->get_total(), 'total');
            
            // Copy billing & shipping addresses
            $order->set_address($subscription->get_address('billing'), 'billing');
            $order->set_address($subscription->get_address('shipping'), 'shipping');

            // Add subscription relationship
            $order->add_meta_data('_subscription_renewal', $subscription_id);
            $order->add_meta_data('_wsrcp_payment_deducted_for_subscription', $subscription_id);

            // Add subscription meta
            // Meta to keep track of the orders created for the subscription at any point of time
            $subscription->add_meta_data('_wsrcp_payment_deducted_via_order', $order->get_id());
            // Meta to keep track of the order which is currently being processed for renewal
            $subscription->update_meta_data('_wsrcp_force_renewal_in_progress_via_order', $order->get_id());
            // wsrcp_log('Before saving the Subscription');
            $subscription->save();
            
            // Save order
            // wsrcp_log('Before saving the Order');
            $order->save();
            // wsrcp_log('After saving the Order');

            // Trigger payment
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            // wsrcp_log('Available Gateways: ' . print_r($available_gateways));
            $payment_method = $subscription->get_payment_method();
            // wsrcp_log('Payment Method: ' . $payment_method);

            if (isset($available_gateways[$payment_method])) {
                // wsrcp_log('inside the if block, before processing the payment');
                $available_gateways[$payment_method]->process_payment($order->get_id());
                // wsrcp_log('After processing the payment');
            }

            wsrcp_log('Renewal order created for subscription ID: ' . $subscription_id . ' Order ID: ' . $order->get_id());
            
            return $order;

        } catch (\Exception $e) {
            wsrcp_log('Error creating renewal order for subscription ID: ' . $subscription_id . '. Error: ' . $e->getMessage());

            // print_better($e->getMessage(), 'Error creating renewal order for subscription ID: ' . $subscription_id);
            

            return 0;
        }

        // wsrcp_log('Exiting Try-Catch Block');
    }
}