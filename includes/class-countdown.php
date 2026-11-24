<?php
/**
 * Таймер обратного отсчёта.
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Выводит таймер обратного отсчёта для области шапки и для содержимого записей.
 */
class MKS_Core_Countdown {

	const DEFAULT_TITLE = 'До конца акции осталось';

	/**
	 * Хуки.
	 */
	public static function init() {
		add_shortcode( 'mks_countdown', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 1000 );
	}

	/**
	 * Подключает стили и скрипт там, где может появиться таймер.
	 */
	public static function enqueue_assets() {
		$needs_assets = is_front_page();

		if ( ! $needs_assets && is_singular() ) {
			$post = get_post();

			if ( $post && has_shortcode( $post->post_content, 'mks_countdown' ) ) {
				$needs_assets = true;
			}
		}

		if ( ! $needs_assets ) {
			return;
		}

		wp_enqueue_style( 'mks-core-countdown', plugins_url( 'assets/countdown.css', MKS_CORE_FILE ), array(), MKS_CORE_VERSION );
		wp_enqueue_script( 'mks-core-countdown', plugins_url( 'assets/countdown.js', MKS_CORE_FILE ), array(), MKS_CORE_VERSION, true );
	}

	/**
	 * Обёртка шорткода.
	 *
	 * @param array $atts Атрибуты шорткода.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'until' => '',
				'title' => '',
			),
			$atts,
			'mks_countdown'
		);

		return self::render( $atts );
	}

	/**
	 * Формирует разметку таймера.
	 *
	 * @param array $args Аргументы таймера.
	 * @return string
	 */
	public static function render( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'until' => '',
				'title' => '',
			)
		);

		$until = self::parse_until( $args['until'] );

		if ( ! $until ) {
			return '';
		}

		$remaining = $until->getTimestamp() - time();

		if ( $remaining <= 0 ) {
			return '';
		}

		$units = array(
			'days'    => array(
				'value' => intdiv( $remaining, DAY_IN_SECONDS ),
				'label' => 'дней',
			),
			'hours'   => array(
				'value' => intdiv( $remaining % DAY_IN_SECONDS, HOUR_IN_SECONDS ),
				'label' => 'часов',
			),
			'minutes' => array(
				'value' => intdiv( $remaining % HOUR_IN_SECONDS, MINUTE_IN_SECONDS ),
				'label' => 'минут',
			),
			'seconds' => array(
				'value' => $remaining % MINUTE_IN_SECONDS,
				'label' => 'секунд',
			),
		);

		$title = '' !== $args['title'] ? $args['title'] : self::DEFAULT_TITLE;

		$html  = '<div class="mks-countdown" data-until="' . esc_attr( $until->format( 'Y-m-d\TH:i:sP' ) ) . '">';
		$html .= '<div class="mks-countdown__title">' . esc_html( $title ) . '</div>';
		$html .= '<div class="mks-countdown__units">';

		foreach ( $units as $name => $unit ) {
			$html .= '<div class="mks-countdown__unit">';
			$html .= '<div class="mks-countdown__value" data-unit="' . esc_attr( $name ) . '">' . esc_html( str_pad( (string) $unit['value'], 2, '0', STR_PAD_LEFT ) ) . '</div>';
			$html .= '<div class="mks-countdown__label">' . esc_html( $unit['label'] ) . '</div>';
			$html .= '</div>';
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Разбирает дату окончания в часовом поясе сайта.
	 *
	 * @param string $value Дата в формате Y-m-d H:i:s.
	 * @return DateTime|false
	 */
	private static function parse_until( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return false;
		}

		return DateTime::createFromFormat( 'Y-m-d H:i:s', $value, wp_timezone() );
	}
}
