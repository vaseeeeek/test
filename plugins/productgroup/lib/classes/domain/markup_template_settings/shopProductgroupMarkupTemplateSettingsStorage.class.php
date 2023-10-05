<?php

class shopProductgroupMarkupTemplateSettingsStorage
{
	private $template_registry;
	private $template_path_registry;
	private $template_file_storage;

	public function __construct(
		shopProductgroupMarkupTemplateRegistry $template_registry,
		shopProductgroupMarkupTemplatePathRegistry $template_path_registry,
		shopProductgroupMarkupTemplateFileStorage $template_file_storage
	)
	{
		$this->template_registry = $template_registry;
		$this->template_path_registry = $template_path_registry;
		$this->template_file_storage = $template_file_storage;
	}

	/**
	 * @param string $theme_id
	 * @return shopProductgroupMarkupTemplateSettings|null
	 */
	public function getSettingsForTheme($theme_id)
	{
		$groups_block_template = $this->template_registry->getGroupsBlockTemplate($theme_id);
		$simple_group_template = $this->template_registry->getSimpleGroupTemplate($theme_id);
		$photo_group_template = $this->template_registry->getPhotoGroupTemplate($theme_id);
		$color_group_template = $this->template_registry->getColorGroupTemplate($theme_id);

		$settings = new shopProductgroupMarkupTemplateSettings(
			file_get_contents($this->template_path_registry->getTemplatePath($groups_block_template)),
			!$this->template_file_storage->isThemeFileExist($groups_block_template),
			file_get_contents($this->template_path_registry->getTemplatePath($simple_group_template)),
			!$this->template_file_storage->isThemeFileExist($simple_group_template),
			file_get_contents($this->template_path_registry->getTemplatePath($photo_group_template)),
			!$this->template_file_storage->isThemeFileExist($photo_group_template),
			file_get_contents($this->template_path_registry->getTemplatePath($color_group_template)),
			!$this->template_file_storage->isThemeFileExist($color_group_template)
		);

		return $settings;
	}

	public function getThemeIdsWithPersonalSettings()
	{
		// todo waThemes storage

		$theme_ids_with_personal = [];
		foreach (wa('shop')->getThemes('shop') as $wa_theme)
		{
			$theme_id = $wa_theme->id;

			$groups_block_template = $this->template_registry->getGroupsBlockTemplate($theme_id);
			$simple_group_template = $this->template_registry->getSimpleGroupTemplate($theme_id);
			$photo_group_template = $this->template_registry->getPhotoGroupTemplate($theme_id);
			$color_group_template = $this->template_registry->getColorGroupTemplate($theme_id);

			if (
				$this->template_file_storage->isThemeFileExist($groups_block_template)
				|| $this->template_file_storage->isThemeFileExist($simple_group_template)
				|| $this->template_file_storage->isThemeFileExist($photo_group_template)
				|| $this->template_file_storage->isThemeFileExist($color_group_template)
			)
			{
				$theme_ids_with_personal[] = $theme_id;
			}
		}

		return $theme_ids_with_personal;
	}

	public function storeSettingsForTheme($theme_id, shopProductgroupMarkupTemplateSettings $new_settings)
	{
		$groups_block_template = $this->template_registry->getGroupsBlockTemplate($theme_id);
		$simple_group_template = $this->template_registry->getSimpleGroupTemplate($theme_id);
		$photo_group_template = $this->template_registry->getPhotoGroupTemplate($theme_id);
		$color_group_template = $this->template_registry->getColorGroupTemplate($theme_id);

		if ($new_settings->is_groups_block_template_default)
		{
			$this->template_file_storage->deleteThemeFile($groups_block_template);
		}
		else
		{
			$this->template_file_storage->storeThemeContent($groups_block_template, $new_settings->groups_block_template);
		}

		if ($new_settings->is_simple_group_template_default)
		{
			$this->template_file_storage->deleteThemeFile($simple_group_template);
		}
		else
		{
			$this->template_file_storage->storeThemeContent($simple_group_template, $new_settings->simple_group_template);
		}

		if ($new_settings->is_photo_group_template_default)
		{
			$this->template_file_storage->deleteThemeFile($photo_group_template);
		}
		else
		{
			$this->template_file_storage->storeThemeContent($photo_group_template, $new_settings->photo_group_template);
		}

		if ($new_settings->is_color_group_template_default)
		{
			$this->template_file_storage->deleteThemeFile($color_group_template);
		}
		else
		{
			$this->template_file_storage->storeThemeContent($color_group_template, $new_settings->color_group_template);
		}

		return true;
	}
}