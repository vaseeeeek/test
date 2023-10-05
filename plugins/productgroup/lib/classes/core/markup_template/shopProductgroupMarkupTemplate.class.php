<?php

/**
 * @property-read $template_id
 * @property-read $theme_id
 * @property-read $plugin_file_name
 * @property-read $theme_file_name
 */
class shopProductgroupMarkupTemplate extends shopProductgroupImmutableStructure
{
	protected $template_id;
	protected $theme_id;
	protected $plugin_file_name;
	protected $theme_file_name;

	public function __construct(
		$template_id,
		$theme_id,
		$plugin_file_name,
		$theme_file_name
	)
	{
		$this->template_id = $template_id;
		$this->theme_id = $theme_id;
		$this->plugin_file_name = $plugin_file_name;
		$this->theme_file_name = $theme_file_name;
	}
}