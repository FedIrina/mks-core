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
						'key'       => 'field_mks_banner_tab',
						'label'     => 'Баннер',
						'type'      => 'tab',
						'placement' => 'top',
					),
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
						'key'       => 'field_mks_timer_tab',
						'label'     => 'Таймер',
						'type'      => 'tab',
						'placement' => 'top',
					),
					array(
						'key'            => 'field_mks_banner_show_from',
						'name'           => 'banner_show_from',
						'label'          => 'Дата и время начала акции',
						'instructions'   => 'С этого момента вместо баннера показывается таймер. Пустое значение — таймер сразу.',
						'type'           => 'date_time_picker',
						'display_format' => 'd.m.Y H:i',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
					),
					array(
						'key'            => 'field_mks_countdown_until',
						'name'           => 'countdown_until',
						'label'          => 'Дата и время окончания акции',
						'instructions'   => 'Заполненное значение включает таймер. После окончания снова показывается баннер.',
						'type'           => 'date_time_picker',
						'display_format' => 'd.m.Y H:i',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
					),
					array(
						'key'           => 'field_mks_countdown_title',
						'name'          => 'countdown_title',
						'label'         => 'Заголовок таймера',
						'type'          => 'text',
						'default_value' => 'До конца акции осталось',
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
	 * Checks whether the banner should be displayed in the header.
	 *
	 * A countdown banner outside its window falls back to the regular banner
	 * if its image or caption was saved earlier.
	 *
	 * @param int $post_id Banner ID.
	 * @return bool
	 */
	public static function is_visible( $post_id ) {
		if ( ! get_field( 'countdown_until', $post_id ) ) {
			return true;
		}

		return self::is_countdown_active( $post_id ) || self::has_banner_content( $post_id );
	}

	/**
	 * Checks whether the countdown is inside its show window.
	 *
	 * @param int $post_id Banner ID.
	 * @return bool
	 */
	public static function is_countdown_active( $post_id ) {
		$start = self::field_timestamp( 'banner_show_from', $post_id );
		$end   = self::field_timestamp( 'countdown_until', $post_id );

		if ( $start && $start > time() ) {
			return false;
		}

		if ( $end && $end <= time() ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the timestamp of a date field in the site timezone.
	 *
	 * @param string $name    Field name.
	 * @param int    $post_id Banner ID.
	 * @return int|false
	 */
	private static function field_timestamp( $name, $post_id ) {
		$value = get_field( $name, $post_id );

		if ( ! $value ) {
			return false;
		}

		$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() );

		return $date ? $date->getTimestamp() : false;
	}

	/**
	 * Checks whether the banner has content to show as a regular banner.
	 *
	 * @param int $post_id Banner ID.
	 * @return bool
	 */
	private static function has_banner_content( $post_id ) {
		return get_post_thumbnail_id( $post_id ) || '' !== trim( get_post_field( 'post_content', $post_id ) );
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
		if ( '' === $value ) {
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
