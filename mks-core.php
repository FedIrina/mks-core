<?php
/**
 * Plugin Name:       MKS Core
 * Description:       Общий функционал интернет-магазина (REST API остатков и др.).
 * Version:           0.6.3
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

define( 'MKS_CORE_VERSION', '0.6.3' );
define( 'MKS_CORE_FILE', __FILE__ );
define( 'MKS_CORE_DIR', dirname( __FILE__ ) );

require_once MKS_CORE_DIR . '/includes/class-roles.php';
require_once MKS_CORE_DIR . '/includes/class-settings.php';
require_once MKS_CORE_DIR . '/includes/class-rest-stock.php';
require_once MKS_CORE_DIR . '/includes/class-category-badge.php';
require_once MKS_CORE_DIR . '/includes/class-banner-fields.php';
require_once MKS_CORE_DIR . '/includes/class-countdown.php';

// The timer does not depend on WooCommerce or ACF, so it runs unconditionally.
MKS_Core_Countdown::init();

add_action( 'init', 'mks_core_load_textdomain' );

register_activation_hook( __FILE__, 'mks_core_activate' );
register_deactivation_hook( __FILE__, array( 'MKS_Core_Roles', 'deactivate' ) );

/**
 * Подключает файлы перевода из каталога languages.
 */
function mks_core_load_textdomain() {
	load_plugin_textdomain( 'mks-core', false, dirname( plugin_basename( MKS_CORE_FILE ) ) . '/languages' );
}

/**
 * Активация плагина.
 */
function mks_core_activate() {
	MKS_Core_Roles::activate();
	MKS_Core_Settings::activate();
	MKS_Core_Category_Badge::activate();
}

/**
 * Инициализация после загрузки плагинов.
 */
add_action( 'plugins_loaded', 'mks_core_bootstrap' );

/**
 * Загружает плагин, если доступны нужные зависимости.
 */
function mks_core_bootstrap() {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		MKS_Core_Banner_Fields::init();
	} else {
		add_action( 'admin_notices', 'mks_core_acf_missing_notice' );
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'mks_core_woocommerce_missing_notice' );
		return;
	}

	MKS_Core_Settings::init();
	MKS_Core_REST_Stock::init();
	MKS_Core_Category_Badge::init();
}

/**
 * Уведомление в админке, если ACF неактивен.
 */
function mks_core_acf_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'MKS Core: поля «Баннеры в шапке» требуют активный плагин Advanced Custom Fields.', 'mks-core' );
	echo '</p></div>';
}

/**
 * Уведомление в админке, если WooCommerce неактивен.
 */
function mks_core_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'MKS Core требует активный плагин WooCommerce.', 'mks-core' );
	echo '</p></div>';
}
