<?php

class shopComplexPluginCartItemsModel extends shopCartItemsModel
{
   public function total($code)
	{
		if (!$code) {
			return 0;
		}
		$sql = "SELECT c.id as item_id, c.quantity, s.*
				FROM ".$this->table." c
					JOIN shop_product_skus s ON c.sku_id = s.id
				WHERE c.code = s:code
					AND type = 'product'";
		$skus = $this->query($sql, array('code' => $code))->fetchAll('item_id');
		if (!$skus) {
			return 0.0;
		}
		$product_ids = array();
		foreach ($skus as $k => $sku) {
			$product_ids[] = $sku['product_id'];
			$skus[$k]['original_price'] = $sku['price'];
			$skus[$k]['original_compare_price'] = $sku['compare_price'];
		}
		$product_ids = array_unique($product_ids);
		$product_model = new shopProductModel();
		$products = $product_model->getById($product_ids);
		foreach ($products as $p_id => $p) {
			$products[$p_id]['original_price'] = $p['price'];
			$products[$p_id]['original_compare_price'] = $p['compare_price'];
		}
		   $products_skus = array(
			'products' => &$products,
			'skus' => &$skus
		);
		shopRounding::roundSkus($skus);
		$products_total = 0.0;
		foreach ($skus as $s) {
			$products_total += $s['frontend_price'] * $s['quantity'];
		}
		// services
		$services_total = $this->getServicesTotal($code, $products_skus);
		return (float) ($products_total + $services_total);
	}
	
	public function getByCode($code, $full_info = false, $hierarchy = true)
	{
		if (!$code) {
			return array();
		}
		$sql = "SELECT * FROM ".$this->table." WHERE code = s:0 ORDER BY parent_id";
		$items = $this->query($sql, $code)->fetchAll('id');
		$obsolete = array();
		if ($full_info) {
			$rounding_enabled = shopRounding::isEnabled();
			$round_services = wa()->getSetting('round_services');
			$product_ids = $sku_ids = $service_ids = $variant_ids = array();
			foreach ($items as $item) {
				$product_ids[] = $item['product_id'];
				$sku_ids[] = $item['sku_id'];
				if ($item['type'] == 'service') {
					$service_ids[] = $item['service_id'];
					if ($item['service_variant_id']) {
						$variant_ids[] = $item['service_variant_id'];
					}
				}
			}
			$product_model = new shopProductModel();
			if (waRequest::param('url_type') == 2) {
				$products = $product_model->getWithCategoryUrl($product_ids);
			} else {
				$products = $product_model->getById($product_ids);
			}
			foreach ($products as $p_id => $p) {
				$products[$p_id]['original_price'] = $p['price'];
				$products[$p_id]['original_compare_price'] = $p['compare_price'];
			}
			$sku_model = new shopProductSkusModel();
			$skus = $sku_model->getByField('id', $sku_ids, 'id');
			foreach ($skus as $s_id => $s) {
				$skus[$s_id]['original_price'] = $s['price'];
				$skus[$s_id]['original_compare_price'] = $s['compare_price'];
			}
			$rounding_enabled && shopRounding::roundProducts($products);
			$rounding_enabled && shopRounding::roundSkus($skus, $products);
			$service_model = new shopServiceModel();
			$services = $service_model->getByField('id', $service_ids, 'id');
			$rounding_enabled && shopRounding::roundServices($services);
			$service_variants_model = new shopServiceVariantsModel();
			$variants = $service_variants_model->getByField('id', $variant_ids, 'id');
			$rounding_enabled && shopRounding::roundServiceVariants($variants, $services);
			$product_services_model = new shopProductServicesModel();
			$rows = $product_services_model->getByProducts($product_ids);
			$rounding_enabled && shopRounding::roundServiceVariants($rows, $services);
			$product_services = $sku_services = array();
			foreach ($rows as $row) {
				if ($row['sku_id'] && !in_array($row['sku_id'], $sku_ids)) {
					continue;
				}
				$service_ids[] = $row['service_id'];
				if (!$row['sku_id']) {
					$product_services[$row['product_id']][$row['service_variant_id']] = $row;
				}
				if ($row['sku_id']) {
					$sku_services[$row['sku_id']][$row['service_variant_id']] = $row;
				}
			}
			$image_model = null;
			foreach ($items as $item_key => &$item) {
				if (($item['type'] == 'product')
					&& isset($products[$item['product_id']])
					&& isset($skus[$item['sku_id']])
				) {
					$item['product'] = $products[$item['product_id']];
					if (!isset($skus[$item['sku_id']])) {
						unset($items[$item_key]);
						continue;
					}
					$sku = $skus[$item['sku_id']];
					// Use SKU image instead of product image if specified
					if ($sku['image_id'] && $sku['image_id'] != $item['product']['image_id']) {
						$image_model || ($image_model = new shopProductImagesModel());
						$img = $image_model->getById($sku['image_id']);
						if ($img) {
							$item['product']['image_id'] = $sku['image_id'];
							$item['product']['image_filename'] = $img['filename'];
							$item['product']['ext'] = $img['ext'];
						}
					}
					$item['sku_code'] = $sku['sku'];
					$item['purchase_price'] = $sku['purchase_price'];
					$item['compare_price'] = $sku['compare_price'];
					$item['sku_name'] = $sku['name'];
					$item['currency'] = $item['product']['currency'];
					$item['price'] = $sku['price'];
					$item['name'] = $item['product']['name'];
					$item['sku_file_name'] = $sku['file_name'];
					if ($item['sku_name']) {
						$item['name'] .= ' ('.$item['sku_name'].')';
					}
					// Fix for purchase price when rounding is enabled
					if (!empty($item['product']['unconverted_currency']) && $item['product']['currency'] != $item['product']['unconverted_currency']) {
						$item['purchase_price'] = shop_currency($item['purchase_price'], $item['product']['unconverted_currency'], $item['product']['currency'], false);
					}
				} elseif (($item['type'] == 'service')
					&& isset($services[$item['service_id']])
					&& isset($items[$item['parent_id']])
					&& isset($products[$items[$item['parent_id']]['product_id']])
				) {
					$item['name'] = $item['service_name'] = $services[$item['service_id']]['name'];
					$item['currency'] = $services[$item['service_id']]['currency'];
					$item['service'] = $services[$item['service_id']];
					$item['variant_name'] = $variants[$item['service_variant_id']]['name'];
					if ($item['variant_name']) {
						$item['name'] .= ' ('.$item['variant_name'].')';
					}
					$item['price'] = $variants[$item['service_variant_id']]['price'];
					if (isset($product_services[$item['product_id']][$item['service_variant_id']])) {
						if ($product_services[$item['product_id']][$item['service_variant_id']]['price'] !== null) {
							$item['price'] = $product_services[$item['product_id']][$item['service_variant_id']]['price'];
						}
					}
					if (isset($sku_services[$item['sku_id']][$item['service_variant_id']])) {
						if ($sku_services[$item['sku_id']][$item['service_variant_id']]['price'] !== null) {
							$item['price'] = $sku_services[$item['sku_id']][$item['service_variant_id']]['price'];
						}
					}
					if ($item['currency'] == '%') {
						$p = $items[$item['parent_id']];
						$item['price'] = shop_currency($item['price'] * $p['price'] / 100, $p['currency'], $p['currency'], false);
						$item['currency'] = $p['currency'];
					}
				} else {
					$obsolete[] = $item_key;
					unset($items[$item_key]);
					continue;
				}
				if ($round_services && ($item['type'] == 'service')) {
					$item['price'] = shopRounding::roundCurrency($item['price'], $item['currency']);
				}
			}
			unset($item);
		}
		// delete outdated cart items
		if ($obsolete) {
			$this->deleteByField(
				array(
					'code' => $code,
					'id'   => $obsolete,
				)
			);
		}
		// sort
		foreach ($items as $item_id => $item) {
			if ($item['parent_id']) {
				$items[$item['parent_id']]['services'][] = $item;
				unset($items[$item_id]);
			}
		}
		if (!$hierarchy) {
			$result = array();
			foreach ($items as $item_id => $item) {
				if (isset($item['services'])) {
					$i = $item;
					unset($i['services']);
					$result[$item_id] = $i;
					foreach ($item['services'] as $s) {
						$result[$s['id']] = $s;
					}
				} else {
					$result[$item_id] = $item;
				}
			}
			$items = $result;
		}
		return $items;
	}
	
