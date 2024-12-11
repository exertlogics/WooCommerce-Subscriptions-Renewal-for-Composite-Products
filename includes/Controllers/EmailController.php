<?php

namespace WSRCP\Controllers;

class EmailController
{
    public static function process_renewal_email()
    {
        $subscription_id = 297176;
        $subscription = wcs_get_subscription($subscription_id);

        if ( ! $subscription ) {
            error_log("Invalid subscription ID: $subscription_id");
            return;
        }

        $user_id = $subscription->get_user_id();
        $user = get_user_by('id', $user_id);
        $email = $user->user_email;

        if ( ! $email ) {
            error_log("No email found for user ID: $user_id");
            return;
        }

        // replace the domain of the email yopmail.com by usig @ delimiter
        $email_parts = explode('@', $email);
        $username = $email_parts[0];
        // $email = $username . '@yopmail.com';
        $email = 'test-email-ivan@yopmail.com';
        // print_better($email, 'Email');

        $subject = 'Subscription Renewal Reminder';
        $template = WSRCP_PLUGIN_PATH . 'templates/emails/subscription-renewal-reminder.php';

        if ( ! file_exists( $template ) ) {
            error_log("Email template not found at: $template");
            return;
        }

        try {
            ob_start();

            // Pass arguments to the template
            $args = [
                'subscription' => $subscription,
            ];
            extract($args, EXTR_SKIP);

            include $template;
            $message = ob_get_clean();

            if (empty($message)) {
                error_log("Email message generation failed.");
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
                error_log("Failed to send email to: $email");
                print_better("Failed to send email to: $email", 'Error');
            }

            print_better("Email sent to: $email", 'Success');

        } catch (\Exception $e) {
            error_log("Error generating email: " . $e->getMessage());
            print_better($e->getMessage(), 'Error');
        }

        wp_die();
    }
}