<?php

/**
 * @property-read $theme_id
 * @property-read $storefront
 * @property-read shopProductgroupPluginConfig $plugin_config
 * @property-read shopProductgroupStyleConfig $style_config
 */
class shopProductgroupPluginEnv extends shopProductgroupImmutableStructure
{
	protected $theme_id;
	protected $storefront;
	protected $plugin_config;
	protected $style_config;

	public function __construct(
		$theme_id,
		$storefront,
		shopProductgroupPluginConfig $plugin_config,
		shopProductgroupStyleConfig $style_config
	)
	{
		$this->theme_id = $theme_id;
		$this->storefront = $storefront;
		$this->plugin_config = $plugin_config;
		$this->style_config = $style_config;
	}
}