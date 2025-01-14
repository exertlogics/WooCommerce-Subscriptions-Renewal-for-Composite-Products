<?php

namespace WSRCP\Traits;

trait RenewalTrait {
    protected function getRenewableSubscriptions(int $days_pending = 10) {
        // Get all the subscriptions
        $subscriptions = wcs_get_subscriptions(array(
            'status' => 'active',
            'limit' => -1,
        ));

        $renewalable_subscriptions = null;

        foreach ($subscriptions as $subscription) {
            $next_payment_date = $subscription->get_date('next_payment');

            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            $next_payment_date = new \DateTime($next_payment_date);
            $next_payment_date->setTime(0, 0, 0);

            $interval = $today->diff($next_payment_date);
            $days = $interval->format('%R%a');

            if ($days <= $days_pending) {
                $renewalable_subscriptions[] = [
                    'subscription_id' => $subscription->get_id(),
                    'next_payment_date' => $next_payment_date,
                    'days_pending' => $days,
                ];
            }
        }

        return $renewalable_subscriptions;
    }
}