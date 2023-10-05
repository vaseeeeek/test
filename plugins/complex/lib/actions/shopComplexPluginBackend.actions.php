<?php

class shopComplexPluginBackendActions extends shopComplexPluginActions
{
	public function disableTransferBlockAction()
	{
		$this->setJson();
		
		wao(new waAppSettingsModel())->set(array('shop', 'complex'), 'transfer', '0');
	}
	
	public function newAction()
	{
		$this->setJson();
		
		$name = waRequest::post('name');
		$rule_id = waRequest::post('rule_id', 0, 'int');
		$default_style = waRequest::post('default_style', 0, 'int');
		$default_from = waRequest::post('default_from', 0, 'int');
		$default_value = waRequest::post('default_value', 0);
		$default_value = floatval($default_value);
		$rounding = waRequest::post('rounding', 0);
		
		if(empty($name)) {
			$this->setError(_wp('Enter price name'));
			return false;
		}
		
		if(empty($this->id)) {
			$rule_model = new shopComplexPluginRuleModel();
			$rule = $rule_model->getById($rule_id);

			if(empty($rule)) {
				$this->setError(_wp('Set price rules'));
				return false;
			}
		}
		
		if(!in_array($default_style, array(0, 1, -1)) || !in_array($default_from, array(0, 1, -1)) || !in_array($rounding, array('100', '99', '10', '1', '0.99', '0.1', '0.01', '0'))) {
			$this->setError(_wp('Form sending error'));
			return false;
		}
		
		if($default_style != 0 && empty($default_value)) {
			$this->setError(sprintf('%s %s', _wp('Set the size of'), $default_style == 1 ? _wp('nacenki') : _wp('skidki')));
			return false;
		}
		
		$price_model = new shopComplexPluginPriceModel();
		$data = array(
			'name' => $name,
			'default_style' => $default_style,
			'default_from' => $default_from,
			'default_value' => $default_value,
			'rounding' => $rounding
		);
		
		if(!empty($this->id)) {
			$price_model->updateById($this->id, $data);
		} else {
			$data['rule_id'] = $rule_id;
			$this->id = $price_model->create($data);
		}
		
		$output = $this->returnRun('priceTypeView');
		$this->response = array(
			'id' => $this->id,
			'output' => $output
		);
	}
	
	public function editAction()
	{
		$this->setJson();
		
		$this->id = waRequest::post('id', 0, 'int');
		
		if(empty($this->id)) {
			$this->setError(_wp('Price type not found'));
			return false;
		}
		
		$this->newAction();
	}
	
	public function deleteAction()
	{
		$this->setJson();
		
		$this->id = waRequest::post('id', 0, 'int');
		
		if(empty($this->id)) {
			$this->setError(_wp('Price type not found'));
			return false;
		}
		
		$price_model = new shopComplexPluginPriceModel();
		
		$this->response = $price_model->deletePrice($this->id);
	}
	
	public function saveSortAction()
	{
		$this->setJson();
		
		$sort = waRequest::post('sort', array(), 'array_int');
		
		$price_model = new shopComplexPluginPriceModel();
		
		foreach($sort as $id => $position)
			$price_model->updateById($id, array(
				'sort' => $position
			));
	}
	
	public function priceTypeViewAction()
	{
		$id = ifset($this->id, waRequest::post('id', 0, 'int'));
		
		$price_model = new shopComplexPluginPriceModel();
		$price = $price_model->getById($id);
		$price_model->workupPriceRules($price, true);
		
		$this->view->assign('price', $price);
	}
	
	public function toggleStatusAction()
	{
		$this->setJson();
		
		$id = waRequest::post('id', 0, 'int');
		$status = waRequest::post('status', 1, 'int');
		
		if($id) {
			$model = new shopComplexPluginPriceModel();
			$model->updateById($id, array(
				'status' => $status == 0 ? 0 : 1
			));
		}
	}
	
	public function getRegionsAction()
	{
		$this->setJson();
		
		$country = waRequest::get('country');
		
		if(!$country) {
			$this->setError(_wp('Select country!'));
			return false;
		}
		
		$model = new waRegionModel();
		
		$regions = $model->getByCountry($country);
		
		if(!$regions) {
			$this->setError('empty');
			return false;
		} else {
			$return = array();
			
			foreach($regions as $region)
				$return[$region['code']] = $region['name'];
			
			$this->response = $return;
		}
	}
	
	public function getShippingRatesAction()
	{
		$this->setJson();
		
		$shipping_id = waRequest::get('shipping_id');
		
		if(!$shipping_id) {
			$this->setError(_wp('Select shipping!'));
			return false;
		}
		
		if($rates = shopComplexPluginControls::takeShippingRates($shipping_id))
			$this->response = $rates;
		else
			$this->setError(_wp('Can\'t found shipping rates'));
	}
	
	public function rulesDialogAction()
	{
		$selected = waRequest::get('selected', 0, 'int');
		
		$form_action = $selected ? 'edit' : 'new';
		
		$this->view->assign('selected', $selected);
		$this->view->assign('form_action', $form_action);
		
		if($form_action == 'edit') {
			$rule_actions_instance = new shopComplexPluginRuleActions();
			$rule_actions_instance->setId($selected);
			$rule_actions_instance->setViewType('edit');
			$rule = $rule_actions_instance->returnRun('view');
			
			$this->view->assign('rule', $rule);
		}
	}
	