	public function getItem($code, $id)
	{
		$row = $this->getByField(array('code' => $code, 'id' => $id));
		if (!$row) {
			return array();
		}
		$product_model = new shopProductModel();
		$p = $product_model->getById($row['product_id']);
		if (!$p) {
			return array();
		}
		$p['original_price'] = $p['price'];
		$p['original_compare_price'] = $p['compare_price'];
		$products = array($p['id'] => $p);
		$skus_model = new shopProductSkusModel();
		$s = $skus_model->getById($row['sku_id']);
		if (!$s) {
			return array();
		}
		$s['original_price'] = $s['price'];
		$s['original_compare_price'] = $s['compare_price'];
		$skus = array($s['id'] => $s);

		$result = $row;
		$result['price'] = $skus[$result['sku_id']]['price'];
		$result['currency'] = $products[$result['product_id']]['currency'];
		$result['unconverted_price'] = $result['price'];
		$result['unconverted_currency'] = $result['currency'];
		if ($result['price'] && shopRounding::isEnabled()) {
			$config = wa('shop')->getConfig();
			/**
			 * @var shopConfig $config
			 */
			$frontend_currency = $config->getCurrency(false);
			if ($frontend_currency != $result['currency']) {
				$result['currency'] = $frontend_currency;
				$result['price'] = shopRounding::roundCurrency(
					shop_currency($result['unconverted_price'], $result['unconverted_currency'], $frontend_currency, false),
					$frontend_currency
				);
			}
		}
		return $result;
	}
}