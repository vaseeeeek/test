<?php
class shopComplexPluginTransferDefaultController extends waController
{
	public function __construct() {
		$this->getResponse()->addHeader('Content-Type', 'text/event-stream');
		$this->getResponse()->sendHeaders();
		
		$this->ie = waRequest::get('ie') ? true : false;
	}
	
	protected function message($id, $message, $progress = false) {
		if(!$this->ie) {
			$data = array(
				'progress' => $progress
			);
			
			if(is_string($message))
				$data['message'] = _wp($message);
			elseif(is_array($message))
				$data = array_merge($data, $message);
			
			echo "id: $id" . PHP_EOL;
			echo "data: " . json_encode($data) . PHP_EOL;
			echo PHP_EOL;
			
			@ob_flush();
			@flush();
		}
	}
	
	public function execute()
	{
		$transfer = new shopComplexPluginTransfer();
		
		$plugin = waRequest::get('transfer_plugin');
		$price_params = shopComplexPluginTransfer::getPluginPriceParams($plugin);
		
		if(!empty($price_params)) {
			$product_skus_model = new shopProductSkusModel();
			
			$condition_model = new shopComplexPluginConditionModel();
			$condition_group_model = new shopComplexPluginConditionGroupModel();
			$rule_model = new shopComplexPluginRuleModel();
			$price_model = new shopComplexPluginPriceModel();
			
			$i = 1;
			$count = count($price_params);
			foreach($price_params as $price_data) {
				$conditions = array();
				$price_conditions = $price_data['conditions'];
				
				foreach($price_conditions as $condition) {
					$control = $condition['control'];
					unset($condition['control']);
					
					if($control == 'group' && !empty($condition['conditions'])) {
						$group_conditions = array();
						
						foreach($condition['conditions'] as $group_condition) {
							$group_control = $group_condition['control'];
							unset($group_condition['control']);
							
							$group_conditions[] = $condition_model->insert(array(
								'field' => $group_control,
								'value' => json_encode($group_condition)
							));
						}
						
						$group_condition_group_id = $condition_group_model->insert(array(
							'mode' => $condition['mode'],
							'conditions' => implode(',', $group_conditions)
						));
						
						$conditions[] = $condition_model->insert(array(
							'field' => 'group',
							'value' => $group_condition_group_id
						));
					} else
						$conditions[] = $condition_model->insert(array(
							'field' => $control,
							'value' => json_encode($condition)
						));
				}
				
				$condition_group_id = $condition_group_model->insert(array(
					'mode' => $price_data['condition_mode'],
					'conditions' => implode(',', $conditions)
				));
		
				$rule_id = $rule_model->insert(array(
					'condition_group_id' => $condition_group_id,
				));
				
				$price_id = $price_model->create(array(
					'name' => $price_data['name'],
					'rule_id' => $rule_id,
					'default_style' => ifset($price_data['default_style'], 0),
					'default_from' => ifset($price_data['default_from'], 0),
					'default_value' => ifset($price_data['default_value'], 0),
					'default_rounding' => 0
				));

				$price_index = 'complex_plugin_price_' . $price_id;
				$type_index = 'complex_plugin_type_' . $price_id;
				
				$prices_data = shopComplexPluginTransfer::getPluginPrices($plugin, $price_data['id']);
				$prices = $prices_data['data'];
				$price_key = $prices_data['price_key'];
				$type_key = $prices_data['type_key'];
				
				$prices_count = count($prices);
				$percentage = 100 / $count;
				$n = 1;
				
				$status = 'Перенос настроек "' . $price_data['name'] . '"...';
				$start_progress = ($i - 1) * $percentage;
				$max_progress = $i * $percentage;
								
				foreach($prices as $price_row) {
					if(!empty($price_row['sku_id']))
						$id = $price_row['sku_id'];
					else
						$id = $price_row['id'];
					
					$product_id = $price_row['product_id'];
					$price = $price_row[$price_key];
					$type = $price_row[$type_key];
					
					try {
						$sql = "UPDATE `{$product_skus_model->getTableName()}` SET `{$price_index}` = s:price, `{$type_index}` = s:type WHERE `id` = s:sku_id AND `product_id` = s:product_id";
						
						$product_skus_model->exec($sql, array(
							'sku_id' => $id,
							'product_id' => $product_id,
							'price' => $price,
							'type' => $type
						));
					} catch(waDbException $e) {
						waLog::log($e, 'wa-apps/shop/plugins/complex/transfer.log');
					}
					
					$inter_progress = $n / $prices_count;
					$progress = $start_progress + ($inter_progress * $max_progress);
					
					$this->message('message', array(
						'message' => $status,
						'done' => $n,
						'from' => $prices_count
					), $progress);

					$n++;
				}
				
				$i++;
			}
			
			wao(new waAppSettingsModel())->set(array('shop', 'complex'), 'transfer', '0');
			
			$this->message('close', _wp('Transfer is complete'), 100);
		} else {
			$this->message('error', _wp('Parameters for this plugin not found'), 0);
		}
	}
}