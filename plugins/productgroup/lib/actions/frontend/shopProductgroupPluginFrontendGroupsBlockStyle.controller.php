<?php

class shopProductgroupPluginFrontendGroupsBlockStyleController extends waController
{
	private $style_content = '';

	public function execute()
	{
		/** @var shopProductgroupPlugin $plugin */
		$plugin = wa('shop')->getPlugin('productgroup');

		$theme_id = $plugin->getPluginEnv()->theme_id;
		$storefront = $plugin->getPluginEnv()->storefront;

		if (!$theme_id)
		{
			throw new waException();
		}

		$styles_compiler = new shopProductgroupStyleCompiler();
		$this->style_content = $styles_compiler->compile($theme_id, $storefront);
	}

	public function run($params = null)
	{
		parent::run($params);

		$this->display();
	}

	public function display()
	{
		$this->getResponse()->addHeader('Content-Type', 'text/css');
		$this->getResponse()->sendHeaders();

		echo $this->style_content;
	}
}