<?php

// Only Super Admin Access
if (!function_exists('is_superadmin')) {
    function is_superadmin()
    {
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url());
            return false;
        }

        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles)) {
            wp_redirect(home_url());
            return false;
        }

        return true;
    }
}