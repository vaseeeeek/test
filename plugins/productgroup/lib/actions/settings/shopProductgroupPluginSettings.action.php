<?php

class shopProductgroupPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$all_wa_themes = $this->getAllWaThemes();
		$first_theme_id = $this->getFirstThemeId();

		$loaded_theme_ids = count($all_wa_themes) > 0 && isset($all_wa_themes[$first_theme_id])
			? [$first_theme_id]
			: [];

		$this->view->assign('state', [
			'storefront_plugin_settings' => $this->getStorefrontSettings(),
			'storefronts_with_personal_settings' => $this->getStorefrontsWithPersonalSettings(),

			'groups' => $this->getGroups(),

			'markup_template_theme_settings' => $this->getMarkupTemplateSettings($loaded_theme_ids),
			'theme_ids_with_personal_settings' => $this->themeIdsWithPersonalSettings(),

			'base_style_theme_settings' => $this->getBaseStyleThemeSettings($loaded_theme_ids),

			'markup_style_theme_storefront_settings' => $this->getMarkupStyleSettings($loaded_theme_ids),
			'theme_storefronts_with_personal_style_settings' => $this->getThemeStorefrontWithPersonalStyleSettings(),


			'storefronts' => $this->getAllStorefronts(),
			'themes' => $this->getAllThemesAssoc(),
			'templates_meta' => $this->getTemplatesMeta(),
			'color_features' => $this->getColorFeatures(),
			'image_sizes' => $this->getImageSizes(),
			'group_helper_template' => '{shopProductgroupViewHelper::getSpecificGroupsBlock($product.id, %GROUP_ID%)}',
			'category_group_helper_template' => '{shopProductgroupViewHelper::getCategoryProductBlock($product.id, %GROUP_ID%)}',
		]);
	}

	private function getStorefrontSettings()
	{
		$settings_storage =  shopProductgroupPluginContext::getInstance()->getStorefrontSettingsStorage();
		$settings_mapper = new shopProductgroupSettingsMapper();

		$storefront = shopProductgroupGeneralStorefront::NAME;

		return [
			[
				'storefront' => $storefront,
				'settings' => $settings_mapper->toAssoc($settings_storage->getSettings($storefront)),
			]
		];
	}

	private function getStorefrontsWithPersonalSettings()
	{
		$settings_storage = shopProductgroupPluginContext::getInstance()->getStorefrontSettingsStorage();

		return $settings_storage->getStorefrontsWithPersonalSettings();
	}

	private function getGroups()
	{
		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();
		$group_settings_storage = shopProductgroupPluginContext::getInstance()->getGroupSettingsStorage();
		$groups = $group_storage->getAll();

		$groups_assoc = [];
		foreach ($groups as $group)
		{
			$group_assoc = $group->toAssoc();

			$group_assoc['scope_settings'] = [];

			foreach (shopProductgroupGroupSettingsScope::getScopes() as $scope)
			{
				$group_assoc['scope_settings'][] = [
					'scope' => $scope,
					'settings' => $group_settings_storage->getGroupScopeSettings($group->id, $scope)->toAssoc()
				];
			}

			$groups_assoc[] = $group_assoc;
		}

		return $groups_assoc;
	}

	private function getMarkupTemplateSettings($theme_ids)
	{
		$context = shopProductgroupPluginContext::getInstance();
		$markup_template_settings_storage = $context->getMarkupTemplateSettingsStorage();
		$assoc_mapper = $context->getMarkupTemplateSettingsAssocMapper();

		$result = [];

		foreach ($theme_ids as $theme_id)
		{
			$settings = $markup_template_settings_storage->getSettingsForTheme($theme_id);

			$settings_assoc = $assoc_mapper->settingsToAssoc($settings);

			$result[] = [
				'theme_id' => $theme_id,
				'settings' => $settings_assoc,
			];
		}

		return $result;
	}

	private function getMarkupStyleSettings($theme_ids)
	{
		$context = shopProductgroupPluginContext::getInstance();

		$markup_style_settings_storage = $context->getMarkupStyleSettingsStorage();
		$assoc_mapper = $context->getMarkupStyleSettingsAssocMapper();

		$storefront = shopProductgroupGeneralStorefront::NAME;
		$result = [];
		foreach ($theme_ids as $theme_id)
		{
			$settings = $markup_style_settings_storage->getThemeStorefrontSettings($theme_id, $storefront);

			$result[] = [
				'theme_id' => $theme_id,
				'storefront' => $storefront,
				'settings' => $assoc_mapper->toAssoc($settings),
			];
		}

		return $result;
	}

	private function getThemeStorefrontWithPersonalStyleSettings()
	{
		// todo

		return [
			[
				'theme_id' => 'default',
				'storefronts' => [shopProductgroupGeneralStorefront::NAME],
			]
		];
	}

	private function themeIdsWithPersonalSettings()
	{
		$markup_template_settings_storage = shopProductgroupPluginContext::getInstance()->getMarkupTemplateSettingsStorage();

		return $markup_template_settings_storage->getThemeIdsWithPersonalSettings();
	}

	private function getBaseStyleThemeSettings($loaded_theme_ids)
	{
		$result = [];

		$style_file_storage = shopProductgroupPluginContext::getInstance()->getStyleFileStorage();

		foreach ($loaded_theme_ids as $theme_id)
		{
			$custom_style_exist = $style_file_storage->isThemeBaseStyleExist($theme_id);

			$result[] = [
				'theme_id' => $theme_id,
				'settings' => [
					'is_default' => !$custom_style_exist,
					'content' => $custom_style_exist
						? $style_file_storage->getThemeBaseStyleContent($theme_id)
						: $style_file_storage->getPluginBaseStyleContent(),
				],
			];
		}

		return $result;
	}

	/**
	 * @return waTheme[]
	 * @throws waException
	 */
	private function getAllWaThemes()
	{
		return wa('shop')->getThemes('shop');
	}

	/**
	 * @return string|null
	 * @throws waException
	 */
	private function getFirstThemeId()
	{
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

		return $first_theme_id;
	}

	private function getAllStorefronts()
	{
		$routing = wa()->getRouting();
		$domains = $routing->getByApp('shop');
		$storefronts = [];

		foreach ($domains as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if ((!method_exists($routing, 'isAlias') || !$routing->isAlias($domain)) && isset($route['url']))
				{
					$storefronts[] = $domain . '/' . $route['url'];
				}
			}
		}

		return $storefronts;
	}

	private function getAllThemesAssoc()
	{
		$first_theme_id = $this->getFirstThemeId();

		$first_theme_assoc = null;
		$themes_assoc = [];
		$themes_without_storefronts_assoc = [];

		foreach ($this->getAllWaThemes() as $wa_theme)
		{
			$storefronts_with_theme = [];
			$used = $wa_theme->used;
			if (!is_array($used))
			{
				$used = [];
			}

			foreach ($used as $use)
			{
				$storefronts_with_theme["{$use['domain']}/{$use['url']}"] = true;
			}

			$theme_assoc = [
				'id' => $wa_theme->id,
				'name' => $wa_theme->getName(),
				'type' => $wa_theme->type,
				'storefronts' => array_keys($storefronts_with_theme),
			];

			if ($first_theme_id === $wa_theme->id)
			{
				$first_theme_assoc = $theme_assoc;
			}
			elseif (count($storefronts_with_theme) === 0)
			{
				$themes_without_storefronts_assoc[] = $theme_assoc;
			}
			else
			{
				$themes_assoc[] = $theme_assoc;
			}
		}

		if ($first_theme_assoc)
		{
			array_unshift($themes_assoc, $first_theme_assoc);
		}

		return array_merge($themes_assoc, $themes_without_storefronts_assoc);
	}

	private function getTemplatesMeta()
	{
		$context = shopProductgroupPluginContext::getInstance();

		$template_registry = $context->getMarkupTemplateRegistry();
		$template_file_storage = $context->getMarkupTemplateFileStorage();
		$style_file_storage = $context->getStyleFileStorage();

		$groups_block_template = $template_registry->getGroupsBlockTemplate('');
		$simple_group_template = $template_registry->getSimpleGroupTemplate('');
		$photo_group_template = $template_registry->getPhotoGroupTemplate('');
		$color_group_template = $template_registry->getColorGroupTemplate('');

		return [
			[
				'template_id' => $groups_block_template->template_id,
				'theme_file_name' => $groups_block_template->theme_file_name,
				'plugin_template' => $template_file_storage->getPluginContent($groups_block_template),
				'type' => 'smarty',
			],
			[
				'template_id' => $simple_group_template->template_id,
				'theme_file_name' => $simple_group_template->theme_file_name,
				'plugin_template' => $template_file_storage->getPluginContent($simple_group_template),
				'type' => 'smarty',
			],
			[
				'template_id' => $photo_group_template->template_id,
				'theme_file_name' => $photo_group_template->theme_file_name,
				'plugin_template' => $template_file_storage->getPluginContent($photo_group_template),
				'type' => 'smarty',
			],
			[
				'template_id' => $color_group_template->template_id,
				'theme_file_name' => $color_group_template->theme_file_name,
				'plugin_template' => $template_file_storage->getPluginContent($color_group_template),
				'type' => 'smarty',
			],
			[
				'template_id' => shopProductgroupStyleId::BASE_STYLES,
				'theme_file_name' => $style_file_storage->getThemeBaseStyleFileName(),
				'plugin_template' => $style_file_storage->getPluginBaseStyleContent(),
				'type' => 'css',
			],
		];
	}

	private function getColorFeatures()
	{
		$feature_model = new shopFeatureModel();

		return $feature_model
			->select('id,code,name,status')
			->where('type = :type', ['type' => shopFeatureModel::TYPE_COLOR])
			->fetchAll();
	}

	private function getImageSizes()
	{
		/** @var shopConfig $shop_config */
		$shop_config = wa('shop')->getConfig();

		return array_values(array_map('strval', $shop_config->getImageSizes()));
	}
}