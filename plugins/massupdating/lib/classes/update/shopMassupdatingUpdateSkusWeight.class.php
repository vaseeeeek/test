<?php

class shopMassupdatingUpdateSkusWeight
{
	public function __construct()
	{
		$this->product_skus_model = new shopProductSkusModel();
	}
	
	public function update($id, $product, $params)
	{
		if(!in_array($params['unit'], array('kg', 'g', 'lbs', 'oz')))
			throw new Exception('Неверно задана единица измерения веса');
	
		$product_features_model = new shopProductFeaturesModel();
		$features_model = new shopFeatureModel();
		$feature = $features_model->getByCode('weight');
		
		if($feature) {
			$feature_id = $feature['id'];
			
			$data = array();
			foreach($product['skus'] as $sku) {
				$sku_id = $sku['id'];
				
				$set_value = shopDimension::getInstance()->convert(floatval($params['to']), 'weight', 'kg', $params['unit']);
				$set_unit = 'kg';
				
				$sku_features = $product_features_model->getValues($id, -$sku_id);
				
				if($params['type'] == 1) {
					if(isset($sku_features['weight'])) {
						$f = $sku_features['weight'];
						if($params['action'] == 'plus')
							$set_value += $f['value_base_unit'];
						elseif($params['action'] == 'minus')
							$set_value = $f['value_base_unit'] - $set_value;
							
						if($f['unit'] != 'kg') {
							$set_value = shopDimension::getInstance()->convert($set_value, 'weight', $f['unit'], 'kg');
							$set_unit = $f['unit'];
						}
					} else {
						if($params['action'] == 'minus')
							$set_value = false;
					}
				} else {
					$set_unit = $params['unit'];
				}

				if($set_value !== false && !empty($params['convert_to_unit']) && in_array($params['unit_for_convert'], array('kg', 'g', 'lbs', 'oz'))) {
					$set_value = shopDimension::getInstance()->convert($set_value, 'weight', $params['unit_for_convert'], $set_unit);
					$set_unit = $params['unit_for_convert'];
				}

				if($set_value !== false) {
					$data['skus'][$sku_id] = $sku;
					$data['skus'][$sku_id]['features']['weight'] = array(
						'value' => $set_value,
						'unit' => $set_unit
					);
				}
			}
			
			$product->save($data);
		} else
			throw new Exception('Характеристика веса (weight) не найдена');
	}
}