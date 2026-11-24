<?php
/**
 * Поля баннеров в шапке.
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Регистрирует группу полей баннеров в шапке кодом.
 *
 * Ключи полей совпадают с группой, ранее созданной в админке,
 * чтобы существующие значения метаданных остались привязаны к своим полям.
 */
class MKS_Core_Banner_Fields {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register' ) );
		add_filter( 'acf/validate_value/key=field_mks_countdown_until', array( __CLASS__, 'validate_until' ), 10, 4 );
	}

	/**
	 * Объявляет группу полей.
	 */
	public static function register() {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_64be48d1df9e0',
				'title'                 => __( 'Баннеры в шапке', 'mks-core' ),
				'fields'                => array(
					array(
						'key'       => 'field_mks_banner_tab',
						'label'     => __( 'Баннер', 'mks-core' ),
						'type'      => 'tab',
						'placement' => 'top',
					),
					array(
						'key'                => 'field_64be48d221877',
						'name'               => 'banner_position',
						'label'              => __( 'Позиция баннера на странице', 'mks-core' ),
						'type'               => 'radio',
						'choices'            => array(
							'up'   => __( 'вверху', 'mks-core' ),
							'down' => __( 'внизу', 'mks-core' ),
						),
						'default_value'      => 'up',
						'return_format'      => 'value',
						'layout'             => 'horizontal',
						'relevanssi_exclude' => 1,
					),
					array(
						'key'           => 'field_683d9eece3690',
						'name'          => 'banner_back_color',
						'label'         => __( 'Цвет фона', 'mks-core' ),
						'type'          => 'color_picker',
						'return_format' => 'string',
					),
					array(
						'key'           => 'field_683da59422ae6',
						'name'          => 'banner_back_image',
						'label'         => __( 'Изображение фона', 'mks-core' ),
						'type'          => 'image',
						'return_format' => 'url',
						'preview_size'  => 'medium',
						'library'       => 'all',
					),
					array(
						'key'       => 'field_mks_timer_tab',
						'label'     => __( 'Таймер', 'mks-core' ),
						'type'      => 'tab',
						'placement' => 'top',
					),
					array(
						'key'            => 'field_mks_banner_show_from',
						'name'           => 'banner_show_from',
						'label'          => __( 'Дата и время начала акции', 'mks-core' ),
						'instructions'   => __( 'С этого момента вместо баннера показывается таймер. Пустое значение — таймер сразу.', 'mks-core' ),
						'type'           => 'date_time_picker',
						'display_format' => 'd.m.Y H:i',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
						'wrapper'        => array(
							'width' => '50',
						),
					),
					array(
						'key'            => 'field_mks_countdown_until',
						'name'           => 'countdown_until',
						'label'          => __( 'Дата и время окончания акции', 'mks-core' ),
						'instructions'   => __( 'Заполненное значение включает таймер. После окончания снова показывается баннер.', 'mks-core' ),
						'type'           => 'date_time_picker',
						'display_format' => 'd.m.Y H:i',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
						'wrapper'        => array(
							'width' => '50',
						),
					),
					array(
						'key'           => 'field_mks_countdown_title',
						'name'          => 'countdown_title',
						'label'         => __( 'Заголовок таймера', 'mks-core' ),
						'type'          => 'text',
						'default_value' => __( 'До конца акции осталось', 'mks-core' ),
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
	 * Проверяет, нужно ли показывать баннер в шапке.
	 *
	 * Баннер с таймером вне своего окна уступает место обычному баннеру,
	 * если его изображение или подпись были сохранены ранее.
	 *
	 * @param int $post_id ID баннера.
	 * @return bool
	 */
	public static function is_visible( $post_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return true;
		}

		if ( ! get_field( 'countdown_until', $post_id ) ) {
			return true;
		}

		return self::is_countdown_active( $post_id ) || self::has_banner_content( $post_id );
	}

	/**
	 * Проверяет, находится ли таймер внутри окна показа.
	 *
	 * @param int $post_id ID баннера.
	 * @return bool
	 */
	public static function is_countdown_active( $post_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return false;
		}

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
	 * Возвращает метку времени поля с датой в часовом поясе сайта.
	 *
	 * @param string $name    Имя поля.
	 * @param int    $post_id ID баннера.
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
	 * Проверяет, есть ли у баннера содержимое для показа обычным баннером.
	 *
	 * @param int $post_id ID баннера.
	 * @return bool
	 */
	private static function has_banner_content( $post_id ) {
		return get_post_thumbnail_id( $post_id ) || '' !== trim( get_post_field( 'post_content', $post_id ) );
	}

	/**
	 * Ограничивает дату окончания таймера 99 днями вперёд.
	 *
	 * @param bool|string $valid Статус валидации.
	 * @param mixed       $value Отправленное значение.
	 * @param array       $field Массив поля.
	 * @param string      $input Имя поля ввода.
	 * @return bool|string
	 */
	public static function validate_until( $valid, $value, $field, $input ) {
		if ( '' === $value ) {
			return $valid;
		}

		$until = DateTime::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() );

		if ( ! $until ) {
			return __( 'Укажите корректную дату окончания.', 'mks-core' );
		}

		if ( $until->getTimestamp() > time() + 99 * DAY_IN_SECONDS ) {
			return __( 'Дата не может быть дальше 99 дней от текущего момента.', 'mks-core' );
		}

		return $valid;
	}
}
