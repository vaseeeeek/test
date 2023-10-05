<?php

/**
 * @deprecated
 */
class shopProductgroupWaProductGroupsCollection
{
	private $product_id;
	private $group_ids = null;

	public function __construct($product_id)
	{
		$this->product_id = $product_id;
	}


	/**
	 * @deprecated
	 * @return shopProductgroupProductProductsGroup[]
	 * @throws waDbException
	 * @throws waException
	 */
	public function getGroups()
	{
		$scope = shopProductgroupGroupSettingsScope::PRODUCT;

		$query_params = [
			'product_id' => $this->product_id,
			'scope' => $scope,
		];

		$group_id_condition = '';
		if (is_array($this->group_ids))
		{
			if (count($this->group_ids) === 0)
			{
				return [];
			}

			$group_id_condition = 'AND g.id IN (:group_ids)';
			$query_params['group_ids'] = $this->group_ids;
		}

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
WHERE pgp.product_id = :product_id
	AND g.is_shown = 1
	AND (gs_is_shown.value IS NULL OR gs_is_shown.value = '1')
	AND (
		gs_show_on_primary_product_only.value IS NULL
		OR gs_show_on_primary_product_only.value = '0'
		OR pgp.is_primary = '1'
	)
	{$group_id_condition}
ORDER BY g.sort
";

		$model = new waModel();
		$group_storage = shopProductgroupPluginContext::getInstance()->getGroupStorage();
		$group_settings_storage = shopProductgroupPluginContext::getInstance()->getGroupSettingsStorage();

		$products_groups = [];
		foreach ($model->query($sql, $query_params) as $row)
		{
			$group_id = $row['group_id'];


			$group = $group_storage->getById($group_id);
			$group_settings = $group_settings_storage->getGroupScopeSettings($group_id, $scope);

			$pgp_model = new shopProductgroupProductGroupProductModel();
			$group_products_query = $pgp_model
				->select('product_id,label')
				->where('product_group_id = :id', ['id' => $row['product_group_id']])
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



			$old_drop_out_of_stock = waRequest::param('drop_out_of_stock');

			$new_drop_out_of_stock = $group_settings->show_in_stock_only
				? '2'
				: '0';
			waRequest::setParam('drop_out_of_stock', $new_drop_out_of_stock);

			$group_products_collection = new shopProductsCollection('id/' . implode(',', $group_product_ids));
			$group_products = $group_products_collection->getProducts('*,frontend_url', 0, count($group_product_ids));

			waRequest::setParam('drop_out_of_stock', $old_drop_out_of_stock);


			$group_products_sorted = [];
			foreach ($group_product_ids as $group_product_id)
			{
				if (isset($group_products[$group_product_id]))
				{
					if ($group_settings->current_product_first && $group_product_id == $this->product_id)
					{
						array_unshift($group_products_sorted, $group_products[$group_product_id]);
					}
					else
					{
						$group_products_sorted[] = $group_products[$group_product_id];
					}
				}
			}


			$products_groups[] = new shopProductgroupProductProductsGroup(
				$group,
				$group_settings,
				$scope,
				$group_products_sorted,
				$product_labels
			);
		}

		return $products_groups;
	}

	/**
	 * @param int[] $group_ids
	 */
	public function filterByGroupId($group_ids)
	{
		$this->group_ids = $group_ids;
	}
}