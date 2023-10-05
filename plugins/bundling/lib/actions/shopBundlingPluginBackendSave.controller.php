<?php

class shopBundlingPluginBackendSaveController extends waJsonController
{
	public function execute()
	{
		if(waRequest::get('id') != waRequest::post('product_id'))
			return $this->setError(_wp('Unknown error!'));
		
		if(waRequest::post('_csrf') != waRequest::cookie('_csrf'))
			return $this->setError(_wp('CSRF error'));
		
		$product_id = waRequest::post('product_id');
		$bundles = waRequest::post('bundle', array(), 'array');
		$sort = waRequest::post('sort', array(), 'array_int');
		
		$model = new shopBundlingModel();
		$products_model = new shopBundlingProductsModel();
		$sort_model = new shopBundlingSortModel();
		
		$bundle_groups_settings = wa('shop')->getPlugin('bundling')->getSettings('bundle_groups');
		if($bundle_groups_settings == 'custom') {
			$bundle_groups = $model->getBundleGroups($product_id);
		} elseif($bundle_groups_settings == 'main_category') {
			$model = new shopBundlingCategoriesModel();
			$bundle_groups = $model->getCategoriesAsBundleGroups($product_id);
		}
		
		foreach($bundle_groups as $bundle_id) {
			$bundle_key = $bundle_id;
			if(intval($bundle_id) < 0)
				$bundle_key = 0;

			$products_model->deleteByField(array(
				'product_id' => $product_id,
				'bundle_id' => $bundle_key
			));
		}
		
		if($bundle_groups_settings == 'custom') {
			foreach($bundle_groups as $bundle_id) {
				if(array_key_exists($bundle_id, $bundles))
					$this->saveBundles($product_id, $bundle_id, $bundles[$bundle_id]);
			}
		} elseif($bundle_groups_settings == 'main_category') {
			foreach($bundles as $bundle) {
				$this->saveBundles($product_id, 0, $bundle);
			}
		}
		
		foreach($sort as $bundle_id => $position) {
			$bundle_key = $bundle_id;
			if(intval($bundle_id) < 0)
				$bundle_key = 0;
			
			$sort_model->insert(array(
				'bundle_id' => $bundle_key,
				'product_id' => $product_id,
				'sort' => intval($position)
			), 1);
		}
	}
	
	private function saveBundles($product_id, $bundle_id, $current_bundle)
	{
		$products_model = new shopBundlingProductsModel();
		
		if(!empty($current_bundle['products'])) {
				foreach($current_bundle['products'] as $bundled_product_data) {
					$bundled_product_id = intval($bundled_product_data);
					if(strpos($bundled_product_data, '-') > 0) {
						$bundled_product_id = substr($bundled_product_data, 0, strpos($bundled_product_data, '-'));
						$sku_id = substr($bundled_product_data, strpos($bundled_product_data, '-') + 1);
					} else
						$sku_id = null;
					
					$default_quantity = ifset($current_bundle['quantities'][$bundled_product_data], 1);
					$default_quantity = str_replace(',', '.', $default_quantity);
					$default_quantity = floatval($default_quantity);
					$discount = ifset($current_bundle['discounts'][$bundled_product_data], 0);
					$sorts = ifset($current_bundle['sort'][$bundled_product_data], 0);
					$params = ifset($current_bundle['params'][$bundled_product_data], null);

					if(is_array($params))
						$params = json_encode($params);
					else
						$params = null;

					$products_model->insert(array(
						'product_id' => $product_id,
						'bundle_id' => $bundle_id,
						'bundled_product_id' => $bundled_product_id,
						'sku_id' => $sku_id,
						'default_quantity' => $default_quantity,
						'discount' => $discount,
						'sort' => $sorts,
						'params' => $params
					), 1);
				}
		}
	}
}