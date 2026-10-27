<?php
/**
 * Uninstall MKS Core.
 *
 * Removes the partner role, admin capability and settings when the plugin is deleted
 * (not on deactivate).
 *
 * @package mks-core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/includes/class-roles.php';
require_once dirname( __FILE__ ) . '/includes/class-settings.php';
require_once dirname( __FILE__ ) . '/includes/class-category-badge.php';

remove_role( MKS_Core_Roles::ROLE );

$admin = get_role( 'administrator' );
if ( $admin ) {
	$admin->remove_cap( MKS_Core_Roles::CAPABILITY );
}

delete_option( MKS_Core_Settings::OPTION );
delete_option( MKS_Core_Category_Badge::OPTION );
