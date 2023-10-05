<?php

class shopComplexPluginPriceModel extends waModel
{
	protected $table = 'shop_complex_price';
	
	public function getPriceFields()
	{
		$rows = $this->query("SELECT CONCAT('complex_plugin_price_', id) AS field FROM {$this->getTableName()} ORDER BY sort ASC")->fetchAll('field');
		
		return array_keys($rows);
	}
	
	public function getPrices($enabled = false, $sort = 'sort')
	{
		$where = '';
		
		if($enabled)
			$where = ' WHERE `status` = 1';
		
		return $this->query("SELECT * FROM {$this->getTableName()} {$where} ORDER BY `{$sort}` ASC")->fetchAll('id');
	}
	
	public function workupPriceRules(&$prices, $single = false)
	{
		if(empty($prices))
			return false;
		
		if($single)
			$prices = array(
				$prices
			);
		
		$rule_actions_instance = new shopComplexPluginRuleActions();

		$rules = array();
		
		foreach($prices as &$price) {
			if(empty($rules[$price['rule_id']])) {
				$rule_actions_instance->setId($price['rule_id']);
				$output = $rule_actions_instance->returnRun('view');
				$output_simple = $rule_actions_instance->returnRun('viewSimple');
				$is_rule_found = $rule_actions_instance->isRuleFound();
				
				$rules[$price['rule_id']] = array(
					'output' => $output,
					'output_simple' => $output_simple,
					'is_rule_found' => $is_rule_found
				);
			}
			
			$price['rule'] = $rules[$price['rule_id']]['output'];
			$price['rule_simple'] = $rules[$price['rule_id']]['output_simple'];
			$price['rule_not_found'] = !$rules[$price['rule_id']]['is_rule_found'];
		}
		
		if($single) {
			$prices = $prices[0];
			return $prices;
		}
	}
	
	public function create($data)
	{
		$data['sort'] = $this->query("SELECT MAX(sort) AS max FROM {$this->getTableName()}")->fetchField('max');
		$data['sort'] = intval($data['sort']) + 1;
		$id = $this->insert($data);

		try {
			$product_skus_model = new shopProductSkusModel();
			$product_skus_model->exec("ALTER TABLE `{$product_skus_model->getTableName()}` ADD `complex_plugin_price_{$id}` DECIMAL(15,4) NOT NULL DEFAULT '0.0000';");
			$product_skus_model->exec("ALTER TABLE `{$product_skus_model->getTableName()}` ADD `complex_plugin_type_{$id}` ENUM('', '+', '%', '-','-%') NOT NULL DEFAULT '';");
			$product_skus_model->exec("ALTER TABLE `{$product_skus_model->getTableName()}` ADD `complex_plugin_from_{$id}` INT(1) NOT NULL DEFAULT '0';");
		} catch(waDbException $e) { }
		
		return $id;
	}	
	
	public function getName($id)
	{
		return $this->query("SELECT `name` FROM `{$this->getTableName()}` WHERE id = ?", intval($id))->fetchField('name');
	}
	
	public function deletePrice($id)
	{
		$id = intval($id);
		
		$price = $this->getById($id);
		
		$rule_model = new shopComplexPluginRuleModel();
		$rule_model->deleteRule($price['rule_id']);
		
		try {
			$product_skus_model = new shopProductSkusModel();
			$product_skus_model->exec("ALTER TABLE `{$product_skus_model->getTableName()}` DROP `complex_plugin_price_{$id}`;");
			$product_skus_model->exec("ALTER TABLE `{$product_skus_model->getTableName()}` DROP `complex_plugin_type_{$id}`;");
			$product_skus_model->exec("ALTER TABLE `{$product_skus_model->getTableName()}` DROP `complex_plugin_from_{$id}`;");
		} catch(waDbException $e) { }
		
		$this->deleteById($id);
	}
	
	public function isEnabled($id)
	{
		$price = $this->getById($id);
		
		if(!empty($price['status']))
			return true;
		else
			return false;
	}
}