<?php
/**
 * Category image badge settings.
 *
 * @package mks-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the category badge rule and renders its settings page.
 */
class MKS_Core_Category_Badge {

	const OPTION = 'mks_core_category_badge';
	const PAGE   = 'mks-category-badge';

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'       => 0,
			'term_ids'      => array(),
			'attachment_id' => 0,
			'text'          => '',
			'text_color'    => '#ffffff',
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

		if ( isset( $stored['term_id'] ) ) {
			$stored['term_ids'] = $stored['term_id'] ? array( (int) $stored['term_id'] ) : array();
			unset( $stored['term_id'] );
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
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
		add_action( 'mks_category_badge', array( __CLASS__, 'render' ) );
	}

	/**
	 * Adds page under WooCommerce.
	 */
	public static function add_menu() {
		add_submenu_page(
			'woocommerce',
			'Бейджи категорий',
			'Бейджи категорий',
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'mks-core-category-badge',
			plugins_url( 'assets/category-badge.js', MKS_CORE_FILE ),
			array( 'jquery', 'wp-color-picker' ),
			MKS_CORE_VERSION,
			true
		);
	}

	/**
	 * Frontend styles.
	 */
	public static function enqueue_frontend_assets() {
		wp_enqueue_style(
			'mks-core-category-badge',
			plugins_url( 'assets/category-badge-frontend.css', MKS_CORE_FILE ),
			array(),
			MKS_CORE_VERSION
		);
	}

	/**
	 * Renders the badge inside a product image container.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function render( $product_id ) {
		$settings = self::get();

		if ( ! $settings['enabled'] || ! $settings['term_ids'] || ! $settings['attachment_id'] ) {
			return;
		}

		if ( ! has_term( $settings['term_ids'], 'product_cat', $product_id ) ) {
			return;
		}

		$image = wp_get_attachment_image(
			(int) $settings['attachment_id'],
			'full',
			false,
			array( 'class' => 'mks-category-badge-img', 'alt' => '' )
		);

		echo '<div class="mks-category-badge">';
		echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( '' !== $settings['text'] ) {
			printf(
				'<span class="mks-category-badge-text" style="color:%s;">%s</span>',
				esc_attr( $settings['text_color'] ),
				esc_html( $settings['text'] )
			);
		}
		echo '</div>';
	}

	/**
	 * Registers setting and fields.
	 */
	public static function register() {
		register_setting(
			'mks_core_category_badge_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'mks_core_category_badge',
			'Бейдж-картинка для категории',
			array( __CLASS__, 'section_intro' ),
			self::PAGE
		);

		add_settings_field(
			'enabled',
			'Показывать бейдж',
			array( __CLASS__, 'field_enabled' ),
			self::PAGE,
			'mks_core_category_badge'
		);

		add_settings_field(
			'term_ids',
			'Товарные категории',
			array( __CLASS__, 'field_term' ),
			self::PAGE,
			'mks_core_category_badge'
		);

		add_settings_field(
			'attachment_id',
			'Изображение',
			array( __CLASS__, 'field_image' ),
			self::PAGE,
			'mks_core_category_badge'
		);

		add_settings_field(
			'text',
			'Текст на бейдже',
			array( __CLASS__, 'field_text' ),
			self::PAGE,
			'mks_core_category_badge'
		);

		add_settings_field(
			'text_color',
			'Цвет текста',
			array( __CLASS__, 'field_text_color' ),
			self::PAGE,
			'mks_core_category_badge'
		);
	}

	/**
	 * Section description.
	 */
	public static function section_intro() {
		echo '<p>Бейдж-картинка выводится на всех товарах выбранных категорий. Переключатель действует сразу на все товары.</p>';
	}

