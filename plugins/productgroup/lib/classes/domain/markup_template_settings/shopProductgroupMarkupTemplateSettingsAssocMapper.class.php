<?php

class shopProductgroupMarkupTemplateSettingsAssocMapper
{
	public function settingsToAssoc(shopProductgroupMarkupTemplateSettings $settings)
	{
		return [
			'groups_block_template' => $settings->groups_block_template,
			'is_groups_block_template_default' => $settings->is_groups_block_template_default,
			'simple_group_template' => $settings->simple_group_template,
			'is_simple_group_template_default' => $settings->is_simple_group_template_default,
			'photo_group_template' => $settings->photo_group_template,
			'is_photo_group_template_default' => $settings->is_photo_group_template_default,
			'color_group_template' => $settings->color_group_template,
			'is_color_group_template_default' => $settings->is_color_group_template_default,
		];
	}

	public function buildSettingsByAssoc(array $assoc)
	{
		$settings = new shopProductgroupMarkupTemplateSettings(
			$assoc['groups_block_template'],
			$assoc['is_groups_block_template_default'],
			$assoc['simple_group_template'],
			$assoc['is_simple_group_template_default'],
			$assoc['photo_group_template'],
			$assoc['is_photo_group_template_default'],
			$assoc['color_group_template'],
			$assoc['is_color_group_template_default']
		);

		return $settings;
	}
}