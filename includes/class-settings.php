<?php
/**
 * Plugin settings (Settings → MKS Core).
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders MKS Core settings.
 */
class MKS_Core_Settings {

	const OPTION = 'mks_core_settings';
	const PAGE   = 'mks-core';

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'per_page_default'   => 20,
			'endpoint_path'      => 'mks-core/v1/stock',
			'publisher_taxonomy' => '',
			'publisher_term'     => '',
			'stock_threshold'    => 50,
		);
	}

	/**
	 * @return array
	 */
	public static function get() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		if ( isset( $stored['publisher_attribute'] ) ) {
			unset( $stored['publisher_attribute'] );
		}

		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Writes defaults once (activation).
	 */
	public static function activate() {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, self::defaults() );
		}
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_mks_core_attribute_terms', array( __CLASS__, 'ajax_attribute_terms' ) );
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( ! wp_script_is( 'selectWoo', 'registered' ) && function_exists( 'WC' ) ) {
			wp_register_script(
				'selectWoo',
				WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js',
				array( 'jquery' ),
				'1.0.6',
				true
			);
		}

		if ( ! wp_style_is( 'select2', 'registered' ) && function_exists( 'WC' ) ) {
			wp_register_style(
				'select2',
				WC()->plugin_url() . '/assets/css/select2.css',
				array(),
				'4.0.3'
			);
		}

		wp_enqueue_style( 'select2' );
		wp_enqueue_script( 'selectWoo' );

		wp_enqueue_script(
			'mks-core-settings',
			plugins_url( 'assets/admin-settings.js', MKS_CORE_FILE ),
			array( 'jquery', 'selectWoo' ),
			MKS_CORE_VERSION,
			true
		);

		wp_localize_script(
			'mks-core-settings',
			'mksCoreSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mks_core_settings' ),
				'i18n'    => array(
					'search'       => 'Поиск…',
					'noResults'    => 'Ничего не найдено',
					'placeholder'  => 'Выберите…',
					'loadingTerms' => 'Загрузка значений…',
				),
			)
		);
	}

	/**
	 * AJAX: terms for selected attribute taxonomy.
	 */
	public static function ajax_attribute_terms() {
		check_ajax_referer( 'mks_core_settings', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		$search   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json( array( 'results' => array() ) );
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 50,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( '' !== $search ) {
			$args['search'] = $search;
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			wp_send_json( array( 'results' => array() ) );
		}

		$results = array();
		foreach ( $terms as $term ) {
			$results[] = array(
				'id'   => $term->slug,
				'text' => $term->name,
			);
		}

		wp_send_json( array( 'results' => $results ) );
	}

	/**
	 * Registers setting and fields.
	 */
	public static function register() {
		register_setting(
			'mks_core_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'mks_core_rest_api',
			'REST API',
			array( __CLASS__, 'section_rest_api' ),
			self::PAGE
		);

		add_settings_field(
			'per_page_default',
			'Записей на страницу по умолчанию',
			array( __CLASS__, 'field_per_page_default' ),
			self::PAGE,
			'mks_core_rest_api'
		);

		add_settings_field(
			'endpoint_path',
			'URL эндпоинта',
			array( __CLASS__, 'field_endpoint_path' ),
			self::PAGE,
			'mks_core_rest_api'
		);

		add_settings_field(
			'publisher_taxonomy',
			'Атрибут',
			array( __CLASS__, 'field_publisher_taxonomy' ),
			self::PAGE,
			'mks_core_rest_api'
		);

		add_settings_field(
			'publisher_term',
			'Значение атрибута',
			array( __CLASS__, 'field_publisher_term' ),
			self::PAGE,
			'mks_core_rest_api'
		);

		add_settings_field(
			'stock_threshold',
			'Порог складского остатка',
			array( __CLASS__, 'field_stock_threshold' ),
			self::PAGE,
			'mks_core_rest_api'
		);
	}

	/**
	 * Adds page under Settings.
	 */
	public static function add_menu() {
		add_options_page(
			'MKS Core',
			'MKS Core',
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Section description.
	 */
	public static function section_rest_api() {
		echo '<p>Параметры выдачи остатков по атрибуту товара.</p>';
	}

	/**
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = self::get();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		if ( isset( $input['per_page_default'] ) ) {
			$per_page = absint( $input['per_page_default'] );
			if ( $per_page < 1 ) {
				$per_page = $defaults['per_page_default'];
			}
			if ( $per_page > MKS_Core_REST_Stock::MAX_PER_PAGE ) {
				$per_page = MKS_Core_REST_Stock::MAX_PER_PAGE;
			}
			$output['per_page_default'] = $per_page;
		}

		if ( isset( $input['endpoint_path'] ) ) {
			$path = self::normalize_endpoint_path( $input['endpoint_path'] );
			$output['endpoint_path'] = $path ? $path : $defaults['endpoint_path'];
		}

		if ( isset( $input['publisher_taxonomy'] ) ) {
			$taxonomy = sanitize_key( $input['publisher_taxonomy'] );
			if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
				$output['publisher_taxonomy'] = $taxonomy;
			} else {
				$output['publisher_taxonomy'] = '';
				$output['publisher_term']     = '';
			}
		}

		if ( isset( $input['publisher_term'] ) ) {
			$term_slug = sanitize_title( $input['publisher_term'] );
			$taxonomy  = $output['publisher_taxonomy'];
			$term      = ( $taxonomy && $term_slug ) ? get_term_by( 'slug', $term_slug, $taxonomy ) : false;
			if ( $term && ! is_wp_error( $term ) ) {
				$output['publisher_term'] = $term->slug;
			} else {
				$output['publisher_term'] = '';
			}
		}

		if ( isset( $input['stock_threshold'] ) ) {
			$threshold = absint( $input['stock_threshold'] );
			if ( $threshold < 1 ) {
				$threshold = $defaults['stock_threshold'];
			}
			$output['stock_threshold'] = $threshold;
		}

		unset( $output['publisher_attribute'] );

		return $output;
	}

	/**
	 * Accepts full URL or path after /wp-json/; stores path only.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function normalize_endpoint_path( $value ) {
		$value = trim( wp_unslash( $value ) );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $value ) ) {
			$path = (string) wp_parse_url( $value, PHP_URL_PATH );
		} else {
			$path = $value;
		}

		$path   = trim( $path, '/' );
		$prefix = trim( rest_get_url_prefix(), '/' );
		if ( 0 === strpos( $path, $prefix . '/' ) ) {
			$path = substr( $path, strlen( $prefix ) + 1 );
		}

		$path = strtolower( $path );
		$path = preg_replace( '#[^a-z0-9\-_\/]+#', '', $path );
		$path = trim( $path, '/' );

		return $path;
	}

	/**
	 * Full public URL for the configured endpoint.
	 *
	 * @return string
	 */
	public static function endpoint_url() {
		$settings = self::get();
		return rest_url( $settings['endpoint_path'] );
	}

	/**
	 * Splits endpoint_path into namespace + route for register_rest_route.
	 *
	 * @return array{0:string,1:string} Namespace and route (with leading slash).
	 */
	public static function endpoint_parts() {
		$settings = self::get();
		$path     = trim( $settings['endpoint_path'], '/' );
		$segments = array_values( array_filter( explode( '/', $path ) ) );

		if ( count( $segments ) < 2 ) {
			$defaults = self::defaults();
			$path     = $defaults['endpoint_path'];
			$segments = explode( '/', $path );
		}

		$route     = '/' . array_pop( $segments );
		$namespace = implode( '/', $segments );

		return array( $namespace, $route );
	}

	/**
	 * Product attribute taxonomies for the select (label => taxonomy).
	 *
	 * @return array<string,string> taxonomy => label
	 */
	public static function get_attribute_choices() {
		$choices = array();
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $choices;
		}

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			$label    = $attribute->attribute_label ? $attribute->attribute_label : $attribute->attribute_name;
			$choices[ $taxonomy ] = $label;
		}

		asort( $choices, SORT_NATURAL | SORT_FLAG_CASE );

		return $choices;
	}

	/**
	 * Field: stock quantity threshold for API selection.
	 */
	public static function field_stock_threshold() {
		$settings = self::get();
		printf(
			'<input type="number" name="%1$s[stock_threshold]" value="%2$d" min="1" class="small-text" /> <span class="description">в выборку — остаток строго больше этого значения; в API вместо числа — «более %2$d»</span>',
			esc_attr( self::OPTION ),
			(int) $settings['stock_threshold']
		);
	}

	/**
	 * Field: per_page default.
	 */
	public static function field_per_page_default() {
		$settings = self::get();
		$max      = MKS_Core_REST_Stock::MAX_PER_PAGE;
		printf(
			'<input type="number" name="%1$s[per_page_default]" value="%2$d" min="1" max="%3$d" class="small-text" /> <span class="description">от 1 до %3$d</span>',
			esc_attr( self::OPTION ),
			(int) $settings['per_page_default'],
			(int) $max
		);
	}

	/**
	 * Field: endpoint path / URL.
	 */
	public static function field_endpoint_path() {
		$settings = self::get();
		printf(
			'<input type="text" name="%1$s[endpoint_path]" value="%2$s" class="regular-text" />',
			esc_attr( self::OPTION ),
			esc_attr( $settings['endpoint_path'] )
		);
		echo '<p class="description">Путь после <code>/wp-json/</code> или полный URL. В БД хранится путь.</p>';
		printf(
			'<p class="description">Текущий URL: <code>%s</code></p>',
			esc_html( self::endpoint_url() )
		);
	}

	/**
	 * Field: attribute taxonomy (select by label).
	 */
	public static function field_publisher_taxonomy() {
		$settings = self::get();
		$choices  = self::get_attribute_choices();
		$current  = $settings['publisher_taxonomy'];

		printf(
			'<select id="mks-core-publisher-taxonomy" name="%1$s[publisher_taxonomy]" class="mks-core-select" style="width:320px;">',
			esc_attr( self::OPTION )
		);
		echo '<option value="">' . esc_html__( 'Выберите…', 'mks-core' ) . '</option>';

		foreach ( $choices as $taxonomy => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $taxonomy ),
				selected( $current, $taxonomy, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">Таксономический атрибут WooCommerce (в БД — имя таксономии).</p>';
	}

	/**
	 * Field: attribute term (select by label, AJAX search).
	 */
	public static function field_publisher_term() {
		$settings = self::get();
		$taxonomy = $settings['publisher_taxonomy'];
		$slug     = $settings['publisher_term'];
		$label    = $slug;

		if ( $taxonomy && $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$label = $term->name;
			}
		}

		printf(
			'<select id="mks-core-publisher-term" name="%1$s[publisher_term]" class="mks-core-select-term" style="width:320px;" data-taxonomy="%2$s">',
			esc_attr( self::OPTION ),
			esc_attr( $taxonomy )
		);

		if ( $slug ) {
			printf(
				'<option value="%1$s" selected="selected">%2$s</option>',
				esc_attr( $slug ),
				esc_html( $label )
			);
		}

		echo '</select>';
		echo '<p class="description">Значение выбранного атрибута (в БД — слаг термина).</p>';
	}

	/**
	 * Renders settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'mks_core_settings_group' );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
