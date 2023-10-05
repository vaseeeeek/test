<?php

class shopEditApp
{
	public $app_id;
	public $name;

	public $app_info = array();
	/** @var shopEditStorefront[] */
	public $storefronts = array();
	/** @var waTheme[] */
	public $themes = array();

	public function __construct($app_id, $app_info)
	{
		$this->app_id = $app_id;

		if (is_array($app_info))
		{
			$this->app_info = $app_info;

			$this->name = $app_info['name'];
		}
	}

	public function assoc()
	{
		return array(
			'app_id' => $this->app_id,
			'app_info' => $this->app_info,
			'name' => $this->name,
			'storefronts' => array_map(array($this, 'toAssoc'), $this->storefronts),
			'themes' => array_map(array($this, 'themeToAssoc'), $this->themes),
		);
	}

	private function toAssoc(shopEditStorefront $storefront)
	{
		return $storefront->assoc();
	}

	private function themeToAssoc(waTheme $theme)
	{
		return array(
			'id' => $theme->id,
			'name' => $theme->getName(),
		);
	}
}