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
        // $this->enqueue_scripts();
    }

    public function define_constants() {
        define( 'WSRCP_VERSION', '1.0.0' );
        // define( 'WSRCP_PLUGIN_FILE', WSRCP_PLUGIN_FILE );
        define( 'WSRCP_PLUGIN_PATH', plugin_dir_path( WSRCP_PLUGIN_FILE ) );
        define( 'WSRCP_PLUGIN_URL', plugin_dir_url( WSRCP_PLUGIN_FILE ) );
        define( 'WSRCP_MODE', 'development' );
    }

    public function includes() {
        require_once WSRCP_PLUGIN_PATH . 'vendor/autoload.php';
        // require_once WSRCP_PLUGIN_PATH . 'includes/Helpers/load_helpers.php';

        
    }
    
    public function init_hooks() {
        // add_filter( 'wcs_view_subscription_actions', [ 'WSRCP\Controllers\UserDashboard', 'modify_action_buttons' ]);
        
        // enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // add_filter( 'woocommerce_add_to_cart_validation', 'allow_duplicate_subscriptions', 10, 6 );
        // add_filter( 'woocommerce_add_to_cart_validation', 'WSRCP\Controllers\RenewSubscription::allow_duplicate_subscriptions', 10, 6 );
        // add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'allow_duplicate_subscriptions' ], 10, 3 );
    }

    public function bootstrap() {
        $router = new Router();
    }

    public function enqueue_scripts() {

        $version = time();
        if ( ! is_account_page() && ! is_admin() ) {
            wp_enqueue_style( 'wsrcp-frontend', WSRCP_PLUGIN_URL . 'assets/css/frontend.css', [], $version );
            wp_enqueue_script( 'wsrcp-frontend', WSRCP_PLUGIN_URL . 'assets/js/frontend.js', [], $version, true );
        }

        // if ( is_admin() ) {
        //     wp_enqueue_style( 'wsrcp-admin', WSRCP_PLUGIN_URL . 'assets/css/admin.css', [], $version );
        //     wp_enqueue_script( 'wsrcp-admin', WSRCP_PLUGIN_URL . 'assets/js/admin.js', [], $version, true );
        // }

        // if ( is_account_page() ) {
        //     wp_enqueue_style( 'wsrcp-user-dashboard', WSRCP_PLUGIN_URL . 'assets/css/user-dashboard.css', [], $version );
        //     wp_enqueue_script( 'wsrcp-user-dashboard', WSRCP_PLUGIN_URL . 'assets/js/user-dashboard.js', [], $version, true );
        // }
    }
}