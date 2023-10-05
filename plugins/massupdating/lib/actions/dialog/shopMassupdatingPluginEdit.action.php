<?php

/*
 * mail@shevsky.com
 */
 
class shopMassupdatingPluginEditAction extends shopMassupdatingDialog
{
	public function getSkusSummary($product_ids, $products)
	{
		$product_skus_model = new shopProductSkusModel();
		
		$skus_summary = array();
		$skus_summary['count'] = array();
		foreach($product_ids as $id) {			
			$skus = $product_skus_model->getByField('product_id', $id, true);
			foreach($skus as $sku) {
				$skus_summary['price'][] = shop_currency($sku['price'], $products[$id]['currency'], shopMassupdatingPlugin::getDefaultCurrency(), false);
				$skus_summary['primary_price'][] = shop_currency($sku['primary_price'], $products[$id]['currency'], shopMassupdatingPlugin::getDefaultCurrency(), false);
				$skus_summary['purchase_price'][] = shop_currency($sku['purchase_price'], $products[$id]['currency'], shopMassupdatingPlugin::getDefaultCurrency(), false);
				$skus_summary['compare_price'][] = shop_currency($sku['compare_price'], $products[$id]['currency'], shopMassupdatingPlugin::getDefaultCurrency(), false);
				if($sku['count'] != '')
					$skus_summary['count'][] = $sku['count'];
			}
		}
		
		return $skus_summary;
	}
	
	public function findSimilarTags(array $tags_array)
	{
		$counting = array();
		$tags = array();
		$count = count($tags_array);
		foreach($tags_array as $tags) {
			if(count($tags) == 0)
				return false;
			else {
				foreach($tags as $id => $tag) {
					$tags[$id] = $tag;
					if(!empty($tag))
						if(!isset($counting[$id]))
							$counting[$id] = 1;
						else
							$counting[$id]++;
				}
			}
		}
		
		$similar_keys = array_keys($counting, $count);
		$similar = count($similar_keys) > 0 ? array_intersect_key($tags, array_flip($similar_keys)) : array();
		
		return $similar;
	}
	
	public function execute()
	{
		$action = waRequest::get('do', 'massupdating', 'string');
		$inputs = $this->plugin->inputs;
		
		$app_info = wa()->getAppInfo('shop');
		if($app_info['version'] < 7)
			unset($inputs['video_url']);
		
		if(empty($inputs[$action]))
			$action = 'massupdating';
		$selected_inputs = array();
		if($action == 'massupdating')
			$selected_inputs = $inputs;
		else
			$selected_inputs[$action] = $inputs[$action];
		
		$values = array();
		
		if(isset($selected_inputs['tags']))
			$product_tags_model = new shopProductTagsModel();
		
		$this->products = array();
		foreach($this->product_ids as $product_id) {
			$product = new shopProduct($product_id);
			$this->products[$product_id] = $product;
			
			foreach($selected_inputs as $key => $input) {
				$values[$key][] = isset($product[$key]) ? $product[$key] : '';
				if($key == 'prices' || $key == 'currencies')
					$values[$key]['currency'][] = isset($product['currency']) ? $product['currency'] : $this->plugin->getDefaultCurrency(false);
			}
		}

		foreach($values as $key => $value) {
			if(gettype($value) == 'array') {
				if(count($this->product_ids) == count($value) && $key != 'tags' && $key != 'currencies') {
					if($key == 'params' || $key == 'skus')
						$value = array_map('json_encode', $value);

					$unique = array_unique($value, $key == 'features' ? SORT_REGULAR : SORT_STRING);
					if(count($unique) == 1) {
						$selected_inputs[$key]['value'] = $key == 'params' ? json_decode($unique[0], true) : $unique[0];
					} else
						$selected_inputs[$key]['different'] = true;
				} elseif($key == 'prices' || $key == 'currencies') {
					$unique = array_unique($value['currency']);
					$selected_inputs[$key]['currency'] = $unique;
				} else
					$selected_inputs[$key]['different'] = true;
			}
		}

		if(isset($selected_inputs['prices']) || isset($selected_inputs['skus'])) {
			$skus = $this->getSkusSummary($this->product_ids, $this->products);
			
			if(isset($selected_inputs['prices']))
				$selected_inputs['prices']['value'] = $skus;
			
			if(isset($selected_inputs['skus'])) {
				$stock_model = new shopStockModel();
				
				$selected_inputs['skus']['stocks'] = $stock_model->getAll();
				$selected_inputs['skus']['value'] = $skus;
			}
		}
		
		if(isset($selected_inputs['subpages'])) {
			$pages_model = new shopProductPagesModel();
			$pages = $pages_model->query("SELECT * FROM {$pages_model->getTableName()} WHERE product_id IN (" . implode(',', $this->product_ids) . ")")->fetchAll();
			foreach($pages as &$page) {
				$page['product_name'] = ifset($this->products[$page['product_id']]['name']);
			}
			$selected_inputs['subpages']['value'] = $pages;
		}
		
		if(isset($selected_inputs['tags']))
			$selected_inputs['tags']['value']['tags'] = $this->findSimilarTags($values['tags']);
		
		if(isset($selected_inputs['badge'])) {
			if(isset($selected_inputs['badge']['value']) && !in_array($selected_inputs['badge']['value'], array('new', 'bestseller', 'lowprice'))) {
				$selected_inputs['badge']['custom'] = $selected_inputs['badge']['value'];
				$selected_inputs['badge']['value'] = 'custom';
			};
		} 
		
		if(isset($selected_inputs['features'])) {
			$default_features_ids = $this->plugin->getSettings('features');
			$default_features = $this->plugin->getFeaturesControls($default_features_ids, $this->product_ids);
			// $features_for_all_types = $plugin->getFeaturesForAllTypes('all', $this->product_ids);
			// $features_for_all_types = array_diff_key($features_for_all_types, $default_features);

			$this->view->assign('default_features', $default_features);
			// $this->view->assign('features_for_all_types', $features_for_all_types);
			
			$selected_inputs['features']['features'] = $this->plugin->getFeaturesWithoutDefault();
		}
		
		if(isset($selected_inputs['tax_id'])) {
			$tax_model = new shopTaxModel();
			$taxes = $tax_model->getAll();
			$this->view->assign('taxes', $taxes);
		}
		
		$this->view->assign('title', _wp('Массовое редактирование') . ($action == 'massupdating' ? '' : (' <b>"' . $inputs[$action]['name'] . '"</b>')));
		
		$post_max_size = $this->plugin->iniGet('post_max_size');;
		$upload_max_filesize = $this->plugin->iniGet('upload_max_filesize');
		
		$this->view->assign('post_max_size', $post_max_size);
		$this->view->assign('upload_max_filesize', $upload_max_filesize);
		$this->view->assign('post_max_size_string', waFiles::formatSize($post_max_size, '%0.0f', 'Б,кБ,МБ,ГБ'));
		$this->view->assign('upload_max_filesize_string', waFiles::formatSize($upload_max_filesize, '%0.0f', 'Б,кБ,МБ,ГБ'));
		
		$this->view->assign('action', $action);
		$this->view->assign('inputs', $selected_inputs);
		
		$this->view->assign('products', $this->products);
	}
}