<?php
/**
 * Plugin Name:       MKS Core
 * Description:       Общий функционал интернет-магазина (REST API остатков и др.).
 * Version:           0.2.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            MKS
 * Text Domain:       mks-core
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MKS_CORE_VERSION', '0.2.0' );
define( 'MKS_CORE_FILE', __FILE__ );
define( 'MKS_CORE_DIR', dirname( __FILE__ ) );

require_once MKS_CORE_DIR . '/includes/class-roles.php';
require_once MKS_CORE_DIR . '/includes/class-settings.php';
require_once MKS_CORE_DIR . '/includes/class-rest-stock.php';

register_activation_hook( __FILE__, 'mks_core_activate' );
register_deactivation_hook( __FILE__, array( 'MKS_Core_Roles', 'deactivate' ) );

/**
 * Plugin activation.
 */
function mks_core_activate() {
	MKS_Core_Roles::activate();
	MKS_Core_Settings::activate();
}

/**
 * Bootstrap after plugins are loaded.
 */
add_action( 'plugins_loaded', 'mks_core_bootstrap' );

/**
 * Loads the plugin when WooCommerce is available.
 */
function mks_core_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'mks_core_woocommerce_missing_notice' );
		return;
	}

	MKS_Core_Settings::init();
	MKS_Core_REST_Stock::init();
}

/**
 * Admin notice when WooCommerce is inactive.
 */
function mks_core_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'MKS Core требует активный плагин WooCommerce.', 'mks-core' );
	echo '</p></div>';
}
