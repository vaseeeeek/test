<?php

/**
 * @property-read $groups_block_template
 * @property-read $is_groups_block_template_default
 * @property-read $simple_group_template
 * @property-read $is_simple_group_template_default
 * @property-read $photo_group_template
 * @property-read $is_photo_group_template_default
 * @property-read $color_group_template
 * @property-read $is_color_group_template_default
 */
class shopProductgroupMarkupTemplateSettings extends shopProductgroupImmutableStructure
{
	protected $groups_block_template;
	protected $is_groups_block_template_default;
	protected $simple_group_template;
	protected $is_simple_group_template_default;
	protected $photo_group_template;
	protected $is_photo_group_template_default;
	protected $color_group_template;
	protected $is_color_group_template_default;

	/**
	 * @param string $groups_block_template
	 * @param bool $is_groups_block_template_default
	 * @param string $simple_group_template
	 * @param bool $is_simple_group_template_default
	 * @param string $photo_group_template
	 * @param bool $is_photo_group_template_default
	 * @param string $color_group_template
	 * @param bool $is_color_group_template_default
	 */
	public function __construct(
		$groups_block_template,
		$is_groups_block_template_default,
		$simple_group_template,
		$is_simple_group_template_default,
		$photo_group_template,
		$is_photo_group_template_default,
		$color_group_template,
		$is_color_group_template_default
	)
	{
		$this->groups_block_template = $groups_block_template;
		$this->is_groups_block_template_default = $is_groups_block_template_default;
		$this->simple_group_template = $simple_group_template;
		$this->is_simple_group_template_default = $is_simple_group_template_default;
		$this->photo_group_template = $photo_group_template;
		$this->is_photo_group_template_default = $is_photo_group_template_default;
		$this->color_group_template = $color_group_template;
		$this->is_color_group_template_default = $is_color_group_template_default;
	}
}