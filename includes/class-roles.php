<?php
/**
 * Роль и право для API-партнёров.
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Регистрирует и обслуживает роль mks_api_partner.
 */
class MKS_Core_Roles {

	const ROLE          = 'mks_api_partner';
	const CAPABILITY    = 'read_mks_api';

	/**
	 * Добавляет роль партнёра с правом read_mks_api (при активации плагина).
	 */
	public static function activate() {
		add_role(
			self::ROLE,
			__( 'МКС API Partner', 'mks-core' ),
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
	 * Намеренно пусто: роль не удаляется при деактивации,
	 * чтобы у назначенных пользователей она сохранилась.
	 */
	public static function deactivate() {
	}
}
