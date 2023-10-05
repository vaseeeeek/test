<?php


class shopRegionsBackendLayout extends shopBackendLayout
{
	public function display()
	{
		waSystem::popActivePlugin();

		try
		{
			parent::display();
		}
		catch (Exception $e)
		{}

		waSystem::pushActivePlugin('regions', 'shop');
	}

	protected function getTemplate()
	{
		$shop_layout = new shopBackendLayout();

		return $shop_layout->getTemplate();
	}
}