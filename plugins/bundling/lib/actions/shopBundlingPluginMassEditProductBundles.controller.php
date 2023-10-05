<?php

class shopBundlingPluginMassEditProductBundlesController extends waJsonController
{
	public $product_params = array();
	
	public function getParamsForProductIds($product_ids)
	{
		foreach($product_ids as $product_id) {
			if(empty($this->product_params[$product_id]))
				$this->product_params[$product_id] = array(
					'type_id' => shopBundlingModel::getTypeForProduct($product_id),
					'category_ids' => shopBundlingModel::getCategoriesForProduct($product_id),
					'features' => shopBundlingModel::getFeaturesForProduct($product_id)
				);
		}
		
		return $this->product_params;
	}
	
	public function getAllowedProductIds($product_ids, $bundle_id)
	{
		if(empty($this->bundles[$bundle_id]))
			$this->bundles[$bundle_id] = $this->model->getBundle($bundle_id);

		$product_params = $this->getParamsForProductIds($product_ids);
		$bundle = $this->bundles[$bundle_id];

		if(!is_null($bundle['product_id']))
			return false;
		
		$condition = array(
			'by' => !is_null($bundle['type_id']) ? 'type' : (!is_null($bundle['category_id']) ? 'category' : 'feature'),
			'value' => !is_null($bundle['type_id']) ? $bundle['type_id'] : (!is_null($bundle['category_id']) ? $bundle['category_id'] : $bundle['feature'])
		);
		
		$allowed_product_ids = array();
		foreach($product_params as $id => $params) {
			if(($condition['by'] == 'type' && $condition['value'] == $params['type_id']) || ($condition['by'] == 'category' && in_array($condition['value'], $params['category_ids'])) || ($condition['by'] == 'feature' && in_array($condition['value'], $params['features'])))
				array_push($allowed_product_ids, $id);
		}
		
		return $allowed_product_ids;
	}
	
	public function preExecute() {		
		$this->plugin = wa('shop')->getPlugin('bundling');
		$this->model = $this->plugin->model;
		
		$this->product_model = new shopProductModel();
		$this->category_products_model = new shopCategoryProductsModel();
	}
	
	public static function getProductAndSkuIds($string)
	{
		$product_id = 0;
		$sku_id = 0;

		if(strpos($string, '-') > 0) {
			$params = explode('-', $string);
			$product_id = intval($params[0]);
			$sku_id = intval($params[1]);
		} else
			$product_id = intval($string);
		
		return array(
			'product_id' => $product_id,
			'sku_id' => $sku_id
		);
	}
	
	public function execute()
	{
		$product_ids = waRequest::request('product_id', array(), 'array_int');
		$hash = waRequest::request('hash', '');
		if(count($product_ids) == 0 && !$hash)
			return $this->setError('Ошибка');
		
		if($hash)
			$product_ids = $this->plugin->getProductIdsByHash($hash);
		
		$bundle_ids = waRequest::post('on', array(), 'array_int');
		$bundles = waRequest::post('bundles', array(), 'array');
		$discounts = waRequest::post('discount', array(), 'array');
		$default_quantities = waRequest::post('default_quantity', array(), 'array');
		foreach($bundle_ids as $bundle_id => &$on)
			if($on == 1 && !empty($bundles[$bundle_id])) {
				$add = array_keys($bundles[$bundle_id], 'all');
				$delete = array_keys($bundles[$bundle_id], 'delete');
				$update = array_keys($bundles[$bundle_id], 'update');

				if(count($product_ids) == 1)
					$allowed_product_ids = $product_ids;
				else
					$allowed_product_ids = $this->getAllowedProductIds($product_ids, $bundle_id);

				if($allowed_product_ids) {
					foreach($allowed_product_ids as $product_id) {
						foreach($update as $upd_product) {
							$params = self::getProductAndSkuIds($upd_product);
							$this->model->products_model->updateByField(array(
								'product_id' => $product_id,
								'bundle_id' => $bundle_id,
								'bundled_product_id' => $params['product_id'],
								'sku_id' => $params['sku_id']
							), array(
								'default_quantity' => intval(ifempty($default_quantities[$bundle_id][$upd_product], 1)),
								'discount' => intval(ifempty($discounts[$bundle_id][$upd_product], 0)),
							));
						}
						
						foreach($delete as $del_product) {
							$params = self::getProductAndSkuIds($del_product);
							$this->model->products_model->deleteByField(array(
								'product_id' => $product_id,
								'bundle_id' => $bundle_id,
								'bundled_product_id' => $params['product_id'],
								'sku_id' => $params['sku_id']
							));
						}
						
						foreach($add as $add_product) {
							$params = self::getProductAndSkuIds($add_product);
							$this->model->products_model->insert(array(
								'product_id' => $product_id,
								'bundle_id' => $bundle_id,
								'bundled_product_id' => $params['product_id'],
								'sku_id' => $params['sku_id'],
								'default_quantity' => intval(ifempty($default_quantities[$bundle_id][$add_product], 1)),
								'discount' => intval(ifempty($discounts[$bundle_id][$add_product], 0)),
							), 1);
						}
					}
				}
			} else
				unset($on);
			
	}
}