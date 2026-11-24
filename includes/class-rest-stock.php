<?php
/**
 * REST-эндпоинт: остатки товаров (только чтение).
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Регистрирует GET-маршрут остатков и возвращает строки остатков.
 */
class MKS_Core_REST_Stock {

	/** Максимум записей на страницу. */
	const MAX_PER_PAGE = 100;

	/**
	 * Подключается к rest_api_init и к авторизации ключами WC для своего пространства имён.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'woocommerce_rest_is_request_to_rest_api', array( __CLASS__, 'allow_wc_auth_for_namespace' ) );
	}

	/**
	 * Позволяет WooCommerce аутентифицировать ключи клиентов на настроенном маршруте.
	 *
	 * @param bool $is Считается ли запрос запросом WC REST.
	 * @return bool
	 */
	public static function allow_wc_auth_for_namespace( $is ) {
		if ( $is || empty( $_SERVER['REQUEST_URI'] ) ) {
			return $is;
		}

		$endpoint = trim( MKS_Core_Settings::get()['endpoint_path'], '/' );
		if ( '' === $endpoint ) {
			return false;
		}

		$path = '/' . trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' );
		$marker = '/' . trim( rest_get_url_prefix(), '/' ) . '/';
		$pos    = strpos( $path, $marker );
		if ( false === $pos ) {
			return false;
		}

		$after = trim( substr( $path, $pos + strlen( $marker ) ), '/' );

		return $after === $endpoint || 0 === strpos( $after, $endpoint . '/' );
	}

	/**
	 * Регистрирует маршрут только для чтения по настройкам.
	 */
	public static function register_routes() {
		$settings = MKS_Core_Settings::get();
		list( $namespace, $route ) = MKS_Core_Settings::endpoint_parts();

		register_rest_route(
			$namespace,
			$route,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_items' ),
				'permission_callback' => array( __CLASS__, 'permissions_check' ),
				'args'                => array(
					'page'     => array(
						'description'       => 'Номер страницы.',
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'validate_callback' => 'rest_validate_request_arg',
						'sanitize_callback' => 'rest_sanitize_request_arg',
					),
					'per_page' => array(
						'description'       => 'Записей на страницу.',
						'type'              => 'integer',
						'default'           => (int) $settings['per_page_default'],
						'minimum'           => 1,
						'maximum'           => self::MAX_PER_PAGE,
						'validate_callback' => 'rest_validate_request_arg',
						'sanitize_callback' => 'rest_sanitize_request_arg',
					),
				),
			)
		);
	}

	/**
	 * @return true|WP_Error
	 */
	public static function permissions_check() {
		if ( isset( $_GET['consumer_key'] ) || isset( $_GET['consumer_secret'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return new WP_Error(
				'mks_core_query_auth_forbidden',
				__( 'API keys in query parameters are not allowed. Use HTTP Basic Authentication.', 'mks-core' ),
				array( 'status' => 401 )
			);
		}

		if ( current_user_can( MKS_Core_Roles::CAPABILITY ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to do that.', 'mks-core' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * @param WP_REST_Request $request Запрос.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_items( $request ) {
		$settings = MKS_Core_Settings::get();
		$page     = (int) $request['page'];
		$per_page = (int) $request['per_page'];

		if ( '' === $settings['publisher_taxonomy'] || '' === $settings['publisher_term'] ) {
			return new WP_Error(
				'mks_core_filter_not_configured',
				__( 'Attribute filter is not configured.', 'mks-core' ),
				array( 'status' => 503 )
			);
		}

		$threshold = (int) $settings['stock_threshold'];

		$query = new WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => $per_page,
				'paged'                  => $page,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'relation' => 'AND',
					array(
						'taxonomy' => $settings['publisher_taxonomy'],
						'field'    => 'slug',
						'terms'    => $settings['publisher_term'],
					),
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => 'simple',
					),
				),
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_stock',
						'value'   => $threshold,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$items = array();
		foreach ( $query->posts as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$items[] = self::serialize_product( $product, $threshold );
		}

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		return $response;
	}

	/**
	 * @param WC_Product $product   Товар.
	 * @param int        $threshold Порог остатка из настроек.
	 * @return array
	 */
	private static function serialize_product( $product, $threshold ) {
		return array(
			'name'           => $product->get_name(),
			'sku'            => $product->get_sku(),
			'isbn'           => $product->get_attribute( 'isbn' ),
			'stock_quantity' => 'более ' . (int) $threshold,
		);
	}
}
