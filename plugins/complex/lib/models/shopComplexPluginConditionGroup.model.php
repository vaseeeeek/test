<?php

class shopComplexPluginConditionGroupModel extends waModel
{
	protected $table = 'shop_complex_condition_group';
	
	public function getConditions($id)
	{
		$condition_model = new shopSeopageConditionModel();
		
		$main_group = $this->getById($id);
		$condition_ids = explode(',', $main_group['conditions']);
		$conditions = $condition_model->getByField('id', $condition_ids, true);
		
		foreach($conditions as &$condition) {
			if($condition['field'] == 'group') {
				$group = $this->getById($condition['value']);
				$group_condition_ids = explode(',', $group['conditions']);
				$group_conditions = $condition_model->getByField('id', $group_condition_ids, true);
				
				$condition['mode'] = $group['mode'];
				$condition['conditions'] = $group_conditions;
				foreach($condition['conditions'] as &$_condition)
					$_condition['value'] = json_decode($_condition['value'], 1);
			} else {
				$condition['value'] = json_decode($condition['value'], 1);
				continue;
			}
		}
		
		return $conditions;
	}
	
	public function getMode($id)
	{
		return $this->query("SELECT mode FROM {$this->getTableName()} WHERE id = ?", $id)->fetchField('mode');
	}
}