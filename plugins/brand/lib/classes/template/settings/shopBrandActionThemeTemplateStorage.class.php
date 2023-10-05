<?php

class shopBrandActionThemeTemplateStorage
{
	const ACTION_BRANDS = 'BRANDS';
	const ACTION_BRAND_PAGES_TABS = 'BRAND_PAGES_TABS';
	const ACTION_BRAND_FRONTEND_NAV = 'BRAND_FRONTEND_NAV';
	const ACTION_BRAND_PAGE = 'BRAND_PAGE';
	const ACTION_BRAND_PAGE_CATALOG = 'BRAND_PAGE_CATALOG';
	const ACTION_BRAND_PAGE_CATALOG_HEADER = 'BRAND_PAGE_CATALOG_HEADER';
	const ACTION_BRAND_PAGE_REVIEWS = 'BRAND_PAGE_REVIEWS';
	const ACTION_BRAND_PAGE_INFO = 'BRAND_PAGE_INFO';
	const ACTION_BRAND_GROUPED_BRANDS = 'BRAND_GROUPED_BRANDS';
	const ACTION_BRAND_SEARCH_BRANDS = 'BRAND_SEARCH_BRANDS';

	public function getActions()
	{
		return array(
			self::ACTION_BRANDS => self::ACTION_BRANDS,
			self::ACTION_BRAND_PAGES_TABS => self::ACTION_BRAND_PAGES_TABS,
			self::ACTION_BRAND_FRONTEND_NAV => self::ACTION_BRAND_FRONTEND_NAV,
			self::ACTION_BRAND_PAGE => self::ACTION_BRAND_PAGE,
			self::ACTION_BRAND_PAGE_CATALOG => self::ACTION_BRAND_PAGE_CATALOG,
			self::ACTION_BRAND_PAGE_CATALOG_HEADER => self::ACTION_BRAND_PAGE_CATALOG_HEADER,
			self::ACTION_BRAND_PAGE_REVIEWS => self::ACTION_BRAND_PAGE_REVIEWS,
			self::ACTION_BRAND_PAGE_INFO => self::ACTION_BRAND_PAGE_INFO,
			self::ACTION_BRAND_GROUPED_BRANDS => self::ACTION_BRAND_GROUPED_BRANDS,
			self::ACTION_BRAND_SEARCH_BRANDS => self::ACTION_BRAND_SEARCH_BRANDS,
		);
	}

	public function getThemeTemplatesState($theme_id)
	{
		$action_theme_template = $this->getThemeTemplates($theme_id);
		$action_theme_template_state = array();
		foreach ($action_theme_template as $action => $theme_template)
		{
			$action_theme_template_state[$action] = array();

			/** @var shopBrandActionTemplateSettings $template_settings */
			foreach ($theme_template as $theme_id => $template_settings)
			{
				$action_theme_template_state[$action][$theme_id] = $this->settingsState($template_settings);
			}
		}

		return $action_theme_template_state;
	}

	public function getThemeTemplates($theme_id)
	{
		$theme = $this->getTheme($theme_id);

		$action_theme_template = array();

		foreach ($this->getActions() as $action)
		{
			$template = $this->getActionTemplate($action, $theme);
			$template_settings = new shopBrandActionTemplateSettings($template);

			$action_theme_template[$action] = array(
				$theme_id => $template_settings,
			);
		}

		return $action_theme_template;
	}

	public function getActionThemeTemplateSettings($action, $theme_id)
	{
		$theme = $this->getTheme($theme_id);
		$template = $this->getActionTemplate($action, $theme);

		return new shopBrandActionTemplateSettings($template);
	}

	public function getActionThemeTemplateSettingsState($action, $theme_id)
	{
		$template_settings = $this->getActionThemeTemplateSettings($action, $theme_id);

		return $this->settingsState($template_settings);
	}

