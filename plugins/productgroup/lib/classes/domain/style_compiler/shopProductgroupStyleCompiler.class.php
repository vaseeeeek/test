<?php

class shopProductgroupStyleCompiler
{
	public function compile($theme_id, $storefront)
	{
		$style_registry = new shopProductgroupStyleRegistry();

		$base_style = $style_registry->getBaseStyleContent($theme_id);
		$custom_style_template = $style_registry->getCustomStyleTemplateContent();


		$style_config_storage = new shopProductgroupStyleConfigStorage();
		$custom_style_config = $style_config_storage->getConfig($theme_id, $storefront);

		$custom_style = $this->renderCustomStyle($custom_style_template, $custom_style_config);

		return $base_style . "\n" . $custom_style;
	}

	private function renderCustomStyle($custom_style_template, shopProductgroupStyleConfig $custom_style_config)
	{
		$view = new shopProductgroupWaView(wa()->getView());

		$view->assign([
			'style_config' => $custom_style_config,
		]);

		return $view->fetch('string:' . $custom_style_template);
	}
}