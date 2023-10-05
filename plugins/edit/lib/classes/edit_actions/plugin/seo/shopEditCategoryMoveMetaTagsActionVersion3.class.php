<?php

class shopEditCategoryMoveMetaTagsActionVersion3 extends shopEditCategoryMoveMetaTagsAction
{
	private $settings;
	private $destination_storefront_group_ids;

	public function __construct(shopEditCategoryMoveMetaTagsFormState $settings)
	{
		parent::__construct();

		$this->settings = $settings;
		$this->destination_storefront_group_ids = $this->getDestinationStorefrontGroups();

		if (count($this->destination_storefront_group_ids) == 0)
		{
			throw new shopEditActionInvalidParamException('destination_storefront_selection', 'Не выбрано ни одной группы витрин');
		}
	}

	protected function execute()
	{
		$settings = $this->settings;

		$model = new waModel();

		foreach ($settings->meta_fields as $field)
		{
			list($update_sql_to_execute, $clear_sql_to_execute) = $this->getTagModificationSqlQueries($field);
			if ($update_sql_to_execute === null)
			{
				continue;
			}

			$query_params = array(
				'field_id' => $field,
				'source_storefront_group_id' => $settings->source_storefront_group_id,
			);

			if ($settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED)
			{
				$query_params['category_ids'] = $settings->category_selection->category_ids;
			}

			foreach ($this->destination_storefront_group_ids as $destination_storefront_group_id)
			{
				if ($settings->source_storefront_group_id == $destination_storefront_group_id)
				{
					continue;
				}

				$query_params['destination_storefront_group_id'] = $destination_storefront_group_id;

				$model->exec($update_sql_to_execute, $query_params);
			}

			if ($settings->drop_source_tags && $clear_sql_to_execute !== null)
			{
				$model->exec($clear_sql_to_execute, $query_params);
			}
		}

		// todo подробный информативный отчет
		/*$affected = array(
			'meta_title' => array(
				'affected_storefronts' => array(
					'regions.dn/shop*' => array(
						'affected_categories_count' => 3,
						'affected_category_ids' => array(1,2,3)
					)
				),
			),
		);*/

		return array(
			'settings' => $settings->assoc(),
		);
	}

	private function getDestinationStorefrontGroups()
	{
		if ($this->settings->destination_is_general)
		{
			return array(0);
		}
		elseif ($this->settings->destination_storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_ALL_GROUPS)
		{
			$all_group_ids = array();
			foreach (shopSeoContext::getInstance()->getGroupStorefrontService()->getAll() as $storefront_group)
			{
				$all_group_ids[] = $storefront_group->getId();
			}

			return $all_group_ids;
		}
		elseif ($this->settings->destination_storefront_selection->mode == shopEditSeoStorefrontSelection::MODE_SELECTED_GROUPS)
		{
			return $this->settings->destination_storefront_selection->storefront_group_ids;
		}
		else
		{
			return array();
		}
	}

	private function getTagModificationSqlQueries($field)
	{
		$settings = $this->settings;

		$copy_sql = $clear_sql = null;

		if (
			($field == 'h1' || $field == 'seo_name' || $field == 'additional_description')
			|| (!$settings->source_is_general && !$settings->destination_is_general)
		)
		{
			$copy_sql = $this->getCopyFromCustomToCustomSql();
			$clear_sql = $this->getClearCustomTagsSql();
		}
		elseif ($settings->source_is_general && !$settings->destination_is_general)
		{
			$copy_sql = $this->getCopyFromGeneralToCustomSql($field);
			$clear_sql = $this->getClearGeneralTagsSql($field);
		}
		elseif (!$settings->source_is_general && $settings->destination_is_general)
		{
			$copy_sql = $this->getCopyFromCustomToGeneralSql($field);
			$clear_sql = $this->getClearCustomTagsSql();
		}

		return array($copy_sql, $clear_sql);
	}

	private function getCopyFromGeneralToCustomSql($field_column)
	{
		$category_condition = $this->settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED
			? 'AND id IN (:category_ids)'
			: '';

		return "
REPLACE INTO `shop_seo_category_settings` 
(`group_storefront_id`, `category_id`, `name`, `value`)
SELECT :destination_storefront_group_id, id, :field_id, {$field_column} 
FROM shop_category 
WHERE {$field_column} != '' {$category_condition};
";
	}

	private function getCopyFromCustomToCustomSql()
	{
		$category_condition = $this->settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED
			? 'AND category_id IN (:category_ids)'
			: '';

		return "
REPLACE INTO `shop_seo_category_settings`
(`group_storefront_id`, `category_id`, `name`, `value`)
SELECT :destination_storefront_group_id, `category_id`, `name`, `value` 
FROM shop_seo_category_settings
WHERE group_storefront_id = :source_storefront_group_id AND `name` = :field_id AND TRIM(`value`) != '' {$category_condition};
";
	}

	private function getCopyFromCustomToGeneralSql($field_column)
	{
		$category_condition = $this->settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED
			? 'AND c.id IN (:category_ids)'
			: '';

		return "
UPDATE shop_category c, shop_seo_category_settings t
SET c.{$field_column} = t.value
WHERE c.id = t.category_id AND TRIM(t.`value`) != ''
	AND t.name = :field_id AND t.group_storefront_id = :source_storefront_group_id 
	{$category_condition}
";
	}



	private function getClearGeneralTagsSql($field_column)
	{
		$category_condition = $this->settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED
			? 'WHERE id IN (:category_ids)'
			: '';

		return "
UPDATE `shop_category`
SET {$field_column} = ''
{$category_condition}
";
	}

	private function getClearCustomTagsSql()
	{
		$category_condition = $this->settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED
			? 'AND category_id IN (:category_ids)'
			: '';

		return "
DELETE
FROM `shop_seo_category_settings`
WHERE group_storefront_id = :source_storefront_group_id AND `name` = :field_id {$category_condition}
";
	}
}