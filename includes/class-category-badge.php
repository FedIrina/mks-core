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
 * Stores the category badge rules and renders its settings page.
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
			'enabled' => 0,
			'badges'  => array(),
		);
	}

	/**
	 * Default values of a single badge.
	 *
	 * @return array
	 */
	public static function default_badge() {
		return array(
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

		if ( ! isset( $stored['badges'] ) ) {
			$stored['badges'] = array(
				array(
					'term_ids'      => isset( $stored['term_ids'] ) ? (array) $stored['term_ids'] : array(),
					'attachment_id' => isset( $stored['attachment_id'] ) ? (int) $stored['attachment_id'] : 0,
					'text'          => isset( $stored['text'] ) ? $stored['text'] : '',
					'text_color'    => isset( $stored['text_color'] ) ? $stored['text_color'] : '',
				),
			);
			unset( $stored['term_ids'], $stored['attachment_id'], $stored['text'], $stored['text_color'] );
		}

		$settings = wp_parse_args( $stored, self::defaults() );

		$badges = array();
		foreach ( (array) $settings['badges'] as $badge ) {
			if ( is_array( $badge ) ) {
				$badges[] = wp_parse_args( $badge, self::default_badge() );
			}
		}
		$settings['badges'] = $badges;

		return $settings;
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

		if ( ! $settings['enabled'] || ! $settings['badges'] ) {
			return;
		}

		foreach ( $settings['badges'] as $badge ) {
			if ( ! $badge['term_ids'] || ! $badge['attachment_id'] ) {
				continue;
			}

			if ( ! has_term( $badge['term_ids'], 'product_cat', $product_id ) ) {
				continue;
			}

			$image = wp_get_attachment_image(
				(int) $badge['attachment_id'],
				'full',
				false,
				array( 'class' => 'mks-category-badge-img', 'alt' => '' )
			);

			echo '<div class="mks-category-badge">';
			echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( '' !== $badge['text'] ) {
				printf(
					'<span class="mks-category-badge-text" style="color:%s;">%s</span>',
					esc_attr( $badge['text_color'] ),
					esc_html( $badge['text'] )
				);
			}
			echo '</div>';
		}
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
			'Бейджи для категорий',
			array( __CLASS__, 'section_intro' ),
			self::PAGE
		);

		add_settings_field(
			'enabled',
			'Показывать бейджи',
			array( __CLASS__, 'field_enabled' ),
			self::PAGE,
			'mks_core_category_badge'
		);

		add_settings_field(
			'badges',
			'Бейджи',
			array( __CLASS__, 'field_badges' ),
			self::PAGE,
			'mks_core_category_badge'
		);
	}

	/**
	 * Section description.
	 */
	public static function section_intro() {
		echo '<p>Бейдж выводится на всех товарах выбранных категорий. Каждый бейдж задаётся отдельно: свой набор категорий, картинка, текст и цвет текста.</p>';
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
		$output['badges']  = array();

		if ( ! isset( $input['badges'] ) || ! is_array( $input['badges'] ) ) {
			return $output;
		}

		foreach ( $input['badges'] as $badge ) {
			if ( ! is_array( $badge ) ) {
				continue;
			}

			$term_ids = array();
			if ( isset( $badge['term_ids'] ) && is_array( $badge['term_ids'] ) ) {
				foreach ( $badge['term_ids'] as $term_id ) {
					$term = get_term( absint( $term_id ), 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$term_ids[] = (int) $term->term_id;
					}
				}
			}

			if ( ! $term_ids ) {
				continue;
			}

			$attachment_id = isset( $badge['attachment_id'] ) ? absint( $badge['attachment_id'] ) : 0;
			$text_color    = isset( $badge['text_color'] ) ? sanitize_hex_color( $badge['text_color'] ) : '';

			$output['badges'][] = array(
				'term_ids'      => $term_ids,
				'attachment_id' => ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) ? $attachment_id : 0,
				'text'          => isset( $badge['text'] ) ? sanitize_text_field( $badge['text'] ) : '',
				'text_color'    => $text_color ? $text_color : self::default_badge()['text_color'],
			);
		}

		return $output;
	}

	/**
	 * Field: enabled.
	 */
	public static function field_enabled() {
		$settings = self::get();
		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="1"%2$s /> Показывать бейджи на товарах выбранных категорий</label>',
			esc_attr( self::OPTION ),
			checked( $settings['enabled'], 1, false )
		);
	}

	/**
	 * Field: badge sets (repeatable).
	 */
	public static function field_badges() {
		$settings = self::get();
		$badges   = $settings['badges'];

		if ( ! $badges ) {
			$badges = array( self::default_badge() );
		}

		$terms = get_terms(
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

		echo '<style>#mks-badge-sets .mks-badge-set{border:1px solid #c3c4c7;background:#fff;padding:0 12px 8px;margin:0 0 12px;max-width:760px}.mks-badge-set-head{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f1;margin-bottom:8px}.mks-badge-set-head strong{font-size:14px}.mks-badge-cats{list-style:none;margin:0}.mks-badge-cats ul{list-style:none;margin:0 0 0 18px}.mks-badge-cats-scroll{display:inline-block;max-width:100%;max-height:220px;overflow-y:scroll;padding:6px 4px 6px 6px;border:1px solid #dcdcde;background:#fff}.mks-badge-chip{display:inline-block;margin:0 6px 6px 0;padding:1px 4px 1px 8px;background:#f0f0f1;border:1px solid #c3c4c7;border-radius:3px;font-size:14px;line-height:1.8;white-space:nowrap}.mks-badge-chip button{padding:0 4px;border:0;background:none;color:#787c82;cursor:pointer;font-size:16px;line-height:1}.mks-badge-chip button:hover{color:#d63638}</style>';
		echo '<div id="mks-badge-sets">';
		foreach ( $badges as $index => $badge ) {
			self::render_badge_set( $children, $index, $badge );
		}
		echo '</div>';
		echo '<p><button type="button" class="button" id="mks-badge-add">Добавить бейдж</button></p>';
		echo '<template id="mks-badge-set-template">';
		self::render_badge_set( $children, '__index__', self::default_badge() );
		echo '</template>';
	}

	/**
	 * Renders one badge set.
	 *
	 * @param array      $children Terms grouped by parent ID.
	 * @param int|string $index    Badge index or template placeholder.
	 * @param array      $badge    Badge values.
	 */
	private static function render_badge_set( $children, $index, $badge ) {
		$name_base     = self::OPTION . '[badges][' . $index . ']';
		$attachment_id = (int) $badge['attachment_id'];
		$image_url     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$selected      = array_map( 'intval', (array) $badge['term_ids'] );
		?>
		<div class="mks-badge-set">
			<div class="mks-badge-set-head">
				<strong>Бейдж <span class="mks-badge-set-number"><?php echo is_int( $index ) ? (int) $index + 1 : ''; ?></span></strong>
				<button type="button" class="button-link button-link-delete mks-badge-set-remove">Удалить бейдж</button>
			</div>
			<p><strong>Товарные категории</strong></p>
			<div class="mks-badge-selected"></div>
			<p><input type="search" class="mks-badge-cats-search regular-text" placeholder="Поиск категории…" /></p>
			<div class="mks-badge-cats-scroll">
				<ul class="mks-badge-cats">
					<?php self::render_term_checkboxes( $children, 0, $selected, $name_base . '[term_ids][]' ); ?>
				</ul>
			</div>
			<p><strong>Изображение</strong></p>
			<input type="hidden" class="mks-badge-attachment" name="<?php echo esc_attr( $name_base . '[attachment_id]' ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>" />
			<img class="mks-badge-preview" src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:200px;height:auto;display:<?php echo $image_url ? 'block' : 'none'; ?>;margin-bottom:10px;" />
			<p>
				<button type="button" class="button mks-badge-pick">Выбрать изображение</button>
				<button type="button" class="button-link button-link-delete mks-badge-remove" style="display:<?php echo $attachment_id ? 'inline' : 'none'; ?>;">Удалить</button>
			</p>
			<p><strong>Текст на бейдже</strong><br />
				<input type="text" class="regular-text" name="<?php echo esc_attr( $name_base . '[text]' ); ?>" value="<?php echo esc_attr( $badge['text'] ); ?>" />
			</p>
			<p><strong>Цвет текста</strong><br />
				<input type="text" class="mks-core-color-picker" name="<?php echo esc_attr( $name_base . '[text_color]' ); ?>" value="<?php echo esc_attr( $badge['text_color'] ); ?>" data-default-color="<?php echo esc_attr( self::default_badge()['text_color'] ); ?>" />
			</p>
		</div>
		<?php
	}

	/**
	 * Renders nested category checkboxes.
	 *
	 * @param array  $children Terms grouped by parent ID.
	 * @param int    $parent   Parent term ID.
	 * @param array  $selected Selected term IDs.
	 * @param string $name     Checkbox name attribute.
	 */
	private static function render_term_checkboxes( $children, $parent, $selected, $name ) {
		if ( empty( $children[ $parent ] ) ) {
			return;
		}

		foreach ( $children[ $parent ] as $term ) {
			printf(
				'<li><label><input type="checkbox" name="%1$s" value="%2$d"%3$s /> %4$s</label>',
				esc_attr( $name ),
				(int) $term->term_id,
				checked( in_array( (int) $term->term_id, $selected, true ), true, false ),
				esc_html( $term->name )
			);

			if ( ! empty( $children[ (int) $term->term_id ] ) ) {
				echo '<ul>';
				self::render_term_checkboxes( $children, (int) $term->term_id, $selected, $name );
				echo '</ul>';
			}

			echo '</li>';
		}
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
