<?php

/**
 * Class shopEditCategoryStorage
 *
 * @method shopEditCategory|null getById($id)
 */
class shopEditCategoryStorage extends shopEditStorage
{
	/**
	 * @return shopEditCategory[]
	 */
	public function getAll()
	{
		$categories = array();

		$query = $this->model
			->select('id')
			->order('left_key ASC')
			->query();

		foreach ($query as $row)
		{
			$categories[] = $this->getById($row['id']);
		}

		return $categories;
	}

	public function getAllAssocForSettings()
	{
		$categories = array();

		$fields = array();
		foreach ($this->getAvailableFields() as $field)
		{
			$fields[] = "c.{$field}";
		}
		$fields_statement = implode(', ', $fields);

		$select_sql = "
SELECT
	{$fields_statement},
	COALESCE(p.value, '0') AS `enable_sorting`,
	IF(c.filter IS NOT NULL AND c.filter != '', '1', '0') AS `category_has_filters`
FROM shop_category AS c
	LEFT JOIN shop_category_params AS p
		ON p.category_id = c.id AND p.name = 'enable_sorting'
GROUP BY c.id
ORDER BY c.left_key
";

		$query = $this->model->query($select_sql);

		foreach ($query as $category_raw)
		{
			$category_assoc = $this->prepareStorableForAccessible($category_raw);
			$category_assoc['params_enable_sorting'] = $category_raw['enable_sorting'] == '1';
			$category_assoc['category_has_filters'] = $category_raw['category_has_filters'] == '1';

			$categories[] = $category_assoc;
		}

		return $categories;
	}

	/**
	 * @param shopEditCategorySelection $category_selection
	 * @param boolean $toggle
	 * @return int[]  affected category ids
	 */
	public function toggleIncludeSubCategories(shopEditCategorySelection $category_selection, $toggle)
	{
		$include_sub_categories = $toggle ? '1' : '0';

		$affected_category_ids = array();

		if ($category_selection->mode == shopEditCategorySelection::MODE_ALL)
		{
			$affected_category_query = $this->model->select('id')
				->where('include_sub_categories != :include_sub_categories', array('include_sub_categories' => $include_sub_categories))
				->query();

			foreach ($affected_category_query as $row)
			{
				$affected_category_ids[] = $row['id'];
			}

			$this->model->exec('UPDATE `shop_category` SET `include_sub_categories` = :val', array('val' => $include_sub_categories));
		}
		elseif (count($category_selection->category_ids) > 0)
		{
			if ($category_selection->mode == shopEditCategorySelection::MODE_SELECTED)
			{
				$affected_category_query = $this->model->select('id')
					->where('include_sub_categories != :include_sub_categories', array('include_sub_categories' => $include_sub_categories))
					->where('id IN (:ids)', array('ids' => $category_selection->category_ids))
					->query();

				foreach ($affected_category_query as $row)
				{
					$affected_category_ids[] = $row['id'];
				}

				$this->model->updateByField('id', $category_selection->category_ids, array(
					'include_sub_categories' => $include_sub_categories,
				));
			}
		}

		return $affected_category_ids;
	}