	public function setActionTheme($action_theme_template)
	{
		$themes = array();

		foreach ($action_theme_template as $action => $theme_templates_state)
		{
			foreach ($theme_templates_state as $theme_id => $template_settings_state)
			{
				if (!array_key_exists($theme_id, $themes))
				{
					$themes[$theme_id] = $this->getTheme($theme_id);
				}
				$theme = $themes[$theme_id];

				$action_template = $this->getActionTemplate($action, $theme);
				$action_template_settings = new shopBrandActionTemplateSettings($action_template);

				$types = array(
					shopBrandActionTemplateSettings::FILE_TYPE_SMARTY,
					shopBrandActionTemplateSettings::FILE_TYPE_JS,
					shopBrandActionTemplateSettings::FILE_TYPE_CSS,
				);

				foreach ($types as $type)
				{
					$type_key = $type . '_content';

					$content_to_save = $template_settings_state[$type_key]['is_custom']
						? $template_settings_state[$type_key]['content']
						: '';

					$action_template_settings->saveContent($content_to_save, $type);
				}
			}
		}
	}

	public function getModifiedActionTheme()
	{
		$modified_action_theme = array();

		foreach ($this->getActions() as $action)
		{
			foreach (wa()->getThemes('shop') as $theme)
			{
				$theme_id = $theme->id;

				$action_template = $this->getActionTemplate($action, $theme);
				$action_template_settings = new shopBrandActionTemplateSettings($action_template);

				$types = array(
					shopBrandActionTemplateSettings::FILE_TYPE_SMARTY,
					shopBrandActionTemplateSettings::FILE_TYPE_JS,
					shopBrandActionTemplateSettings::FILE_TYPE_CSS,
				);

				foreach ($types as $type)
				{
					if ($action_template_settings->isCustom($type))
					{
						if (!array_key_exists($action, $modified_action_theme))
						{
							$modified_action_theme[$action] = array();
						}
						$modified_action_theme[$action][$theme_id] = true;

						break;
					}
				}
			}
		}

		return $modified_action_theme;
	}

	/**
	 * @param string $action
	 * @param waTheme $theme
	 * @return shopBrandActionTemplate
	 * @throws waException
	 */
	private function getActionTemplate($action, waTheme $theme)
	{
		if ($action == self::ACTION_BRANDS)
		{
			return new shopBrandBrandsActionTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_FRONTEND_NAV)
		{
			return new shopBrandFrontendNavTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_PAGES_TABS)
		{
			return new shopBrandBrandPagesTabsTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_PAGE)
		{
			return new shopBrandBrandPageActionTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_PAGE_CATALOG)
		{
			return new shopBrandBrandCatalogActionThemeTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_PAGE_CATALOG_HEADER)
		{
			return new shopBrandCatalogHeaderTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_PAGE_REVIEWS)
		{
			return new shopBrandBrandReviewsActionTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_PAGE_INFO)
		{
			return new shopBrandBrandInfoActionTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_GROUPED_BRANDS)
		{
			return new shopBrandGroupedBrandsTemplate($theme);
		}
		elseif ($action == self::ACTION_BRAND_SEARCH_BRANDS)
		{
			return new shopBrandSearchBrandsTemplate($theme);
		}
		else
		{
			throw new waException();
		}
	}

	/**
	 * @param $theme_id
	 * @return waTheme
	 * @throws waException
	 */
	private function getTheme($theme_id)
	{
		$themes = wa()->getThemes('shop');

		if (!array_key_exists($theme_id, $themes))
		{
			throw new waException();
		}

		return $themes[$theme_id];
	}

	private function settingsState(shopBrandActionTemplateSettings $template_settings)
	{
		$state = array();

		$types = array(
			shopBrandActionTemplateSettings::FILE_TYPE_SMARTY,
			shopBrandActionTemplateSettings::FILE_TYPE_JS,
			shopBrandActionTemplateSettings::FILE_TYPE_CSS,
		);
		foreach ($types as $type)
		{
			$state[$type . '_content'] = array(
				'is_used' => $template_settings->isUsed($type),
				'content' => $template_settings->getContent($type),
				'is_theme_only' => $template_settings->isThemeOnly($type),
				'theme_file_name' => $template_settings->getThemeFileName($type), // todo
				'theme_default_file_name' => $template_settings->getThemeDefaultFileName($type), // todo
				'default_content' => $template_settings->getDefaultContent($type),
			);
		}

		return $state;
	}
}