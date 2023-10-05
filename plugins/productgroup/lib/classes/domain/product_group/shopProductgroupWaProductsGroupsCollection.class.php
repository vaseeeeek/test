<?php

class shopProductgroupWaProductsGroupsCollection
{
	private $product_ids;
	private $scope;

	/**
	 * @param int[] $product_ids
	 * @param string $scope
	 */
	public function __construct(array $product_ids, $scope)
	{
		$this->product_ids = $product_ids;
		$this->scope = $scope;
	}

	/**
	 * @return shopProductgroupProductProductsGroup[][]
	 * @throws waDbException
	 * @throws waException
	 */
	public function getProductsGroups()
	{
		if (count($this->product_ids) === 0)
		{
			return [];
		}

		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();
		$group_settings_storage = shopProductgroupPluginContext::getInstance()->getGroupSettingsStorage();


		$products_groups_assoc = $this->loadProductsGroups();
		$products_groups = [];

		foreach ($this->product_ids as $product_id)
		{
			$products_groups[$product_id] = [];

			if (!array_key_exists($product_id, $products_groups_assoc))
			{
				continue;
			}

			foreach ($products_groups_assoc[$product_id] as $product_group)
			{
				$group_id = $product_group['group_id'];
				$product_group_id = $product_group['product_group_id'];

				$group = $group_storage->getById($group_id);
				$group_settings = $group_settings_storage->getGroupScopeSettings($group_id, $this->scope);


				$pgp_model = new shopProductgroupProductGroupProductModel();
				$group_products_query = $pgp_model
					->select('product_id,label')
					->where('product_group_id = :id', ['id' => $product_group_id])
					->order('sort')
					->query();

				$group_product_ids = [];
				$product_labels = [];
				foreach ($group_products_query as $group_product)
				{
					$group_product_id = intval($group_product['product_id']);

					$group_product_ids[] = $group_product_id;
					$product_labels[$group_product_id] = $group_product['label'];
				}

				if (count($group_product_ids) === 0)
				{
					continue;
				}

				$group_products_sorted = [];
				foreach ($group_product_ids as $group_product_id)
				{
					if ($group_settings->current_product_first && $group_product_id == $product_id)
					{
						array_unshift($group_products_sorted, $group_product_id);
					}
					else
					{
						$group_products_sorted[] = $group_product_id;
					}
				}

				$products_groups[$product_id][] = new shopProductgroupProductProductsGroup(
					$group,
					$group_settings,
					$this->scope,
					$group_products_sorted,
					$product_labels
				);
			}
		}

		return $this->loadProductsIntoGroups($products_groups);
	}

	private function loadProductsGroups()
	{
		$query_params = [
			'product_ids' => $this->product_ids,
			'scope' => $this->scope,
		];

		$sql = "
SELECT
	g.id AS group_id,
	pgp.product_id AS product_id,
	pgp.product_group_id AS product_group_id
FROM shop_productgroup_product_group_product AS pgp
	JOIN shop_productgroup_product_group AS pg
		ON pg.id = pgp.product_group_id
	JOIN shop_productgroup_group AS g
		ON g.id = pg.group_id
	LEFT JOIN shop_productgroup_group_settings AS gs_is_shown
		ON gs_is_shown.group_id = g.id AND gs_is_shown.scope = :scope AND gs_is_shown.name = 'is_shown'
	LEFT JOIN shop_productgroup_group_settings AS gs_show_on_primary_product_only
		ON gs_show_on_primary_product_only.group_id = g.id AND gs_show_on_primary_product_only.scope = :scope AND gs_show_on_primary_product_only.name = 'show_on_primary_product_only'
WHERE pgp.product_id IN (:product_ids)
	AND g.is_shown = 1
	AND (gs_is_shown.value IS NULL OR gs_is_shown.value = '1')
	AND (
		gs_show_on_primary_product_only.value IS NULL
		OR gs_show_on_primary_product_only.value = '0'
		OR pgp.is_primary = '1'
	)
ORDER BY g.sort
";


		$model = new waModel();

		$products_groups = [];
		foreach ($model->query($sql, $query_params) as $row)
		{
			$product_id = $row['product_id'];
			if (!array_key_exists($product_id, $products_groups))
			{
				$products_groups[$product_id] = [];
			}

			$products_groups[$product_id][] = $row;
		}

		return $products_groups;
	}

