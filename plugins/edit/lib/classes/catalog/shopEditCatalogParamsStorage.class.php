<?php

class shopEditCatalogParamsStorage
{
	private $model;

	public function __construct()
	{
		$this->model = new waModel();
	}

	/**
	 * @param $entity_type
	 * @param shopEditCategorySelection $category_selection
	 * @throws waException
	 */
	public function deleteAllParamsForEntities($entity_type, shopEditCategorySelection $category_selection)
	{
		if ($entity_type === shopEditCatalogEntityType::CATEGORY)
		{
			$this->deleteAllForCategory($category_selection);
		}
		elseif ($entity_type === shopEditCatalogEntityType::PRODUCT)
		{
			$this->deleteAllForProduct($category_selection);
		}
		else
		{
			throw new waException("Неизвестный тип элемента каталога [{$entity_type}]");
		}
	}

	/**
	 * @param string $entity_type
	 * @param shopEditCategorySelection $category_selection
	 * @param array $params
	 * @throws waException
	 */
	public function overwriteParamsForEntities($entity_type, shopEditCategorySelection $category_selection, $params)
	{
		if ($entity_type === shopEditCatalogEntityType::CATEGORY)
		{
			$this->deleteAllForCategory($category_selection);
			$this->addUpdateParamsCategory($category_selection, $params);
		}
		elseif ($entity_type === shopEditCatalogEntityType::PRODUCT)
		{
			$this->deleteAllForProduct($category_selection);
			$this->addUpdateParamsProduct($category_selection, $params);
		}
		else
		{
			throw new waException("Неизвестный тип элемента каталога [{$entity_type}]");
		}
	}

	/**
	 * @param string $entity_type
	 * @param shopEditCategorySelection $category_selection
	 * @param array $params
	 * @throws waException
	 */
	public function addUpdateParamsForEntities($entity_type, shopEditCategorySelection $category_selection, $params)
	{
		if ($entity_type === shopEditCatalogEntityType::CATEGORY)
		{
			$this->addUpdateParamsCategory($category_selection, $params);
		}
		elseif ($entity_type === shopEditCatalogEntityType::PRODUCT)
		{
			$this->addUpdateParamsProduct($category_selection, $params);
		}
		else
		{
			throw new waException("Неизвестный тип элемента каталога [{$entity_type}]");
		}
	}

	/**
	 * @param string $entity_type
	 * @param shopEditCategorySelection $category_selection
	 * @param array $params
	 * @throws waException
	 */
	public function addIgnoreParamsForEntities($entity_type, shopEditCategorySelection $category_selection, $params)
	{
		if ($entity_type === shopEditCatalogEntityType::CATEGORY)
		{
			$this->addIgnoreParamsForCategory($category_selection, $params);
		}
		elseif ($entity_type === shopEditCatalogEntityType::PRODUCT)
		{
			$this->addIgnoreParamsForProduct($category_selection, $params);
		}
		else
		{
			throw new waException("Неизвестный тип элемента каталога [{$entity_type}]");
		}
	}



	private function deleteAllForCategory(shopEditCategorySelection $category_selection)
	{
		$sql = null;
		$query_params = [
			'excluded_category_params' => array_values($this->getExcludedCategoryParams()),
		];

		if ($category_selection->mode === shopEditCategorySelection::MODE_ALL)
		{
			$sql = '
DELETE FROM shop_category_params
WHERE `name` NOT IN(:excluded_category_params)
';
		}
		elseif ($category_selection->mode === shopEditCategorySelection::MODE_SELECTED)
		{
			if (count($category_selection->category_ids) === 0)
			{
				throw new waException('Выберите хотя бы одну категорию');
			}

			$query_params['category_ids'] = array_values($category_selection->category_ids);

			$sql = '
DELETE FROM shop_category_params
WHERE `name` NOT IN(:excluded_category_params)
	AND `category_id` IN (:category_ids)
';
		}
		else
		{
			throw new waException("Неизвестный тип выбора категорий [{$category_selection->mode}]");
		}

		$this->model->exec($sql, $query_params);
	}

	private function deleteAllForProduct(shopEditCategorySelection $category_selection)
	{
		if ($category_selection->mode === shopEditCategorySelection::MODE_ALL)
		{
			$sql = 'DELETE FROM shop_product_params';

			$this->model->exec($sql);
		}
		elseif ($category_selection->mode === shopEditCategorySelection::MODE_SELECTED)
		{
			if (count($category_selection->category_ids) === 0)
			{
				throw new waException('Выберите хотя бы одну категорию');
			}

			$sql = '
DELETE FROM shop_product_params
WHERE `product_id` IN (:product_ids)
';

			foreach ($this->getSelectedProductIdsChunks($category_selection->category_ids) as $product_ids)
			{
				if (count($product_ids) === 0)
				{
					continue;
				}

				$query_params = [
					'product_ids' => $product_ids,
				];

				$this->model->exec($sql, $query_params);
			}
		}
		else
		{
			throw new waException("Неизвестный тип выбора категорий [{$category_selection->mode}]");
		}
	}

