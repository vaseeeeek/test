<?php

class shopBundlingPluginDialogActions extends waViewActions
{
	public function __construct($params = null) {
		parent::__construct($params);
		
		$this->plugin = wa('shop')->getPlugin('bundling');
		$this->model = $this->plugin->model;
	}
	
	public function setError($error)
	{
		$this->setTemplate('Error');
		$this->view->assign('error', $error);
		$this->view->assign('locale', wa()->getLocale());
		
		return false;
	}
	
	public function preExecute()
	{
		$product_ids = waRequest::request('product_id', array(), 'array_int');
		$hash = waRequest::request('hash', '');
		if(count($product_ids) == 0 && !$hash)
			return $this->setError('zero');
		
		if($hash)
			$product_ids = $this->plugin->getProductIdsByHash($hash);
		
		$this->view->assign('currency', wa('shop')->getConfig()->getCurrency(false));
		$this->view->assign('product_ids', $product_ids);
		
		$this->view->assign('hash', $hash);
		
		$this->hash = $hash;
		$this->product_ids = $product_ids;
	}
	
	public function chooseAction()
	{
		if(count($this->product_ids) > 0) {
			$bundle_groups = $this->plugin->getSettings('bundle_groups');
			$this->view->assign('bundle_groups', $bundle_groups);
		}
	}
	
	public function setAction()
	{
		$first_product_ids = waRequest::request('first_products', '');
		$first_product_ids = explode(',', $first_product_ids);
		$first_hash = waRequest::request('first_hash', '');
		
		$second_product_ids = $this->product_ids;
		$second_hash = $this->hash;
		
		if($second_hash && $first_hash == $second_hash)
			return $this->setError(_wp('You can\'t choose the same product collections.'));
		
		if($first_hash)
			$first_product_ids = $this->plugin->getProductIdsByHash($first_hash);
		
		$bundle_groups = $this->plugin->getSettings('bundle_groups');
		
		if(count($first_product_ids) > 0 && count($second_product_ids) > 0) {
			$second_product_ids = array_diff($second_product_ids, $first_product_ids);
			
			if(count($second_product_ids) > 0) {
				$set = waRequest::request('set');
				
				$this->view->assign('set', $set);
				
				switch($set) {
					case 'set-up':
						$main_product_ids = $first_product_ids;
						$bundled_product_ids = $second_product_ids;
						break;
					case 'set-as':
						$main_product_ids = $second_product_ids;
						$bundled_product_ids = $first_product_ids;
						break;
				}
				
				$main_products = array();
				foreach($main_product_ids as $main_product_id) {
					$p = new shopProduct($main_product_id);
					
					if($bundle_groups == 'main_category')
						$p['bundling_bundles'] = $p['category_id'];
					else
						$p['bundling_bundles'] = $this->model->getBundles('bundle', $p['id']);
					
					$main_products[$main_product_id] = $p;
				}
				
				$bundled_products = array();
				foreach($bundled_product_ids as $bundled_product_id) {
					if(!isset($main_products[$bundled_product_id]))
						$bundled_products[$bundled_product_id] = new shopProduct($bundled_product_id);
				}
				
				$this->view->assign('main_product_ids', $main_product_ids);
				$this->view->assign('main_products', $main_products);
				$this->view->assign('bundled_product_ids', $bundled_product_ids);
				$this->view->assign('bundled_products', $bundled_products);
				$this->view->assign('bundle_groups', $bundle_groups);
			} else
				return $this->setError(_wp('No unique products in one product collection.'));
		} else
			return $this->setError(_wp('No products.'));
	}
	
	public function editProductBundlesAction()
	{
		if(count($this->product_ids) > 0) {
			$data = $this->model->getBundles($this->product_ids);
			if(empty($data['bundles']))
				return $this->setError('no_bundles');
			
			foreach($data as $key => $row)
				$this->view->assign($data, $row);
				
			$bundles = $data['bundles'];
			
			$bundle_products_data = $this->model->products_model->getProductsForProductAndBundle($this->product_ids, array_keys($bundles));
			$bundle_products = array();
			$bundled_ids = array();
			foreach($bundle_products_data as $product) {
				$row = $product['row'];
				
				if($row['sku_id'] != 0)
					$id = $row['bundled_product_id'] . '-' . $row['sku_id'];
				else
					$id = $row['bundled_product_id'];
				$bundled_ids[] = $id;
								
				if(!isset($bundle_products[$row['bundle_id']]))
					$bundle_products[$row['bundle_id']] = array(
						'products' => array(),
						'has' => array($row['product_id']),
						'products_is_similar' => true
					);
				else
					if(!in_array($row['product_id'], $bundle_products[$row['bundle_id']]['has']))
						array_push($bundle_products[$row['bundle_id']]['has'], $row['product_id']);
				
				if(!isset($bundle_products[$row['bundle_id']]['products'][$id]))
					$bundle_products[$row['bundle_id']]['products'][$id] = array(
						'has' => array($row['product_id']),
						'id' => $id,
						'product_id' => $row['bundled_product_id'],
						'sku_id' => $row['sku_id'],
						'product' => $product
					);
				else {
					if(!in_array($row['product_id'], $bundle_products[$row['bundle_id']]['products'][$id]['has']))
						array_push($bundle_products[$row['bundle_id']]['products'][$id]['has'], $row['product_id']);
				}
			}
			
			foreach($bundle_products as $bundle_id => &$data) {
				$has_all_products = array();
				if(count($data['has']) == count($this->product_ids)) {
					foreach($data['products'] as $id => &$product_data) {
						if(count($product_data['has']) == count($this->product_ids) && !in_array($id, $has_all_products))
							$has_all_products[] = $id;
					}
				}

				if(count($has_all_products) != count($data['products']))
					$data['products_is_similar'] = false;
			}

			
			$this->view->assign('bundle_products', $bundle_products);
		}
	}
}
