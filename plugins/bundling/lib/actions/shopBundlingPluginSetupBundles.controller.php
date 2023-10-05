<?php

class shopBundlingPluginSetupBundlesController extends waJsonController
{
	public function preExecute()
	{		
		$this->plugin = wa('shop')->getPlugin('bundling');
		$this->model = $this->plugin->model;
	}
	
	public function execute()
	{
		$main_product_ids = waRequest::request('main_product_ids', '');
		$main_product_ids = explode(',', $main_product_ids);
		$bundled_product_ids = waRequest::request('bundled_product_ids', '');
		$bundled_product_ids = explode(',', $bundled_product_ids);
		$bundled_product_ids = array_diff($bundled_product_ids, $main_product_ids);
		
		$bundle_groups = $this->plugin->getSettings('bundle_groups');
		
		if(count($main_product_ids) && count($bundled_product_ids)) {
			$main_product_groups = waRequest::request('main_product_group', array(), 'array_int');
			$groups_count = array_count_values($main_product_groups);
			if(ifset($groups_count[0]) == count($main_product_groups) && $bundle_groups == 'custom')
				$this->setError('Укажите группы для привязки комплектующих');
			else {
				$main_product_group_titles = waRequest::request('main_product_group_title', array(), 'array');
				$main_product_group_multiples = waRequest::request('main_product_group_multiple', array(), 'array');
				
				if(isset($groups_count[-1]))
					foreach($main_product_groups as $product_id => $group_id)
						if($group_id == -1)
							if(empty($main_product_group_titles[$product_id])) {
								$this->setError('Задайте наименование группы');
								return false;
							} else {
								$main_product_groups[$product_id] = $this->model->insert(array(
									'product_id' => $product_id,
									'multiple' => isset($main_product_group_multiples[$product_id]) ? 1 : 0,
									'title' => $main_product_group_titles[$product_id],
								));
							}
							
				$bundled_products = waRequest::request('bundled_product', array(), 'array_int');
				$product_skus = waRequest::request('product_skus', array(), 'array');
				$discounts = waRequest::request('discount', array(), 'array');
				$default_quantities = waRequest::request('default_quantity', array(), 'array');
				
				foreach($product_skus as $product_id => $skus)
					foreach($skus as $sku_id) {
						$discount = intval(ifempty($discounts[$product_id][$sku_id], 0));
						$default_quantity = ifempty($default_quantities[$product_id][$sku_id], 1);
						$default_quantity = str_replace(',', '.', $default_quantity);
						$default_quantity = floatval($default_quantity);
						
						foreach($main_product_groups as $main_product_id => $group_id)
							$this->model->products_model->insert(array(
								'product_id' => $main_product_id,
								'bundle_id' => $group_id,
								'bundled_product_id' => $product_id,
								'sku_id' => $sku_id,
								'default_quantity' => $default_quantity,
								'discount' => $discount
							), 1);
					}
			}
		} else
			$this->setError('Товары не найдены!');
	}
}