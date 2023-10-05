<?php

/**
 * @property-read bool $is_plugin_css_used
 *
 * @property-read $groups_header_font_size
 *
 * @property-read $simple_group_font_color
 * @property-read $simple_group_background_color
 * @property-read $simple_group_border_color
 * @property-read $simple_group_border_width
 * @property-read $simple_group_active_border_color
 * @property-read $simple_group_border_hover_color
 *
 * @property-read $photo_group_item_height
 * @property-read $photo_group_item_width
 * @property-read $photo_group_border_radius
 * @property-read $photo_group_border_color
 * @property-read $photo_group_active_border_color
 *
 * @property-read $color_group_border_color
 * @property-read $color_group_border_hover_color
 * @property-read $color_group_border_width
 * @property-read $color_group_active_border_color
 * @property-read $color_group_border_radius
 */
class shopProductgroupStyleConfig extends shopProductgroupImmutableStructure
{
	protected $is_plugin_css_used;

	protected $groups_header_font_size;

	protected $simple_group_font_color;
	protected $simple_group_background_color;
	protected $simple_group_border_color;
	protected $simple_group_border_width;
	protected $simple_group_active_border_color;
	protected $simple_group_border_hover_color;

	protected $photo_group_item_height;
	protected $photo_group_item_width;
	protected $photo_group_border_radius;
	protected $photo_group_border_image_radius;
	protected $photo_group_border_color;
	protected $photo_group_active_border_color;

	protected $color_group_border_color;
	protected $color_group_border_hover_color;
	protected $color_group_border_width;
	protected $color_group_active_border_color;
	protected $color_group_border_radius;

	public function __construct(
		$is_plugin_css_used,

		$groups_header_font_size,

		$simple_group_font_color,
		$simple_group_background_color,
		$simple_group_border_color,
		$simple_group_border_width,
		$simple_group_active_border_color,
		$simple_group_border_hover_color,

		$photo_group_item_height,
		$photo_group_item_width,
		$photo_group_border_radius,
		$photo_group_border_image_radius,
		$photo_group_border_color,
		$photo_group_active_border_color,

		$color_group_border_color,
		$color_group_border_hover_color,
		$color_group_border_width,
		$color_group_active_border_color,
		$color_group_border_radius
	)
	{
		$this->is_plugin_css_used = $is_plugin_css_used;

		$this->groups_header_font_size = $groups_header_font_size;

		$this->simple_group_font_color = $simple_group_font_color;
		$this->simple_group_background_color = $simple_group_background_color;
		$this->simple_group_border_color = $simple_group_border_color;
		$this->simple_group_border_width = $simple_group_border_width;
		$this->simple_group_active_border_color = $simple_group_active_border_color;
		$this->simple_group_border_hover_color = $simple_group_border_hover_color;

		$this->photo_group_item_height = $photo_group_item_height;
		$this->photo_group_item_width = $photo_group_item_width;
		$this->photo_group_border_radius = $photo_group_border_radius;
		$this->photo_group_border_image_radius = $photo_group_border_image_radius;
		$this->photo_group_border_color = $photo_group_border_color;
		$this->photo_group_active_border_color = $photo_group_active_border_color;

		$this->color_group_border_color = $color_group_border_color;
		$this->color_group_border_hover_color = $color_group_border_hover_color;
		$this->color_group_border_width = $color_group_border_width;
		$this->color_group_active_border_color = $color_group_active_border_color;
		$this->color_group_border_radius = $color_group_border_radius;
	}
}