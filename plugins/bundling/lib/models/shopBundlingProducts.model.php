<?php

class shopBundlingProductsModel extends waModel
{
	protected $table = 'shop_bundling_products';

	private $category_model;
	private $categories = [];
	
	public function getBundleProducts($product_id, $bundle_id)
	{
		$bundle = $this->query("SELECT b.*, p.product_ids FROM `{$this->getTableName()}` AS p LEFT JOIN `shop_bundling_bundles` AS b ON b.id = p.bundle_id WHERE p.product_id = i:product_id AND p.bundle_id = i:bundle_id ORDER BY sort ASC", array(
			'product_id' => $product_id,
			'bundle_id' => $bundle_id
		))->fetch('id');
		$bundle['product_ids'] = json_decode($bundle['product_ids'], true);
		
		if(!$bundle['product_ids'])
			$bundle['product_ids'] = array();
		
		return $bundle;
	}
	
	private function getZzzfractionalQuantity($product)
	{
		if(class_exists('shopZzzfractionalPlugin') && wa('shop')->getPlugin('zzzfractional') && !empty($product['unit_multiplicity'])) {
			$key = shopZzzfractionalPluginConfig::init()->multiplicity(); 
			return floatval($product[$key]);
		} else
			return null;
	}
	
	public function getProductMinQuantity($product)
	{
		if($zzzfractional = $this->getZzzfractionalQuantity($product))
			return $zzzfractional;
		
		return 1;
	}
	
	public function getProductStepQuantity($product)
	{
		if($zzzfractional = $this->getZzzfractionalQuantity($product))
			return $zzzfractional;
		
		return 1;
	}
	
	public function getProductsForProductAndBundle($product_id, $bundle_id, $hide_if_not_in_stock = false, $type = null, $discounts = true, $rounding = true, $is_group_by_category = true)
	{
		$frontend_currency = wa('shop')->getConfig()->getCurrency(false);

		$group_by_category = false;
		if($is_group_by_category && intval($bundle_id) <= 0) {
			$group_by_category = true;
			$category_id = intval($bundle_id) * -1;
			if($category_id === 0)
				$category_id = null;
			$bundle_id = 0;
		}
		
		$take = 'SELECT p.*';
		$from = "FROM `{$this->getTableName()}` AS p";

		$sql = "$take $from";
		$where = " WHERE 1";

		if(gettype($product_id) == 'array' && gettype($bundle_id) == 'array') {
			$where .= " AND p.product_id IN (s:product_ids) AND p.bundle_id IN (s:bundle_ids)";
			$data = array(
				'product_ids' => $product_id,
				'bundle_ids' => $bundle_id
			);
		} else {
			$where .= " AND p.product_id = s:product_id AND p.bundle_id = s:bundle_id";
			$data = array(
				'product_id' => $product_id,
				'bundle_id' => $bundle_id
			);
		}
		
		if($group_by_category) {
			$sql .= " LEFT JOIN `shop_product` AS sp ON sp.id = p.bundled_product_id";
			if($category_id)
				$where .= " AND sp.category_id = " . $category_id;
			else
				$where .= " AND sp.category_id IS NULL";
		}
		
		$sql .= $where;
		
		$sql .= " ORDER BY sort ASC";

		$bundled_products_data = $this->query($sql, $data)->fetchAll();

		$products = array();
		foreach($bundled_products_data as $bundled_product_data) {
			if(!empty($bundled_product_data['params'])) {
				$bundled_product_data['params'] = @json_decode($bundled_product_data['params'], true);
			}

			$product_id = $bundled_product_data['bundled_product_id'];
			$product = new shopProduct($product_id);

			$product['params'] = ifset($bundled_product_data, 'params', null);

			$product['min'] = $this->getProductMinQuantity($product);
			$product['step'] = $this->getProductStepQuantity($product);

			$product['row'] = $bundled_product_data;
			$product['quantity'] = floatval($product['row']['default_quantity']);
			$product['discount'] = intval($product['row']['discount']);
			$product['sort'] = intval($product['row']['sort']);
			if(!empty($product) && $product->getId() && !(($product['count'] === '0' || $product['status'] == 0) && $hide_if_not_in_stock)) {
				
				$product['has_sku_id'] = !empty($bundled_product_data['sku_id']);
				$product['title'] = $product['name'];
				
				$sku_id = $bundled_product_data['sku_id'];
				if(!$sku_id)
					$sku_id = $product['sku_id'];
				
				$product['sku_id'] = $sku_id;
				
				$sku = $product['skus'][$sku_id];
				
				if($sku) {
					$price = $sku['primary_price'];
					$product['sku'] = $sku;
				} else
					$price = $product['price'];
				
				if($product['sku_count'] > 1 && !empty($sku['name']))
					$product['name'] = $product['name'] . ($type == 'configurator' ? ('</a> ' . $sku['name'] . '<a>') : (' - <b>' . $sku['name'] . '</b>'));

				$product['images'] = $product->getImages(array(
					'200x0',
					'200x200',
					'96x96',
					'48x48'
				));

				foreach($product['images'] as $id => $image) {
					$product['image'] = array(
						'thumb' => $image['url_0'],
						'square' => $image['url_1'],
						'crop' => $image['url_2'],
						'crop_small' => $image['url_3']
					);

					if(empty($sku['image_id']) || (!empty($sku['image_id']) && $sku['image_id'] == $id))
						break;
				}
				
				$product['key'] = $product['id'];
				if($product['sku_count'] > 1 && $product['has_sku_id'])
					$product['key'] .= '-' . $product['sku_id'];
				
				$product['default_frontend_price'] = shopRounding::roundCurrency(shop_currency($price, null, $frontend_currency, false), $frontend_currency);
				$product['frontend_price'] = $product['default_frontend_price'];
				
				if($product['discount'] && $discounts) {
					$product['frontend_price'] *= (100 - $product['discount']) / 100;
					
					if($rounding) {
						$round_style = wa('shop')->getPlugin('bundling')->getSettings('rounding');
						if($round_style)
							$product['frontend_price'] = shopRounding::round($product['frontend_price'], $round_style, false);
					}
				}
				
				$product['frontend_url'] = wa()->getRouteUrl('/frontend/product',
						array(
							'category_url' => $this->getProductCategoryUrl($product),
							'product_url' => $product['url']
						)
					) . (($product['sku_count'] > 1 && $product['has_sku_id']) ? ('?sku=' . $sku_id) : '');

				$event_data = array(
					'data' => $bundled_product_data,
					'product' => $product,
					'bundle_id' => $bundle_id
				);

				if(wa()->getEnv() === 'backend')
					$product['edit_bundled_product_event'] = wa()->event('bundling_edit_bundled_product', $event_data);
				else
					$product['bundled_product_event'] = wa()->event('bundling_bundled_product', $event_data);
				
				if(!(($sku['count'] === 0 || $sku['available'] == 0) && $hide_if_not_in_stock))
					array_push($products, $product);
			}
		}

		return $products;
	}

	private function getProductCategoryUrl($product)
	{
		if (!isset($product['category_id'])) {
			return null;
		}

		if (!isset($this->category_model)) {
			$this->category_model = new shopCategoryModel();
		}

		if (!isset($this->categories[$product['category_id']])) {
			$this->categories[$product['category_id']] = $this->category_model->getById($product['category_id']);
		}

		$category = $this->categories[$product['category_id']];
		if (!$category) {
			return null;
		}

		$url_type = (int)waRequest::param('url_type');
		$short_url_type = $url_type === 1;

		return $short_url_type ? $category['url'] : $category['full_url'];
	}
}
