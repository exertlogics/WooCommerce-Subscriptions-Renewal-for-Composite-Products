<?php

namespace WSRCP;

class Kernel {
    static $instance;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Kernel constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        $this->bootstrap();
    }

    public function define_constants() {
        define( 'WSRCP_VERSION', '1.0.0' );
        define( 'WSRCP_PLUGIN_FILE', WSRCP_PLUGIN_FILE );
        define( 'WSRCP_PLUGIN_PATH', plugin_dir_path( WSRCP_PLUGIN_FILE ) );
        define( 'WSRCP_PLUGIN_URL', plugin_dir_url( WSRCP_PLUGIN_FILE ) );
        define( 'WSRCP_MODE', 'development' );
    }

    public function includes() {
        require_once WSRCP_PLUGIN_PATH . 'vendor/autoload.php';
        // require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/load_helpers.php';
    }

    public function init_hooks() {
        // add_action( 'woocommerce_subscription_after_actions', '' );
        // add_filter( 'wcs_view_subscription_actions', 'WSRCP\Controllers\UserDashboard::modify_action_buttons' );
        add_filter( 'wcs_view_subscription_actions', [ 'WSRCP\Controllers\UserDashboard', 'modify_action_buttons' ]);
    }

    public function bootstrap() {
        $router = new Router();
    }
}