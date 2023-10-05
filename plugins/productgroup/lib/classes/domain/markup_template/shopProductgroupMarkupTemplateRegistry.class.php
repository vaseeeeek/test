<?php

class shopProductgroupMarkupTemplateRegistry
{
	private static $templates = [];

	/**
	 * @param string $theme_id
	 * @return shopProductgroupMarkupTemplate
	 */
	public function getGroupsBlockTemplate($theme_id)
	{
		return $this->getTemplate(
			shopProductgroupMarkupTemplateId::GROUPS_BLOCK,
			$theme_id,
			'GroupsBlock.html',
			'productgroup_plugin.groups_block.html'
		);
	}

	/**
	 * @param string $theme_id
	 * @return shopProductgroupMarkupTemplate
	 */
	public function getSimpleGroupTemplate($theme_id)
	{
		return $this->getTemplate(
			shopProductgroupMarkupTemplateId::SIMPLE_GROUP,
			$theme_id,
			'GroupsBlock.SimpleGroup.html',
			'productgroup_plugin.groups_block.simple_group.html'
		);
	}

	/**
	 * @param string $theme_id
	 * @return shopProductgroupMarkupTemplate
	 */
	public function getPhotoGroupTemplate($theme_id)
	{
		return $this->getTemplate(
			shopProductgroupMarkupTemplateId::PHOTO_GROUP,
			$theme_id,
			'GroupsBlock.PhotoGroup.html',
			'productgroup_plugin.groups_block.photo_group.html'
		);
	}

	/**
	 * @param string $theme_id
	 * @return shopProductgroupMarkupTemplate
	 */
	public function getColorGroupTemplate($theme_id)
	{
		return $this->getTemplate(
			shopProductgroupMarkupTemplateId::COLOR_GROUP,
			$theme_id,
			'GroupsBlock.ColorGroup.html',
			'productgroup_plugin.groups_block.color_group.html'
		);
	}

	private function getTemplate(
		$template_id,
		$theme_id,
		$plugin_file_name,
		$theme_file_name
	)
	{
		$key = "{$template_id}/{$theme_id}";

		if (!isset(self::$templates[$key]))
		{
			self::$templates[$key] = new shopProductgroupMarkupTemplate(
				$template_id,
				$theme_id,
				$plugin_file_name,
				$theme_file_name
			);
		}

		return self::$templates[$key];
	}
}