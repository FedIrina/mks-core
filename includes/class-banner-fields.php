<?php
/**
 * Header banner fields.
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the header banner field group in code.
 *
 * Field keys are kept identical to the group previously created in the admin,
 * so existing meta values stay linked to their field definitions.
 */
class MKS_Core_Banner_Fields {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register' ) );
		add_filter( 'acf/validate_value/key=field_mks_countdown_until', array( __CLASS__, 'validate_until' ), 10, 4 );
	}

	/**
	 * Declares the field group.
	 */
	public static function register() {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_64be48d1df9e0',
				'title'                 => 'Баннеры в шапке',
				'fields'                => array(
					array(
						'key'                => 'field_64be48d221877',
						'name'               => 'banner_position',
						'label'              => 'Позиция баннера на странице',
						'type'               => 'radio',
						'choices'            => array(
							'up'   => 'вверху',
							'down' => 'внизу',
						),
						'default_value'      => 'up',
						'return_format'      => 'value',
						'layout'             => 'horizontal',
						'relevanssi_exclude' => 1,
					),
					array(
						'key'           => 'field_683d9eece3690',
						'name'          => 'banner_back_color',
						'label'         => 'Цвет фона',
						'type'          => 'color_picker',
						'return_format' => 'string',
					),
					array(
						'key'           => 'field_683da59422ae6',
						'name'          => 'banner_back_image',
						'label'         => 'Изображение фона',
						'type'          => 'image',
						'return_format' => 'url',
						'preview_size'  => 'medium',
						'library'       => 'all',
					),
					array(
						'key'           => 'field_mks_countdown_type',
						'name'          => 'countdown_type',
						'label'         => 'Тип содержимого области',
						'type'          => 'radio',
						'choices'       => array(
							'banner'    => 'Баннер',
							'countdown' => 'Таймер',
						),
						'default_value' => 'banner',
						'return_format' => 'value',
						'layout'        => 'horizontal',
					),
					array(
						'key'               => 'field_mks_countdown_until',
						'name'              => 'countdown_until',
						'label'             => 'Дата и время окончания акции',
						'type'              => 'date_time_picker',
						'display_format'    => 'd.m.Y H:i',
						'return_format'     => 'Y-m-d H:i:s',
						'first_day'         => 1,
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_mks_countdown_type',
									'operator' => '==',
									'value'    => 'countdown',
								),
							),
						),
					),
					array(
						'key'               => 'field_mks_countdown_title',
						'name'              => 'countdown_title',
						'label'             => 'Заголовок таймера',
						'type'              => 'text',
						'default_value'     => 'До конца акции осталось',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_mks_countdown_type',
									'operator' => '==',
									'value'    => 'countdown',
								),
							),
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'banner',
						),
					),
				),
				'position'              => 'normal',
				'style'                 => 'seamless',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'show_in_rest'          => 0,
			)
		);
	}

	/**
	 * Limits the countdown end date to 99 days ahead.
	 *
	 * @param bool|string $valid Validation status.
	 * @param mixed       $value Submitted value.
	 * @param array       $field Field array.
	 * @param string      $input Input name.
	 * @return bool|string
	 */
	public static function validate_until( $valid, $value, $field, $input ) {
		$type = isset( $_POST['acf']['field_mks_countdown_type'] ) ? $_POST['acf']['field_mks_countdown_type'] : '';

		if ( 'countdown' !== $type || '' === $value ) {
			return $valid;
		}

		$until = DateTime::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() );

		if ( ! $until ) {
			return 'Укажите корректную дату окончания.';
		}

		if ( $until->getTimestamp() > time() + 99 * DAY_IN_SECONDS ) {
			return 'Дата не может быть дальше 99 дней от текущего момента.';
		}

		return $valid;
	}
}