	/**
	 * @param shopEditCategorySelection $category_selection
	 * @param boolean $enable_sorting
	 */
	public function toggleEnableClientSorting(shopEditCategorySelection $category_selection, $enable_sorting)
	{
		$category_params_model = new shopCategoryParamsModel();

		$affected_category_ids = array();


		$params = array();
		if ($category_selection->mode == shopEditCategorySelection::MODE_ALL)
		{
			$category_where = '1 = 1';
		}
		elseif ($category_selection->mode == shopEditCategorySelection::MODE_SELECTED && count($category_selection->category_ids) > 0)
		{
			$category_where = 'c.id IN (:category_ids)';
			$params['category_ids'] = $category_selection->category_ids;
		}
		else
		{
			$category_where = '1 = 0';
		}


		$toggle_modifier = $enable_sorting ? '' : '!';

		$affected_category_sql = "
SELECT c.id
FROM shop_category AS c
	LEFT JOIN shop_category_params AS p
		ON c.id = p.category_id AND p.name = 'enable_sorting'
WHERE {$category_where} AND {$toggle_modifier}(p.value IS NULL OR p.value = '0')
";

		foreach ($this->model->query($affected_category_sql, $params) as $row)
		{
			$affected_category_ids[] = $row['id'];
		}

		if ($category_selection->mode == shopEditCategorySelection::MODE_ALL)
		{
			if ($enable_sorting)
			{
				$enable_sorting_sql = '
REPLACE INTO `shop_category_params`
SELECT 
    c.id `category_id`, 
    \'enable_sorting\' `name`, 
    1 `value`
FROM shop_category AS c
';

				$category_params_model->exec($enable_sorting_sql);
			}
			else
			{
				$category_params_model->deleteByField('name', 'enable_sorting');
			}
		}
		elseif ($category_selection->mode == shopEditCategorySelection::MODE_SELECTED && count($category_selection->category_ids) > 0)
		{
			if ($enable_sorting)
			{
				$enable_sorting_sql = '
REPLACE INTO `shop_category_params`
SELECT 
    c.id `category_id`, 
    \'enable_sorting\' `name`, 
    1 `value`
FROM shop_category AS c
WHERE c.id IN (:ids)
';

				$category_params_model->exec($enable_sorting_sql, array('ids' => $category_selection->category_ids));
			}
			else
			{
				$category_params_model->deleteByField(array(
					'category_id' => $category_selection->category_ids,
					'name' => 'enable_sorting',
				));
			}
		}

		return $affected_category_ids;
	}

	/**
	 * @param shopEditCategorySelection $category_selection
	 * @param string $sorting
	 */
	public function updateDefaultSorting(shopEditCategorySelection $category_selection, $sorting)
	{
		$sorting = is_string($sorting) && trim($sorting) != ''
			? trim($sorting)
			: null;


		$affected_categories_query = $this->model->select('id');
		if ($sorting === null)
		{
			$affected_categories_query->where('COALESCE(sort_products, \'\') <> \'\'');
		}
		else
		{
			$affected_categories_query->where('!(sort_products <=> :sort_products)', array('sort_products' => $sorting));
		}
		$affected_category_ids = array();

		if ($category_selection->mode == shopEditCategorySelection::MODE_ALL)
		{
			foreach ($affected_categories_query->query() as $row)
			{
				$affected_category_ids[] = $row['id'];
			}


			$update_sql = '
UPDATE `shop_category`
SET `sort_products` = :sort_products
';

			$params = array(
				'sort_products' => $sorting
			);

			$this->model->exec($update_sql, $params);
		}
		elseif ($category_selection->mode == shopEditCategorySelection::MODE_SELECTED)
		{
			$affected_categories_query->where('id IN (:ids)', array('ids' => $category_selection->category_ids));

			foreach ($affected_categories_query->query() as $row)
			{
				$affected_category_ids[] = $row['id'];
			}


			if (count($category_selection->category_ids))
			{
				$this->model->updateByField('id', $category_selection->category_ids, array('sort_products' => $sorting));
			}
		}

		return $affected_category_ids;
	}