	/**
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$output = self::get();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$output['enabled'] = empty( $input['enabled'] ) ? 0 : 1;

		$term_ids = array();
		if ( isset( $input['term_ids'] ) && is_array( $input['term_ids'] ) ) {
			foreach ( $input['term_ids'] as $term_id ) {
				$term = get_term( absint( $term_id ), 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$term_ids[] = (int) $term->term_id;
				}
			}
		}
		$output['term_ids'] = $term_ids;

		$attachment_id = isset( $input['attachment_id'] ) ? absint( $input['attachment_id'] ) : 0;
		$output['attachment_id'] = ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) ? $attachment_id : 0;

		$output['text'] = isset( $input['text'] ) ? sanitize_text_field( $input['text'] ) : '';

		$text_color = isset( $input['text_color'] ) ? sanitize_hex_color( $input['text_color'] ) : '';
		$output['text_color'] = $text_color ? $text_color : self::defaults()['text_color'];

		return $output;
	}

	/**
	 * Field: enabled.
	 */
	public static function field_enabled() {
		$settings = self::get();
		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="1"%2$s /> Показывать бейдж на товарах выбранных категорий</label>',
			esc_attr( self::OPTION ),
			checked( $settings['enabled'], 1, false )
		);
	}

	/**
	 * Field: product categories (multiple).
	 */
	public static function field_term() {
		$settings = self::get();
		$selected = array_map( 'intval', $settings['term_ids'] );
		$terms    = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return;
		}

		$children = array();
		foreach ( $terms as $term ) {
			$children[ (int) $term->parent ][] = $term;
		}

		echo '<style>.mks-badge-cats{list-style:none;margin:0}.mks-badge-cats ul{list-style:none;margin:0 0 0 18px}.mks-badge-chip{display:inline-block;margin:0 6px 6px 0;padding:1px 4px 1px 8px;background:#f0f0f1;border:1px solid #c3c4c7;border-radius:3px;font-size:14px;line-height:1.8;white-space:nowrap}.mks-badge-chip button{padding:0 4px;border:0;background:none;color:#787c82;cursor:pointer;font-size:16px;line-height:1}.mks-badge-chip button:hover{color:#d63638}</style>';
		echo '<div id="mks-core-badge-selected" style="margin-bottom:8px;"></div>';
		printf(
			'<p><input type="search" id="mks-core-badge-cats-search" class="regular-text" placeholder="%s" /></p>',
			esc_attr__( 'Поиск категории…', 'mks-core' )
		);
		echo '<div style="display:inline-block;max-width:100%;max-height:300px;overflow-y:scroll;padding:6px 4px 6px 6px;border:1px solid #dcdcde;background:#fff;">';
		echo '<ul class="mks-badge-cats" id="mks-core-badge-cats">';
		self::render_term_checkboxes( $children, 0, $selected );
		echo '</ul>';
		echo '</div>';
		echo '<p class="description">Категории товаров, которым назначается бейдж.</p>';
	}

	/**
	 * Renders nested category checkboxes.
	 *
	 * @param array $children Terms grouped by parent ID.
	 * @param int   $parent   Parent term ID.
	 * @param array $selected Selected term IDs.
	 */
	private static function render_term_checkboxes( $children, $parent, $selected ) {
		if ( empty( $children[ $parent ] ) ) {
			return;
		}

		foreach ( $children[ $parent ] as $term ) {
			printf(
				'<li><label><input type="checkbox" name="%1$s[term_ids][]" value="%2$d"%3$s /> %4$s</label>',
				esc_attr( self::OPTION ),
				(int) $term->term_id,
				checked( in_array( (int) $term->term_id, $selected, true ), true, false ),
				esc_html( $term->name )
			);

			if ( ! empty( $children[ (int) $term->term_id ] ) ) {
				echo '<ul>';
				self::render_term_checkboxes( $children, (int) $term->term_id, $selected );
				echo '</ul>';
			}

			echo '</li>';
		}
	}

	/**
	 * Field: badge image.
	 */
	public static function field_image() {
		$settings      = self::get();
		$attachment_id = (int) $settings['attachment_id'];
		$image_url     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

		printf(
			'<input type="hidden" id="mks-core-badge-attachment" name="%1$s[attachment_id]" value="%2$d" />',
			esc_attr( self::OPTION ),
			$attachment_id
		);
		printf(
			'<img id="mks-core-badge-preview" src="%1$s" alt="" style="max-width:200px;height:auto;display:%2$s;margin-bottom:10px;" />',
			esc_url( $image_url ),
			$image_url ? 'block' : 'none'
		);
		echo '<p>';
		echo '<button type="button" class="button" id="mks-core-badge-select">Выбрать изображение</button> ';
		printf(
			'<button type="button" class="button-link button-link-delete" id="mks-core-badge-remove" style="display:%s;">Удалить</button>',
			$attachment_id ? 'inline' : 'none'
		);
		echo '</p>';
		echo '<p class="description">Картинка из медиатеки. Небольшое изображение в PNG с прозрачным фоном.</p>';
	}

	/**
	 * Field: badge text.
	 */
	public static function field_text() {
		$settings = self::get();

		printf(
			'<input type="text" name="%1$s[text]" value="%2$s" class="regular-text" />',
			esc_attr( self::OPTION ),
			esc_attr( $settings['text'] )
		);
		echo '<p class="description">Например, «20%». Если поле пустое, выводится только картинка.</p>';
	}

	/**
	 * Field: text color.
	 */
	public static function field_text_color() {
		$settings = self::get();
		$defaults = self::defaults();

		printf(
			'<input type="text" class="mks-core-color-picker" name="%1$s[text_color]" value="%2$s" data-default-color="%3$s" />',
			esc_attr( self::OPTION ),
			esc_attr( $settings['text_color'] ),
			esc_attr( $defaults['text_color'] )
		);
		echo '<p class="description">Цвет текста поверх картинки.</p>';
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
				settings_fields( 'mks_core_category_badge_group' );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
