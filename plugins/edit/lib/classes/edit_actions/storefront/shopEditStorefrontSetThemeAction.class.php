<?php

class shopEditStorefrontSetThemeAction extends shopEditLoggedAction
{
	private $app_id;
	private $storefront_selection;
	private $theme_id;
	private $theme_mobile_id;

	public function __construct($app_id, shopEditStorefrontSelection $storefront_selection, $theme_id, $theme_mobile_id)
	{
		$this->app_id = $app_id;
		$this->storefront_selection = $storefront_selection;
		$this->theme_id = $theme_id;
		$this->theme_mobile_id = $theme_mobile_id;

		parent::__construct();
	}

	protected function execute()
	{
		$storage = new shopEditStorefrontStorage();

		$storefront_selection = $this->storefront_selection;

		/** @var shopEditStorefront[] $storefronts */
		$storefronts = array();
		if ($storefront_selection->mode == shopEditStorefrontSelection::MODE_ALL)
		{
			$storefronts = $storage->getAllAppStorefronts($this->app_id);
		}
		elseif ($storefront_selection->mode == shopEditStorefrontSelection::MODE_SELECTED)
		{
			$storefronts = $storage->getAppStorefronts($this->app_id, $storefront_selection->storefronts);
		}

		$theme_ids = array();
		foreach (wa()->getThemes($this->app_id) as $theme)
		{
			$theme_ids[$theme->id] = $theme->id;
		}

		$update_theme_id = array_key_exists($this->theme_id, $theme_ids);
		$update_theme_mobile_id = array_key_exists($this->theme_mobile_id, $theme_ids);

		$theme_id_changed_on_storefronts = $theme_mobile_id_changed_on_storefronts = array();
		foreach ($storefronts as $storefront)
		{
			if ($update_theme_id)
			{
				if ($storefront->theme != $this->theme_id)
				{
					$theme_id_changed_on_storefronts[$storefront->name] = $storefront->name;
				}

				$storefront->theme = $this->theme_id;
			}

			if ($update_theme_mobile_id)
			{
				if ($storefront->theme_mobile != $this->theme_mobile_id)
				{
					$theme_mobile_id_changed_on_storefronts[$storefront->name] = $storefront->name;
				}

				$storefront->theme_mobile = $this->theme_mobile_id;
			}
		}

		$storage->updateAppStorefronts($this->app_id, $storefronts);

		return array(
			'storefront_selection' => $this->storefront_selection,
			'app_id' => $this->app_id,
			'theme_id' => $this->theme_id,
			'theme_mobile_id' => $this->theme_mobile_id,
			'theme_id_changed_on_storefronts' => array_values($theme_id_changed_on_storefronts),
			'theme_mobile_id_changed_on_storefronts' => array_values($theme_mobile_id_changed_on_storefronts),
		);
	}

	protected function getAction()
	{
		return $this->action_options->STOREFRONT_SET_THEME;
	}
}