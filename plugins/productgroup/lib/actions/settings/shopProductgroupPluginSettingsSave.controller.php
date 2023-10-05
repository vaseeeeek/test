<?php

class shopProductgroupPluginSettingsSaveController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$json_settings = waRequest::post('settings');
		$settings = json_decode($json_settings, true);

		$this->savePluginSettings(
			ifset($settings, 'storefront_plugin_settings', null),
			ifset($settings, 'storefronts_with_personal_settings', null),
			ifset($settings, 'storefronts_to_clear', null)
		);

		$this->saveGroups(
			ifset($settings, 'groups', []),
			ifset($settings, 'group_ids_to_delete', [])
		);

		$this->saveMarkupTemplateSettings(
			ifset($settings, 'markup_template_theme_settings', null)
		);

		$this->saveBaseStyleSettings(
			ifset($settings, 'base_style_theme_settings', null)
		);

		$this->saveMarkupStyleSettings(
			ifset($settings, 'markup_style_theme_storefront_settings', null),
			ifset($settings, 'theme_storefronts_with_personal_style_settings', null),
			ifset($settings, 'theme_storefront_personal_style_settings_to_clear', null)
		);

		$this->clearCache();

		$this->response['state'] = $this->getState();

		$this->response['success'] = true;
	}

	private function savePluginSettings(
		$storefront_plugin_settings,
		$storefronts_with_personal_settings,
		$storefronts_to_clear
	)
	{
		$settings_storage = shopProductgroupPluginContext::getInstance()->getStorefrontSettingsStorage();
		$settings_mapper = new shopProductgroupSettingsMapper();

		foreach ($storefront_plugin_settings as $storefront => $settings_assoc)
		{
			if (
				$storefront !== shopProductgroupGeneralStorefront::NAME
				&& (
					!array_key_exists($storefront, $storefronts_with_personal_settings)
					|| array_key_exists($storefront, $storefronts_to_clear)
				)
			)
			{
				continue;
			}

			$settings = $settings_storage->getSettings($storefront);

			$settings_mapper->mapToObject($settings, $settings_assoc);

			$settings_storage->saveSettings($storefront, $settings);
		}

		foreach (array_keys($storefronts_to_clear) as $storefront)
		{
			$settings_storage->deleteSettings($storefront);
		}
	}

	private function saveGroups($groups, $group_ids_to_delete)
	{
		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();
		$group_settings_storage = shopProductgroupPluginContext::getInstance()->getGroupSettingsStorage();

		$sort = 0;
		foreach ($groups as $group_assoc)
		{
			$group_to_save = new shopProductgroupGroup(
				intval($group_assoc['id']),
				$group_assoc['name'],
				$group_assoc['markup_template_id'],
				$group_assoc['is_shown'],
				$group_assoc['related_feature_id'],
				$sort++
			);

			$group_id = $group_to_save->id;

			if ($group_id > 0)
			{
				$group_storage->updateGroup($group_id, $group_to_save);
			}
			else
			{
				$new_group = $group_storage->addGroup($group_to_save);
				$group_id = $new_group->id;
			}

			foreach ($group_assoc['scope_settings'] as $scope => $group_settings_assoc)
			{
				$group_settings = new shopProductgroupGroupSettings(
					$group_settings_assoc['is_shown'],
					$group_settings_assoc['show_in_stock_only'],
					$group_settings_assoc['show_on_primary_product_only'],
					$group_settings_assoc['show_header'],
					$group_settings_assoc['current_product_first'],
					$group_settings_assoc['image_size']
				);

				$group_settings_storage->storeGroupScopeSettings($group_id, $scope, $group_settings);
			}
		}

		foreach ($group_ids_to_delete as $group_id_to_delete)
		{
			$group_storage->deleteById($group_id_to_delete);
		}
	}

	private function saveMarkupTemplateSettings($markup_template_theme_settings)
	{
		$context = shopProductgroupPluginContext::getInstance();
		$mapper = $context->getMarkupTemplateSettingsAssocMapper();
		$markup_template_settings_storage = $context->getMarkupTemplateSettingsStorage();

		foreach ($markup_template_theme_settings as $theme_id => $settings_assoc)
		{
			$new_settings = $mapper->buildSettingsByAssoc($settings_assoc);

			$markup_template_settings_storage->storeSettingsForTheme($theme_id, $new_settings);
		}
	}

	private function saveBaseStyleSettings($base_style_theme_settings)
	{
		$style_file_storage = shopProductgroupPluginContext::getInstance()->getStyleFileStorage();

		foreach ($base_style_theme_settings as $theme_id => $base_style_setting_assoc)
		{
			if ($base_style_setting_assoc['is_default'])
			{
				$style_file_storage->deleteThemeBaseStyle($theme_id);
			}
			else
			{
				$style_file_storage->storeThemeBaseStyleContent($theme_id, $base_style_setting_assoc['content']);
			}
		}
	}

	private function saveMarkupStyleSettings(
		$markup_style_theme_storefront_settings_assoc,
		$theme_storefronts_with_personal_style_settings,
		$theme_storefront_personal_style_settings_to_clear
	)
	{
		$style_settings_storage = shopProductgroupPluginContext::getInstance()->getMarkupStyleSettingsStorage();
		$style_settings_mapper = shopProductgroupPluginContext::getInstance()->getMarkupStyleSettingsAssocMapper();

		foreach ($markup_style_theme_storefront_settings_assoc as $theme_id => $storefront_settings)
		{
			foreach ($storefront_settings as $storefront => $style_setting_assoc)
			{
				if (
					$storefront !== shopProductgroupGeneralStorefront::NAME
					&& (
						!ifset($theme_storefronts_with_personal_style_settings, $theme_id, $storefront, false)
						|| ifset($theme_storefront_personal_style_settings_to_clear, $theme_id, $storefront, false)
					)
				)
				{
					continue;
				}

				$style_setting = new shopProductgroupMarkupStyleSettings();
				$style_settings_mapper->mapToObject($style_setting, $style_setting_assoc);

				$style_settings_storage->saveThemeStorefrontSettings($theme_id, $storefront, $style_setting);
			}
		}

		foreach ($theme_storefront_personal_style_settings_to_clear as $theme_id => $storefronts_to_clear)
		{
			foreach ($storefronts_to_clear as $storefront => $_)
			{
				$style_settings_storage->deleteThemeStorefrontSettings($theme_id, $storefront);
			}
		}
	}

	private function getState()
	{
		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();
		$group_settings_storage = shopProductgroupPluginContext::getInstance()->getGroupSettingsStorage();

		$groups_assoc = [];
		foreach ($group_storage->getAll() as $group)
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

		return [
			'groups' => $groups_assoc,
		];
	}

	private function clearCache()
	{
		$cache = new shopProductgroupProductsGroupsCache();

		$cache->clearAll();
	}
}