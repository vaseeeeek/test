<?php

class shopEditAppStorage
{
	public function getAll()
	{
		$apps = wa()->getApps();

		/** @var shopEditApp[] $apps_storefronts */
		$apps_storefronts = array();
		foreach ($apps as $app_id => $app_info)
		{
			if (
				$app_id == 'helpdesk'
				|| !is_array($app_info)
				|| !array_key_exists('themes', $app_info) || !$app_info['themes']
				|| !array_key_exists('frontend', $app_info) || !$app_info['frontend']
			)
			{
				continue;
			}

			$app = new shopEditApp($app_id, $app_info);

			foreach (wa()->getRouting()->getByApp($app_id) as $domain => $routes)
			{
				foreach ($routes as $route)
				{
					$app->storefronts[] = new shopEditStorefront($domain, $route);
				}
			}

			foreach (wa()->getThemes($app_id) as $theme)
			{
				$app->themes[] = $theme;
			}

			$apps_storefronts[$app_id] = $app;
		}

		return $apps_storefronts;
	}
}