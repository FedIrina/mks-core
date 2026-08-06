<?php
/**
 * Role and capability for API partners.
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and manages the mks_api_partner role.
 */
class MKS_Core_Roles {

	const ROLE          = 'mks_api_partner';
	const CAPABILITY    = 'read_mks_api';

	/**
	 * Adds the partner role with read_mks_api (on plugin activation).
	 */
	public static function activate() {
		add_role(
			self::ROLE,
			'МКС API Partner',
			array(
				self::CAPABILITY => true,
				'read'           => true,
			)
		);

		$role = get_role( self::ROLE );
		if ( $role && ! $role->has_cap( self::CAPABILITY ) ) {
			$role->add_cap( self::CAPABILITY );
		}

		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::CAPABILITY ) ) {
			$admin->add_cap( self::CAPABILITY );
		}
	}

	/**
	 * Intentionally empty: do not remove the role on deactivate
	 * so assigned users keep their role.
	 */
	public static function deactivate() {
	}
}