	/**
	 * @param shopProductgroupProductProductsGroup[][] $products_groups
	 * @return shopProductgroupProductProductsGroup[][]
	 * @throws waDbException
	 * @throws waException
	 */
	private function loadProductsIntoGroups($products_groups)
	{
		$product_ids_to_load = [];
		$product_ids_in_stock_to_load = [];

		foreach ($products_groups as $group_product_id => $product_groups)
		{
			$product_ids_to_load[$group_product_id] = $group_product_id;

			foreach ($product_groups as $product_group)
			{
				foreach ($product_group->group_products as $product_id)
				{
					if ($product_group->group_settings->show_in_stock_only)
					{
						$product_ids_in_stock_to_load[$product_id] = $product_id;
					}
					else
					{
						$product_ids_to_load[$product_id] = $product_id;
					}
				}
			}
		}


		$products = [];
		$products_in_stock = [];
		$category_ids = [];

		if (count($product_ids_in_stock_to_load) > 0)
		{
			$products_in_stock = $this->fetchProducts(array_values($product_ids_in_stock_to_load), true);
			foreach ($products_in_stock as $id => $product)
			{
				if (array_key_exists($id, $product_ids_to_load))
				{
					$products[$id] = $product;
					unset($product_ids_to_load[$id]);
				}

				if (ifset($product, 'category_id', 0) > 0)
				{
					$category_ids[$product['category_id']] = $product['category_id'];
				}
			}
		}

		if (count($product_ids_to_load) > 0)
		{
			foreach ($this->fetchProducts(array_values($product_ids_to_load), false) as $id => $product)
			{
				$products[$id] = $product;

				if (ifset($product, 'category_id', 0) > 0)
				{
					$category_ids[$product['category_id']] = $product['category_id'];
				}
			}
		}

		$category_urls = [];
		if (count($category_ids) > 0)
		{
			$category_model = new shopCategoryModel();
			$category_urls = $category_model
				->select('id,full_url')
				->where('id IN (:ids)', ['ids' => array_values($category_ids)])
				->fetchAll('id', true);
		}

		$products_groups_extended = [];
		foreach ($products_groups as $group_product_id => $product_groups)
		{
			$products_groups_extended[$group_product_id] = [];

			foreach ($product_groups as $product_group)
			{
				$group_products = [];

				foreach ($product_group->group_products as $product_id)
				{
					$product_to_add = $product_group->group_settings->show_in_stock_only
						? ifset($products_in_stock, $product_id, null)
						: ifset($products, $product_id, null);

					if ($product_to_add)
					{
						if ($product_to_add['category_id'] > 0 && isset($category_urls[$product_to_add['category_id']]))
						{
							$product_to_add['category_url'] = $category_urls[$product_to_add['category_id']];
						}

						$group_products[] = $product_to_add;
					}
				}

				$current_product = ifset($products, $group_product_id, ifset($products_in_stock, $group_product_id, null));

				$products_groups_extended[$group_product_id][] = new shopProductgroupProductProductsGroup(
					$product_group->group,
					$product_group->group_settings,
					$product_group->scope,
					$group_products,
					$product_group->product_labels,
					$current_product
				);
			}
		}

		return $products_groups_extended;
	}

	private function fetchProducts($product_ids, $drop_out_of_stock)
	{
		$model = new waModel();

		$old_drop_out_of_stock = waRequest::param('drop_out_of_stock');
		$old_sort = waRequest::get('sort');

		if ($old_sort)
		{
			unset($_GET['sort']);
		}

		$new_drop_out_of_stock = $drop_out_of_stock ? '2' : '0';

		waRequest::setParam('drop_out_of_stock', $new_drop_out_of_stock);

		$products_query = [];
		try
		{
			$group_products_collection = new shopProductsCollection('id/' . implode(',', $product_ids));
			$from_and_where_sql = $group_products_collection->getSQL();

			// todo если сломается из-за отсутствия HAVING/GROUP BY - придется собирать sql аккуратнее
			$sql = $this->getProductsCollectionSelect() . $from_and_where_sql;
			$products_query = $model->query($sql);
		}
		catch (Exception $e)
		{
			return [];
		}
		finally
		{
			waRequest::setParam('drop_out_of_stock', $old_drop_out_of_stock);
			if ($old_sort)
			{
				$_GET['sort'] = $old_sort;
			}
		}

		$products = [];
		foreach ($products_query as $product)
		{
			$products[$product['id']] = $product;
		}

		return $products;
	}

	private function getProductsCollectionSelect()
	{
		return '
SELECT
	p.id,
	p.name,
	p.summary,
	p.contact_id,
	p.create_datetime,
	p.edit_datetime,
	p.`status`,
	p.type_id,
	p.image_id,
	p.image_filename,
	p.video_url,
	p.sku_id,
	p.ext,
	p.url,
	p.rating,
	p.price,
	p.compare_price,
	p.currency,
	p.min_price,
	p.max_price,
	p.tax_id,
	p.`count`,
	p.cross_selling,
	p.upselling,
	p.rating_count,
	p.category_id,
	p.badge,
	p.sku_type,
	p.sku_count
';
	}
}