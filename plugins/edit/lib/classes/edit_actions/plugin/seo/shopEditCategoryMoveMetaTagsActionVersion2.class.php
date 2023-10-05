<?php

class shopEditCategoryMoveMetaTagsActionVersion2 extends shopEditCategoryMoveMetaTagsAction
{
	private $settings;
	private $destination_storefronts;

	public function __construct(shopEditCategoryMoveMetaTagsFormState $settings)
	{
		parent::__construct();

		$this->settings = $settings;
		$this->destination_storefronts = $this->getCopyDestinationStorefronts();

		if (count($this->destination_storefronts) == 0)
		{
			throw new shopEditActionInvalidParamException('destination_storefront_selection', 'Не выбрано ни одной витрины');
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
				'source_storefront' => $settings->source_storefront,
			);

			if ($settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED)
			{
				$query_params['category_ids'] = $settings->category_selection->category_ids;
			}

			foreach ($this->destination_storefronts as $destination_storefront)
			{
				if ($settings->source_storefront == $destination_storefront)
				{
					continue;
				}

				$query_params['destination_storefront'] = $destination_storefront;

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

	private function getCopyDestinationStorefronts()
	{
		if ($this->settings->destination_is_general)
		{
			return array('*');
		}
		elseif ($this->settings->destination_storefront_selection->mode == shopEditStorefrontSelection::MODE_ALL)
		{
			$storefront_storage = new shopEditStorefrontStorage();

			return array_map(array($this, 'getName'), $storefront_storage->getAllShopStorefronts());
		}
		elseif ($this->settings->destination_storefront_selection->mode == shopEditStorefrontSelection::MODE_SELECTED)
		{
			return $this->settings->destination_storefront_selection->storefronts;
		}
		else
		{
			return array();
		}
	}

	private function getName(shopEditStorefront $s)
	{
		return $s->name;
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
REPLACE INTO `shop_seo_template_category` 
(`category_id`, `storefront_id`, `group_id`, `name`, `value`)
SELECT id, :destination_storefront, 'data', :field_id, {$field_column} 
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
REPLACE INTO `shop_seo_template_category` 
(`category_id`, `storefront_id`, `group_id`, `name`, `value`)
SELECT `category_id`, :destination_storefront, `group_id`, `name`, `value` 
FROM shop_seo_template_category
WHERE storefront_id = :source_storefront AND group_id = 'data' AND `name` = :field_id AND TRIM(`value`) != '' {$category_condition};
";
	}

	private function getCopyFromCustomToGeneralSql($field_column)
	{
		$category_condition = $this->settings->category_selection->mode == shopEditCategorySelection::MODE_SELECTED
			? 'AND c.id IN (:category_ids)'
			: '';

		return "
UPDATE shop_category c, shop_seo_template_category t
SET c.{$field_column} = t.value
WHERE c.id = t.category_id AND TRIM(t.`value`) != ''
	AND t.name = :field_id AND t.storefront_id = :source_storefront 
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
FROM `shop_seo_template_category`
WHERE storefront_id = :source_storefront AND group_id = 'data' AND `name` = :field_id {$category_condition}
";
	}
}