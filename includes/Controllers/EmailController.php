<?php

namespace WSRCP\Controllers;

class EmailController
{
    public static function process_renewal_email($subscription_id)
    {
        // $subscription_id = 297176;
        $subscription = wcs_get_subscription($subscription_id);

        if ( ! $subscription ) {
            wsrcp_error("Invalid subscription ID: $subscription_id");
            return;
        }

        $user_id = $subscription->get_user_id();
        $user = get_user_by('id', $user_id);
        $email = $user->user_email;

        if ( ! $email ) {
            wsrcp_error("No email found for user ID: $user_id");
            return;
        }

        $items = $subscription->get_items();
        $subscription_product = reset($items);
        $subscription_product_id = $subscription_product->get_product_id();

        $action_url = get_permalink($subscription_product_id) .
            '?renew_subscription=true&subscription_id=' . 
            $subscription->get_id() . 
            '&user_id=' . $user_id . 
            '&via=email'.
            '&cache=1440'; // 24 hours * 60 minutes

        // replace the domain of the email yopmail.com by usig @ delimiter
        $email_parts = explode('@', $email);
        $username = $email_parts[0];
        // $email = $username . '@yopmail.com';
        // $email = 'test-email-ivan@yopmail.com';
        // $email = 'fricrausaquobau-1295@yopmail.com';
        // print_better($email, 'Email');

        $subject = 'Subscription Renewal Reminder';
        $template = WSRCP_PLUGIN_PATH . 'templates/emails/subscription-renewal-reminder.php';

        if ( ! file_exists( $template ) ) {
            wsrcp_error("Email template not found at: $template");
            return;
        }

        try {
            ob_start();

            // Pass arguments to the template
            $args = [
                'subscription' => $subscription,
                'user' => $user,
                'uaction_urlrl' => $action_url,
            ];
            extract($args, EXTR_SKIP);

            include $template;
            $message = ob_get_clean();

            if (empty($message)) {
                wsrcp_error("Email message generation failed.");
                return;
            }

            // print_better($message, 'Email Message');
            // die();

            // Set HTML headers
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: Your Business <no-reply@tennishead.com>',
            ];

            // Send the email
            $sent = wp_mail($email, $subject, $message, $headers);

            if ( ! $sent ) {
                wsrcp_error("Failed to send email to: $email");
                // print_better("Failed to send email to: $email", 'Error');
            }

            // print_better("Email sent to: $email", 'Success');
            wsrcp_log("Subscription renewal email sent to: $email for subscription ID: $subscription_id");

        } catch (\Exception $e) {
            wsrcp_error("Error generating email: " . $e->getMessage());
            // print_better($e->getMessage(), 'Error');
        }

        // wp_die();
    }
}