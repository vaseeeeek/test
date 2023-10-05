<?php

class shopEditBrandStorage
{
	public function updateDefaultSorting(shopEditBrandSelection $brand_selection, $product_sort)
	{
		$brand_plugin_helper = new shopEditBrandPluginHelper();

		$brand_model = new shopBrandBrandModel();

		$affected_brands_query = $brand_model
			->select('id')
			->where('product_sort != :product_sort', array('product_sort' => $product_sort));

		$affected_brand_ids = array();

		if ($brand_selection->mode == shopEditBrandSelection::MODE_ALL)
		{
			foreach ($affected_brands_query->query() as $row)
			{
				$affected_brand_ids[] = $row['id'];
			}

			$brand_table = $brand_model->getTableName();
			$update_sql = "
UPDATE {$brand_table}
SET `product_sort` = :product_sort
";
			$params = array('product_sort' => $product_sort);

			$brand_model->exec($update_sql, $params);
		}
		elseif ($brand_selection->mode == shopEditBrandSelection::MODE_SELECTED && count($brand_selection->brand_ids) > 0)
		{
			$affected_brands_query->where('id IN (:ids)', array('ids' => $brand_selection->brand_ids));

			foreach ($affected_brands_query->query() as $row)
			{
				$affected_brand_ids[] = $row['id'];
			}

			$brand_model->updateByField('id', $brand_selection->brand_ids, array('product_sort' => $product_sort));
		}

		$brand_plugin_helper->clearPluginCache();

		return $affected_brand_ids;
	}

	public function toggleEnableClientSorting(shopEditBrandSelection $brand_selection, $toggle)
	{
		$brand_plugin_helper = new shopEditBrandPluginHelper();
		$brand_model = new shopBrandBrandModel();
		$brand_brand_storage = new shopBrandBrandStorage();

		/** @var shopBrandBrand[] $brands_to_update */
		$brands_to_update = array();

		if ($brand_selection->mode === shopEditBrandSelection::MODE_ALL)
		{
			$brands_to_update = $brand_brand_storage->getAll();
			foreach (array_keys($brands_to_update) as $index)
			{
				if ($brands_to_update[$index]->enable_client_sorting === $toggle)
				{
					unset($brands_to_update[$index]);
				}
			}
		}
		elseif ($brand_selection->mode == shopEditBrandSelection::MODE_SELECTED && count($brand_selection->brand_ids) > 0)
		{
			$brands_to_update = $brand_brand_storage->getAll();
			foreach (array_keys($brands_to_update) as $index)
			{
				$brand = $brands_to_update[$index];

				if (!array_key_exists($brand->id, $brand_selection->brand_ids) || $brand->enable_client_sorting === $toggle)
				{
					unset($brands_to_update[$index]);
				}
			}
			unset($brand);
		}

		$affected_brand_ids = array();
		foreach ($brands_to_update as $brand)
		{
			$affected_brand_ids[]= $brand->id;
		}

		if (count($affected_brand_ids) > 0)
		{
			$update = array('enable_client_sorting' => $toggle ? '1' : '0');
			$brand_model->updateByField('id', $affected_brand_ids, $update);
		}

		$brand_plugin_helper->clearPluginCache();

		return $affected_brand_ids;
	}
}
