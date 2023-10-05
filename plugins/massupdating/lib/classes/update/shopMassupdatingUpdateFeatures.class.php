<?php

class shopMassupdatingUpdateFeatures
{
	public function __construct()
	{
		$this->product_features_model = new shopProductFeaturesModel();
	}
	
	public function update($params)
	{
		extract($params);
		
		$allowed_features = shopMassupdatingPlugin::getFeatures(true, true);
				
		$__product = wao(new shopProductModel())->getById($id);
		$__product['features_selectable'] = array();
		$_product = new shopProduct($__product);
		
		foreach($features as $code => $value) {
			$type = $allowed_features[$code]['type'];
			
			if(!empty($new_features[$code]))
				$features[$code] = $new_features[$code];
			
			if(!empty($new_multiple_features[$code]) && gettype($value) == 'array') {
				foreach($new_multiple_features[$code] as $new_value) {
					if(!empty($new_value))
						$features[$code][] = $new_value;
				}
			}
			
			if(($features[$code] == '' && !$this->update_empty && $type != 'boolean') || !isset($allowed_features[$code]) ||
					(
						gettype($value) == 'array' && $allowed_features[$code]['selectable'] && $allowed_features[$code]['multiple'] && count($features[$code]) == 1 && !$this->update_empty // multiple и selectable, то есть groupbox
					) || (
						strpos($type, 'range') === 0 && empty($value['value']['begin']) && empty($value['value']['end']) && !$this->update_empty
					) || (
						strpos($type, 'dimension.*') === 0 && empty($value['value']) && !$this->update_empty
					) || (
						strpos($type, '2d.dimension') === 0 && (empty($features[$code . '.0']['value']) || empty($features[$code . '.1']['value'])) && !$this->update_empty
					) || (
						strpos($type, '3d.dimension') === 0 && (empty($features[$code . '.0']['value']) || empty($features[$code . '.1']['value']) || empty($features[$code . '.2']['value'])) && !$this->update_empty
					) || (
						$type == '2d.double' && (empty($features[$code . '.0']) || empty($features[$code . '.1'])) && !$this->update_empty
					) || (
						$type == '3d.double' && (empty($features[$code . '.0']) || empty($features[$code . '.1']) || empty($features[$code . '.2'])) && !$this->update_empty
					)
					) {
						unset($features[$code]);
			} else {
				if(gettype($value) == 'array' && $allowed_features[$code]['selectable'] && $allowed_features[$code]['multiple']) {
							$action = ifset($feature_action[$code], 'replace');
						
							if(!in_array($action, array('replace', 'add')))
								$action = 'replace';
							
							if($action == 'add') {
								$values = $this->plugin->getFeatureValue($code, $id);
								
								if(gettype($values) == 'array' && count($values) > 0) {
									$features[$code] = array_merge($features[$code], $values);
								}
							}
						}
			}
		}
		
		return $this->product_features_model->setData($_product, $features);
	}
}