	private function addUpdateParamsCategory(shopEditCategorySelection $category_selection, $params)
	{
		return $this->_updateParamsForCategory($category_selection, $params, true);
	}

	private function addUpdateParamsProduct(shopEditCategorySelection $category_selection, $params)
	{
		return $this->_updateParamsForProduct($category_selection, $params, true);
	}

	private function addIgnoreParamsForCategory(shopEditCategorySelection $category_selection, $params)
	{
		return $this->_updateParamsForCategory($category_selection, $params, false);
	}

	private function addIgnoreParamsForProduct(shopEditCategorySelection $category_selection, $params)
	{
		return $this->_updateParamsForProduct($category_selection, $params, false);
	}


	private function _updateParamsForCategory(shopEditCategorySelection $category_selection, $params, $replace_existing)
	{
		$query_params = [];

		if ($category_selection->mode === shopEditCategorySelection::MODE_ALL)
		{
			$insert_sql = '
INSERT INTO shop_category_params
SELECT id AS category_id, :name AS `name`, :value AS `value`
FROM shop_category
';
		}
		elseif ($category_selection->mode === shopEditCategorySelection::MODE_SELECTED)
		{
			if (count($category_selection->category_ids) === 0)
			{
				throw new waException('Выберите хотя бы одну категорию');
			}

			$query_params['category_ids'] = array_values($category_selection->category_ids);

			$insert_sql = '
INSERT INTO shop_category_params
SELECT id AS category_id, :name AS `name`, :value AS `value`
FROM shop_category
WHERE id IN (:category_ids)
';
		}
		else
		{
			throw new waException("Неизвестный тип выбора категорий [{$category_selection->mode}]");
		}


		if ($replace_existing)
		{
			$insert_sql .= 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
		}
		else
		{
			$insert_sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $insert_sql);
		}


		foreach ($params as $key => $value)
		{
			$query_params['name'] = $key;
			$query_params['value'] = $value;

			$this->model->exec($insert_sql, $query_params);
		}

		return [];
	}

	private function _updateParamsForProduct(shopEditCategorySelection $category_selection, $params, $replace_existing)
	{
		if ($category_selection->mode === shopEditCategorySelection::MODE_ALL)
		{
			$insert_sql = '
INSERT INTO shop_product_params
SELECT id AS product_id, :name AS `name`, :value AS `value`
FROM shop_product
';

			if ($replace_existing)
			{
				$insert_sql .= 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
			}
			else
			{
				$insert_sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $insert_sql);
			}

			$this->model->exec($insert_sql);
		}
		elseif ($category_selection->mode === shopEditCategorySelection::MODE_SELECTED)
		{
			if (count($category_selection->category_ids) === 0)
			{
				throw new waException('Выберите хотя бы одну категорию');
			}

			$insert_sql = '
INSERT INTO shop_product_params
SELECT id AS product_id, :name AS `name`, :value AS `value`
FROM shop_product
WHERE id IN (:product_ids)
';

			if ($replace_existing)
			{
				$insert_sql .= 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
			}
			else
			{
				$insert_sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $insert_sql);
			}

			foreach ($this->getSelectedProductIdsChunks($category_selection->category_ids) as $product_ids)
			{
				if (count($product_ids) === 0)
				{
					continue;
				}

				$query_params = ['product_ids' => $product_ids,];

				foreach ($params as $key => $value)
				{
					$query_params['name'] = $key;
					$query_params['value'] = $value;

					$this->model->exec($insert_sql, $query_params);
				}
			}
		}
		else
		{
			throw new waException("Неизвестный тип выбора категорий [{$category_selection->mode}]");
		}

		return [];
	}






	/**
	 * @param $category_ids
	 * @return array|Generator
	 * @throws waException
	 */
	private function getSelectedProductIdsChunks($category_ids)
	{
		$category_ids_filtered = [];
		foreach ($category_ids as $category_id)
		{
			if (wa_is_int($category_id) && $category_id > 0)
			{
				$category_ids_filtered[$category_id] = intval($category_id);
			}
		}

		if (count($category_ids_filtered) === 0)
		{
			return;
		}

		$category_ids_imploded = implode(',', array_values($category_ids_filtered));

		$products_collection = new shopProductsCollection("search/category_id={$category_ids_imploded}");

		$offset = 0;
		$limit = 100;

		do
		{
			$products_fetched = $products_collection->getProducts('id', $offset, $limit);
			$offset += $limit;

			yield array_keys($products_fetched);
		}
		while(count($products_fetched) > 0);

		return;
	}

	private function getExcludedCategoryParams()
	{
		return [
			'enable_sorting' => 'enable_sorting',
		];
	}
}
