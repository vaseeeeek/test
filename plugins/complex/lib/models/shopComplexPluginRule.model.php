<?php

class shopComplexPluginRuleModel extends waModel
{
	protected $table = 'shop_complex_rule';
	
	public function getRuleJoinConditions($id)
	{
		$rule = $this->getById($id);
		
		$condition_group_model = new shopComplexPluginConditionGroupModel();
		$rule['condition_mode'] = $condition_group_model->getMode($rule['condition_group_id']);
		$rule['conditions'] = $condition_group_model->getConditions($rule['condition_group_id']);

		return $rule;
	}
	
	public function getRule($id)
	{
		return $this->getRules(intval($id));
	}
	
	public function getRules($rules = null, $selected = null) {
		$controls_instance = new shopComplexPluginControls();
		$condition_model = new shopComplexPluginConditionModel();
		$condition_group_model = new shopComplexPluginConditionGroupModel();
		
		if(is_int($rules)) {
			$rule = $this->getById($rules);
			
			if(!$rule)
				return null;
			
			$rules = array(
				$rule
			);
			
			$first = true;
		} elseif(is_null($rules)) {
			$sql = "SELECT * FROM {$this->getTableName()} ORDER BY " . ($selected ? "id={$selected} DESC, " : '') . "id DESC";
			$rules = $this->getRules($this->query($sql)->fetchAll());
		}
		
		foreach($rules as &$rule) {
			$condition_group = $condition_group_model->getById($rule['condition_group_id']);
			$condition_ids = explode(',', $condition_group['conditions']);
			$condition_mode = $condition_group['mode'];
			
			$conditions = $condition_model->getByField('id', $condition_ids, 'id');
			foreach($conditions as &$condition) {
				if($condition['field'] == 'group') {
					$condition['value'] = $condition_group_model->getById($condition['value']);
					$condition['value']['condition_ids'] = explode(',', $condition['value']['conditions']);
					$condition['value']['conditions'] = $condition_model->getByField('id', $condition['value']['condition_ids'], 'id');
					foreach($condition['value']['conditions'] as &$group_condition) {
						$group_condition['field_title'] = $controls_instance->getControlTitle($group_condition['field']);
						$group_condition['value'] = json_decode($group_condition['value'], true);
						
						foreach($group_condition['value'] as $control => &$value)
							$group_condition['control_values'][$control] = $controls_instance->getControlValue($control, $value, $group_condition['value']);
					}
				} else {
					$condition['field_title'] = $controls_instance->getControlTitle($condition['field']);
					$condition['value'] = json_decode($condition['value'], true);
					
					foreach($condition['value'] as $control => &$value)
						$condition['control_values'][$control] = $controls_instance->getControlValue($control, $value, $condition['value']);
				}
			}
			
			$rule['condition_mode'] = $condition_mode;
			$rule['conditions'] = $conditions;
		}
		
		return !empty($first) ? array_shift($rules) : $rules;
	}
	
	public function deleteRule($id)
	{
		$rule = $this->getById($id);
		$condition_group_id = $rule['condition_group_id'];
		
		$this->deleteById($id);

		$condition_group_model = new shopComplexPluginConditionGroupModel();
		$condition_model = new shopComplexPluginConditionModel();
		
		$group = $condition_group_model->getById($condition_group_id);
		$condition_ids = explode(',', $group['conditions']);
		
		$condition_group_model->deleteById($condition_group_id);
		
		foreach($condition_ids as $condition_id) {
			$condition = $condition_model->getById($condition_id);
			if($condition['field'] == 'group') {
				$condition_group_id = $condition['value'];
				
				$group = $condition_group_model->getById($condition_group_id);
				$group_condition_ids = explode(',', $group['conditions']);
				
				$condition_group_model->deleteById($condition_group_id);
				
				foreach($group_condition_ids as $group_condition_id) {
					$condition_model->deleteById($group_condition_id);
				}
			}
			
			$condition_model->deleteById($condition_id);
		}
	}
	
	public function deleteNotUsingRules()
	{
		$sql = "SELECT r.* FROM {$this->getTableName()} AS r LEFT JOIN `shop_complex_price` AS p ON p.rule_id = r.id WHERE p.id IS NULL";
		
		$rows = $this->query($sql)->fetchAll();
		
		foreach($rows as $row)
			$this->deleteRule($row['id']);
	}
}