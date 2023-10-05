<?php

class shopProductgroupWaFrontendHeadHandler
{
	private $plugin_env;

	public function __construct(shopProductgroupPluginEnv $plugin_env)
	{
		$this->plugin_env = $plugin_env;
	}

	public function handle()
	{
		$plugin_config = $this->plugin_env->plugin_config;
		$style_config = $this->plugin_env->style_config;

		if (!$plugin_config->is_enabled || !$style_config->is_plugin_css_used)
		{
			return '';
		}

		$route_params = [
			'plugin' => 'productgroup',
			'module' => 'frontend',
			'action' => 'groupsBlockStyle',
		];
		$style_url = wa()->getRouteUrl('shop', $route_params, true);

		wa()->getResponse()->addCss($style_url . '?v=' . shopProductgroupWaHelper::getAssetVersion());

		return '';
	}
}