<?php
/**
 * Plugin Name: WooCommerce Subscriptions Renewal for Composite Products 🔁
 * Plugin URI: https://ifixwoocommerce.com/solutions/wordpress/woocommerce-subscriptions-renewal-for-composite-products/
 * Description: This plugin allows you to renew the subscription of composite products in WooCommerce Subscriptions via a separate renewal order.
 * Version: 1.0.0
 * Author: Ifix WooCommerce
 * Author URI: https://ifixwoocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 6.5
 * Requires PHP: 7.4
 *
 * @package WSRCP
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WSRCP_PLUGIN_FILE' ) ) {
	define( 'WSRCP_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'WSRCP\Kernel' ) ) {
	include_once dirname( WSRCP_PLUGIN_FILE ) . '/includes/Kernel.php';
}

$kernel = WSRCP\Kernel::instance();