	/**
	 * @param shopEditCategorySelection $category_selection
	 * @param bool $consider_include_sub_categories  Учитывать настройку "Включить товары из подкатегорий"
	 * @return array
	 */
	public function deleteEmptyCategories(shopEditCategorySelection $category_selection, $consider_include_sub_categories)
	{
		/** @var shopCategoryModel $category_model */
		$category_model = $this->model;
		$categories_query = $category_model
			->select('id')
			->order('left_key DESC');

		if ($category_selection->mode == shopEditCategorySelection::MODE_SELECTED)
		{
			if (count($category_selection->category_ids) == 0)
			{
				$categories_query->where('1 = 0');
			}
			else
			{
				$categories_query->where('id IN (:ids)', array('ids' => array_values($category_selection->category_ids)));
			}
		}

		$collection_params = array();
		if (!$consider_include_sub_categories)
		{
			$collection_params['consider_include_sub_categories'] = false;
		}
		$categories_to_delete = array();

		foreach ($categories_query->query() as $row)
		{
			$category_id = $row['id'];

			$products_collection = new shopEditCustomProductsCollection("category/{$category_id}", $collection_params);

			$count = $products_collection->count();
			if ($count == 0)
			{
				$categories_to_delete[] = $category_id;
			}
		}

		foreach ($categories_to_delete as $category_id)
		{
			$category_model->delete($category_id);
		}

		return $categories_to_delete;
	}

	public function hideEmptyCategories(shopEditCategorySelection $category_selection)
	{
		/** @var shopCategoryModel $category_model */
		$category_model = $this->model;
		$categories_query = $category_model
			->select('id')
			->order('left_key DESC');

		if ($category_selection->mode == shopEditCategorySelection::MODE_SELECTED)
		{
			if (count($category_selection->category_ids) == 0)
			{
				$categories_query->where('1 = 0');
			}
			else
			{
				$categories_query->where('id IN (:ids)', array('ids' => array_values($category_selection->category_ids)));
			}
		}

		$categories_to_hide = array();
		foreach ($categories_query->query() as $row)
		{
			$category_id = $row['id'];

			$products_collection = new shopEditCustomProductsCollection("category/{$category_id}");

			$count = $products_collection->count();
			if ($count == 0)
			{
				$categories_to_hide[] = $category_id;
			}
		}

		if (count($categories_to_hide) > 0)
		{
			$sql = "
UPDATE shop_category
SET `status` = '0'
WHERE id IN (:ids)
";

			$category_model->exec($sql, array('ids' => $categories_to_hide));

			$this->clearCache();
		}

		return $categories_to_hide;
	}

	public function getCategoriesProductsCount($category_ids)
	{
		$categories_products_count = array();

		foreach ($category_ids as $category_id)
		{
			$products_collection = new shopEditCustomProductsCollection("category/{$category_id}");

			$categories_products_count[$category_id] = $products_collection->count();
		}

		return $categories_products_count;
	}

	public function getCategoryFilter($category_id)
	{
		$filter_raw = $this->model
			->select('filter')
			->where('id = :id', array('id' => $category_id))
			->fetchField();

		return is_string($filter_raw) && strlen($filter_raw) > 0
			? explode(',', $filter_raw)
			: array();
	}

	protected function accessSpecification()
	{
		$specification = new shopEditDataFieldSpecificationFactory();

		return array(
			'id' => $specification->integer(),
			'left_key' => $specification->integer(),
			'right_key' => $specification->integer(),
			'depth' => $specification->integer(),
			'parent_id' => $specification->integer(),
			'name' => $specification->string(),
			'meta_title' => $specification->string(),
			'meta_keywords' => $specification->string(),
			'meta_description' => $specification->string(),
			'type' => $specification->integer(),
			'url' => $specification->string(),
			'full_url' => $specification->string(),
			'count' => $specification->integer(),
			'description' => $specification->string(),
			'conditions' => $specification->string(),
			'create_datetime' => $specification->datetime(),
			'edit_datetime' => $specification->datetime(),
			'sort_products' => $specification->string(),
			'include_sub_categories' => $specification->boolean(false),
			'status' => $specification->boolean(true),
		);
	}

	protected function dataModelInstance()
	{
		return new shopCategoryModel();
	}

	protected function entityInstance()
	{
		return new shopEditCategory();
	}

	protected function clearCache()
	{
		if ($cache = wa('shop')->getCache())
		{
			$cache->deleteGroup('categories');
		}
	}
}
