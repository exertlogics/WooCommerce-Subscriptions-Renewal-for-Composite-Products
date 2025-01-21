<?php

namespace WSRCP\Traits;

trait RenewalTrait {
    protected function getRenewableSubscriptions(int $days_pending = 10) 
    {
        // wsrcp_die('In getRenewableSubscriptions');
        try {
            // Get all the subscriptions
            // $subscriptions = wcs_get_subscriptions(array(
            //     'status' => 'on-hold',
            //     'limit' => -1,
            // ));

            $subscriptions_active = wcs_get_subscriptions(array(
                'status' => ['active'], // Fetch only subscriptions with 'active' status
                'limit'  => -1, // No limit on the number of subscriptions returned
            ));
            
            // Filter out subscriptions with 'on-hold' status (if any) - This is extra protection
            $subscriptions = array_filter($subscriptions_active, function($subscription) {
                return $subscription->get_status() !== 'on-hold';
            });

            // print_better($subscriptions, 'Subscriptions');
            // wsrcp_die('Subscriptions');

            $renewalable_subscriptions = null;

            foreach ($subscriptions as $subscription) {
                $next_payment_date = $subscription->get_date('next_payment');
                // print_better($next_payment_date, 'Next Payment Date: before');

                $today = new \DateTime();
                $today->setTime(0, 0, 0); // Uncaught Exception: Failed to parse time string (0) at position 0 (0): Unexpected character in
                // $today = new \DateTime('now');
                // print_better($today, 'Today Date');
                $next_payment_date = new \DateTime($next_payment_date);
                $next_payment_date->setTime(0, 0, 0);
                // $next_payment_date = date_create($next_payment_date);
                // print_better($next_payment_date, 'Next Payment Date for Subscription ID: ' . $subscription->get_id());

                // if (!$next_payment_date) {
                //     // wsrcp_error('Invalid next payment date for subscription ID: ' . $subscription->get_id());
                //     // wsrcp_error('Next Payment Date: ' . $next_payment_date);
                //     continue;
                // }

                $interval = $today->diff($next_payment_date);
                $days = $interval->format('%R%a');

                // wsrcp_die($days, 'Days');

                if ($days <= $days_pending) {
                    $renewalable_subscriptions[] = [
                        'subscription_id' => $subscription->get_id(),
                        'next_payment_date' => $next_payment_date,
                        'days_pending' => $days,
                    ];
                }
            }

            return $renewalable_subscriptions;
        } catch (\Exception $e) {
            wsrcp_error('Error getting renewable subscriptions: ' . $e->getMessage());
            return null;
        }
    }
}