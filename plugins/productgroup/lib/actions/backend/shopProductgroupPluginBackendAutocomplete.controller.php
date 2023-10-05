<?php

class shopProductgroupPluginBackendAutocompleteController extends shopBackendAutocompleteController
{
	public function productsAutocomplete($q, $limit = null)
	{
		$_except_product_ids = waRequest::request('except_ids', [], waRequest::TYPE_ARRAY);

		$except_product_ids = [];
		foreach ($_except_product_ids as $id)
		{
			if (wa_is_int($id) && $id > 0)
			{
				$except_product_ids[$id] = $id;
			}
		}

		return count($except_product_ids) > 0
			? $this->productsExceptCurrentAutocomplete($q, array_values($except_product_ids), $limit)
			: parent::productsAutocomplete($q, $limit);
	}

	private function productsExceptCurrentAutocomplete($q, $except_product_ids, $limit = null)
	{
		$limit = $limit !== null ? $limit : $this->limit;

		$product_model = new shopProductModel();
		$q = $product_model->escape($q, 'like');
		$fields = 'id, name AS value, price, count, sku_id';

		$products = $product_model->select($fields)
			->where("name LIKE '$q%'")
			->where("id NOT IN (:except_ids)", array('except_ids' => $except_product_ids))
			->limit($limit)
			->fetchAll('id');
		$count = count($products);

		if ($count < $limit) {
			$product_skus_model = new shopProductSkusModel();
			$product_ids = $product_skus_model
				->select('id, product_id')
				->where("(sku LIKE '$q%' OR name LIKE '$q%')")
				->where("product_id NOT IN (:except_ids)", array('except_ids' => $except_product_ids))
				->limit($limit)
				->fetchAll('product_id');
			$product_ids = array_keys($product_ids);
			if ($product_ids) {
				$data = $product_model->select($fields)
					->where('id IN ('.implode(',', $product_ids).')')
					->limit($limit - $count)
					->fetchAll('id');

				// not array_merge, because it makes first reset numeric keys and then make merge
				$products = $products + $data;
			}
		}

		// try find with LIKE %query%
		if (count($products) < $limit) {
			$data = $product_model
				->select($fields)
				->where("name LIKE '%$q%'")
				->where("id NOT IN (:except_ids)", array('except_ids' => $except_product_ids))
				->limit($limit)
				->fetchAll('id');

			// not array_merge, because it makes first reset numeric keys and then make merge
			$products = $products + $data;
		}
		$config = wa('shop')->getConfig();
		/**
		 * @var shopConfig $config
		 */
		$currency = $config->getCurrency();
		foreach ($products as &$p) {
			$p['price_str'] = wa_currency($p['price'], $currency);
			$p['price_html'] = wa_currency_html($p['price'], $currency);
		}
		unset($p);

		if (waRequest::get('with_sku_name')) {
			$sku_ids = array();
			foreach ($products as $p) {
				$sku_ids[] = $p['sku_id'];
			}
			$product_skus_model = new shopProductSkusModel();
			$skus = $product_skus_model->getByField('id', $sku_ids, 'id');
			$sku_names = array();
			foreach ($skus as $sku_id => $sku) {
				if ($sku['name']) {
					$name = $sku['name'];
					if ($sku['sku']) {
						$name .= ' ('.$sku['sku'].')';
					}
				} else {
					$name = $sku['sku'];
				}
				$sku_names[$sku_id] = $name;
			}
			foreach ($products as &$p) {
				$p['sku_name'] = $sku_names[$p['sku_id']];
			}
			unset($p);
		}

		return array_values($products);
	}
}