	public function transferDialogAction()
	{		
		$this->view->assign('transfer', shopComplexPlugin::getTransferPlugins());
	}
	
	public function transferAction()
	{
		$this->setJson();
		
		
	}
	
	public function addConditionAction()
	{
		$depth = waRequest::get('depth', 0, 'int');
		$this->view->assign('depth', $depth);
		
		$control_groups = $this->plugin->getControlsInstance()->getAllControlGroups();
		$this->view->assign('control_groups', $control_groups);
	}
	
	public function fieldValuesAction()
	{
		$type = waRequest::get('type');
		
		if(!($values = $this->plugin->getControlsInstance()->getFieldValues($type))) {
			$this->view->display('string:' . _wp('Field type') . ' ' . $type . ' ' . _wp('is not found'));
			return false;
		};
		
		$this->view->assign('type', $type);
		$this->view->assign('id', 'complex-field-' . md5(microtime()));
		
		$this->view->assign('values', $values);
		$this->view->assign('type', $type);
	}
	
	public function featureValuesAction()
	{
		$this->setJson();
		
		$feature_id = waRequest::get('feature_id');
		
		if(!$feature_id) {
			$this->setError(_wp('Choose a feature!'));
			
			return false;
		}
		
		$model = new shopFeatureModel();
		
		if(!($feature = $model->getById($feature_id))) {
			$this->setError(_wp('Feature is not found'));
			
			return false;
		}
		
		if($feature['selectable']) {
			$type = preg_replace('/\..*$/', '', $feature['type']);
			
			if(in_array($type, array('color', 'dimension'))) {
				$values_model = shopFeatureModel::getValuesModel($feature['type']);
				$values_params = $values_model->getByField('feature_id', $feature_id, true);
				$values = array();

				switch($type) {
					case 'color':
						foreach($values_params as $value)
							$values[$value['id']] = $value['value'];
						break;
					case 'dimension':
						foreach($values_params as $value) {
							$dimension_value = new shopDimensionValue($value);
							
							$values[$value['id']] = $dimension_value->html;
						}
						
						break;
				}
			} else
				$values = $model->getFeatureValues($feature);

			$this->response = $values;
		}
	}
	
	public function getCurrency()
	{
		$currency = wa('shop')->getConfig()->getCurrency();
		
		return $currency;
	}
	
	public function getProductSkusAction()
	{
		$this->setJson();
		
		$id = waRequest::get('id', 0, 'int');
		$product = new shopProduct($id);
		
		if(!$product) {
			$this->setError(_wp('Product not found'));
			return false;
		}
		
		$currency = $this->getCurrency();
		
		$skus = $product['skus'];
		
		$price_model = new shopComplexPluginPriceModel();
		$prices = $price_model->getPrices(true);

		$original_skus = array();
		$complex_skus = array();
		
		foreach($prices as $price_id => $price) {
			$complex_sku = array();
			
			foreach($skus as $sku) {
				$this->workupProductSku($sku, $price_id, $price, $product['currency']);
				
				$final_price = $sku['primary_price'];
				$html_price = wa_currency_html($final_price, $currency);
				
				$complex_sku[$sku['id']] = array(
					$final_price, $html_price
				);
			}
			
			if(count($skus) == 1) {
				$complex_sku = array_shift($complex_sku);
			}
			
			$complex_skus[$price_id] = $complex_sku;
		}
		
		foreach($skus as $sku) {
			$final_price = $sku['primary_price'];
			$html_price = wa_currency_html($final_price, $currency);
			
			$original_skus[$sku['id']] = array(
				$final_price, $html_price
			);
		}
		
		if(count($skus) == 1) {
			$original_skus = array_shift($original_skus);
		}
		
		$this->response = array(
			'original' => $original_skus,
			'complex' => $complex_skus
		);
	}
	
	private function workupProductSku(&$sku, $price_id, $price, $currency)
	{
		extract(shopComplexPluginProduct::getPriceFields($price_id));
		$complex_price = $sku[$price_field];
		$type = $sku[$type_field];
		$from = $sku[$from_field];
		
		if((isset($sku[$price_field]) && floatval($sku[$price_field]) != 0) || !empty($price['default_style'])) {
			if(floatval($complex_price) == 0 && !empty($price['default_style']) && !empty($price['default_value'])) {
				$complex_price = $price['default_value'];

				switch($price['default_style']) {
					case 1:
						$type = '%';
						break;
					case -1:
						$type = '-%';
						break;
				}

				$from = $price['default_from'];
			}
			
			switch($from) {
				case 1:
					$original = floatval($sku['compare_price']);
					break;
				case -1:
					$original = floatval($sku['purchase_price']);
					break;
				default:
					$original = floatval($sku['price']);
					break;
			}
			
			if($original == 0 && $type != '')
				return false;
			
			$final_price = shopComplexPluginProduct::getPrice($original, $complex_price, $type, false);
			
			if($final_price != 0.0) {
				if($currency != $this->getCurrency())
					$final_price = shop_currency($final_price, $currency, $this->getCurrency(), null);
				
				$final_price = shopComplexPluginProduct::roundPrice($price_id, $final_price, $price);
				
				$sku['primary_price'] = $final_price;
			}
		}
	}
}