<?php

class shopBrandPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$view = $this->view;

		$themes = $this->getThemes();
		$first_theme = count($themes) ? reset($themes) : null;

		$state = array(
			'settings' => $this->getSettings(),
			'brands_page_template_layout' => $this->getBrandsPageMeta(),
			'pages' => $this->getPages(),
			'page_storefronts_with_personal' => $this->getPageStorefrontsWithPersonal(),
			'brands_page_storefronts_with_personal' => $this->getBrandsPageStorefrontsWithPersonal(),
			'brand_fields' => $this->getBrandFields(),
			'storefronts' => shopBrandStorefront::getAll(),
			'features' => $this->getFeatures(),
			'template_variables' => $this->getTemplateVariables(),
			'action_theme_template' => $this->getActionThemeTemplates($first_theme),
			'modified_action_theme' => $this->getModifiedActionTheme(),
			'themes' => $themes,
		);

		$view->assign('state', $state);


		$info = wa('shop')->getConfig()->getPluginInfo('brand');
		$this->view->assign('asset_version', waSystemConfig::isDebug() ? time() : $info['version']);
	}

	private function getSettings()
	{
		$storage = new shopBrandSettingsStorage();

		return $storage->getSettings()->assoc();
	}

	private function getBrandsPageMeta()
	{
		$storage = new shopBrandBrandsPageTemplateLayoutStorage();

		return $storage->getMeta(shopBrandStorefront::GENERAL)->assoc();
	}

	private function getPages()
	{
		$storage = new shopBrandPageStorage();

		$pages_assoc = array();
		foreach ($storage->getAll() as $page)
		{
			$pages_assoc[] = $page->assoc();
		}

		return $pages_assoc;
	}

	private function getFeatures()
	{
		$model = new shopFeatureModel();

		$options = array();
		foreach ($model->getAll() as $feature)
		{
			$options[$feature['id']] = $feature;
		}

		return $options;
	}

	private function getBrandFields()
	{
		$storage = new shopBrandBrandFieldStorage();

		$fields = $storage->getAllFields();

		return $fields;
	}

	private function getPageStorefrontsWithPersonal()
	{
		$model = new shopBrandStorefrontTemplateLayoutModel();

		$storefronts = $model->select('DISTINCT storefront')
			->where('brand_id = :brand_id', array('brand_id' => shopBrandStorefrontTemplateLayoutStorage::DEFAULT_BRAND_ID))
			->fetchAll('storefront');

		return array_keys($storefronts);
	}

	private function getBrandsPageStorefrontsWithPersonal()
	{
		$model = new shopBrandSettingsModel();

		$storefronts = $model->select('DISTINCT storefront')->fetchAll('storefront');

		return array_keys($storefronts);
	}

	private function getTemplateVariables()
	{
		$variables = new shopBrandTemplateVariables();

		return $variables->getViewState();
	}

	private function getActionThemeTemplates($first_theme)
	{
		$action_theme_template = array();
		$action_theme_with_personal = array();

		if (!$first_theme)
		{
			return array($action_theme_template, $action_theme_with_personal);
		}

		$theme_id = $first_theme['id'];

		$storage = new shopBrandActionThemeTemplateStorage();
		$action_theme_template = $storage->getThemeTemplatesState($theme_id);

		return $action_theme_template;
	}

	private function getThemes()
	{
		$app = 'shop';
		$themes = array();

		$first_theme_id = null;
		foreach (wa()->getRouting()->getByApp('shop') as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if (!isset($route['theme']))
				{
					continue;
				}
				$first_theme_id = $route['theme'];

				break 2;
			}
		}

		foreach (wa()->getThemes($app) as $theme)
		{
			$theme_assoc = array(
				'id' => $theme->id,
				'name' => $theme->getName(),
			);

			if ($first_theme_id == $theme->id)
			{
				array_unshift($themes, $theme_assoc);
			}
			else
			{
				$themes[] = $theme_assoc;
			}
		}

		return $themes;
	}

	private function getModifiedActionTheme()
	{
		$storage = new shopBrandActionThemeTemplateStorage();

		return $storage->getModifiedActionTheme();
	}
}