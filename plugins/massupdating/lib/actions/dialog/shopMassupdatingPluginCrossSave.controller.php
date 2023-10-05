<?php

/*
 * mail@shevsky.com
 */

class shopMassupdatingPluginCrossSaveController extends waJsonController
{
	private function types($data)
	{
		foreach($data as $key => $value) {
			if($value == 3) $data[$key] = 2;
		};
		
		return $data;
	}
	
	public function execute()
	{
		$product_ids = waRequest::post('product_id', array(), 'array_int');
		$hash = waRequest::post('hash', '');
		if((count($product_ids) == 0 && !$hash) || waRequest::post('_csrf') != waRequest::cookie('_csrf'))
			$this->setError(_wp('Ошибка при отправке данных'));
		else {
			$request = waRequest::request();
			
			$plugin = wa('shop')->getPlugin('massupdating');
			$inputs = $plugin->inputs;
			
			if($hash) {
				$product_ids = array();
				
				$offset = 0;
				$count = 100;
				
				$types = $plugin->getTypes();
				
				if($types === false)
					$this->setError(_wp('Доступ к редактированию выбранных товаров запрещен'));
				
				$collection = new shopProductsCollection(urldecode($hash));
				if(is_array($types))
					$collection->addWhere('p.type_id IN ('.implode(',', $types).')');
				
				$total_count = $collection->count();
				$collection->orderBy('id');
				
				while($offset < $total_count) {
					$products = $collection->getProducts('id', $offset, $count);
					$product_ids = array_merge($product_ids, array_keys($products));
					$offset += count($products);
				}
			}
			
			if(count($product_ids) > 0 && $this->update($product_ids, $request))
				$this->response = true;
			else $this->setError(_wp('Неизвестная ошибка'));
		}
	}

	public function update($products, $data)
	{
		$product_model = new shopProductModel();
		$plugin = wa('shop')->getPlugin('massupdating');
		$allowed_product_ids = $plugin->filterAllowedProducts($products);

		if(count($allowed_product_ids) != count($products)) {
			$this->setError(_wp('Доступ к редактированию выбранных товаров запрещен'));
			return false;
		};
		$allowed_products = $product_model->select('id')->where('id IN (i:ids)', array('ids' => $allowed_product_ids))->fetchAll('id');
		
		$cross = array('cross_selling', 'upselling');
		
		$related_model = new shopProductRelatedModel();
		foreach($allowed_products as $id => $product) {
			foreach($cross as $type) {
				if(isset($data[$type]) && in_array($data[$type], array('0', '1', '2', '3', 'null'))) {
					if($data[$type] == 'null')
						unset($data[$type]);
					elseif($data[$type] == 2) {
						$related_model->deleteByField(array(
							'product_id' => $id,
							'type' => $type
						));
						
						if(isset($data[$type . '_custom'])) {
							$related_product_ids = explode(',', $data[$type . '_custom']);
							
							foreach($related_product_ids as $_id) {
								if($id != $_id && $_id != 0)
									$related_model->insert(array(
										'product_id' => $id,
										'type' => $type,
										'related_product_id' => $_id
									), 1);
							};
						}
					} elseif($data[$type] == 3) {
						if(!empty($data[$type . '_delete'])) {
							$related_model->deleteByField(array(
								'product_id' => $id,
								'type' => $type
							));
						}
						foreach($allowed_products as $_id => $_product) {
							if($id != $_id)
								$related_model->insert(array(
									'product_id' => $id,
									'type' => $type,
									'related_product_id' => $_id
								), 1);
						}
					} else {
						$related_model->deleteByField(array(
							'product_id' => $id,
							'type' => $type
						));
					}
				}
			}
			
			$product_model->updateById($product, $this->types($data));
		};
		
		return true;
	}
}