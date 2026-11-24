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
}
