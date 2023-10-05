<?php

class shopComplexPluginRuleActions extends shopComplexPluginActions
{	
	private function prepareActions()
	{
		$this->modes = waRequest::request('mode', array(), 'array');
		$this->mode = $this->modes[-1];
		
		$this->conditions = waRequest::request('conditions', array(), 'array');
		$this->group_conditions = waRequest::request('group_conditions', array(), 'array');
		
		$this->controls = $this->plugin->getControlsInstance()->getAllControls();
	}
	
	public function setId($id)
	{
		$this->id = intval($id);
	}
	
	public function setViewType($view_type)
	{
		$this->view_type = $view_type;
	}
	
	public function editAction()
	{
		$this->prepareActions();
		$this->setJson();
		$this->id = waRequest::request('id', 0, 'int');
		
		if(empty($this->id)) {
			$this->setError('Не удалось найти это правило', 'no-id');
			return false;
		}
		
		if(!$this->checkConditions('add'))
			return false;
		
		$this->deleteAction();
		$this->newAction();
	}
	
	public function deleteAction()
	{
		$this->setJson();
		
		$id = ifset($this->id, waRequest::get('id', 0, 'int'));
		
		$this->rule_model->deleteRule($id);
	}
	
	public function newAction()
	{
		$this->prepareActions();
		$this->setJson();

		if(empty($this->conditions)) {
			$this->setError(_wp('Set up at least one condition!'), 'at-least-one-condition');
			return false;
		}
		
		if(!$this->checkConditions('add'))
			return false;
		
		foreach($this->conditions as $key => $condition) {
			if(empty($condition['control']))
				continue;

			$control = $condition['control'];
			unset($condition['control']);
			
			if(array_key_exists($control, $this->controls)) {
				$conditions[] = $this->condition_model->insert(array(
					'field' => $control,
					'value' => json_encode($condition)
				));
			} elseif(preg_match('/^group:[0-9]+$/', $control)) {
				preg_match('/^group:([0-9])+$/', $control, $group_matches);
				$group_key = $group_matches[1];
				
				$current_group_conditions = array();
				foreach($this->group_conditions[$group_key] as $group_condition) {
					$group_condition_control = $group_condition['control'];
					unset($group_condition['control']);

					$current_group_conditions[] = $this->condition_model->insert(array(
						'field' => $group_condition_control,
						'value' => json_encode($group_condition)
					));
				}
				
				$group_id = $this->condition_group_model->insert(array(
					'mode' => !empty($this->modes[$group_key]) ? $this->modes[$group_key] : 'and',
					'conditions' => implode(',', $current_group_conditions)
				));
				
				$conditions[] = $this->condition_model->insert(array(
					'field' => 'group',
					'value' => $group_id
				));
			}
		}
		
		$condition_group_id = $this->condition_group_model->insert(array(
			'mode' => $this->mode,
			'conditions' => implode(',', $conditions)
		));
					
		$rule_data = array(
			'condition_group_id' => $condition_group_id,
		);
		
		if(!empty($this->id))
			$rule_data['id'] = $this->id;
		
		$this->id = $this->rule_model->insert($rule_data);
		
		$output = $this->returnRun('view');
		$this->response = array(
			'id' => $this->id,
			'output' => $output
		);
	}
	
	public function isRuleFound()
	{
		return ifset($this->is_rule_found, false);
	}
	
	public function viewEditAction()
	{
		$this->view_type = 'edit';
		$this->viewAction();
	}
	
	public function viewAction()
	{
		$id = ifset($this->id, waRequest::get('id', 0, 'int'));
		$view_type = ifset($this->view_type, waRequest::get('view_type', 'view'));
		
		$rule = $this->rule_model->getRule($id);
		
		if(!$rule) {
			$this->is_rule_found = false;
			$this->setTemplate('string:' . _wp('Rule not found. Maybe it was deleted.'));
			
			return false;
		} else
			$this->is_rule_found = true;
		
		$this->view->assign('view_type', $view_type);
		
		if(in_array($view_type, array('copy', 'edit'))) {
			$control_groups = $this->plugin->getControlsInstance()->getAllControlGroups();
			$this->view->assign('control_groups', $control_groups);
		}
		
		$this->view->assign('rule', $rule);
	}
	
	public function viewSimpleAction()
	{
		$this->viewAction();
	}
	
	public function preExecute()
	{
		parent::preExecute();

		$this->condition_model = new shopComplexPluginConditionModel();
		$this->condition_group_model = new shopComplexPluginConditionGroupModel();
		$this->rule_model = new shopComplexPluginRuleModel();
	}
	
	public function parseConditionValues($values) {
		unset($values['control']);
		
		if(array_key_exists('shipping', $values) && !array_key_exists('rates', $values))
			$values['rates'] = '';
		
		return json_encode($values);
	}
	
	public function checkCondition($control_key, $values, $key)
	{
		if(in_array($control_key, array('product.any')))
			return true;
		
		if(array_key_exists($control_key, $this->controls)) {
			$control = $this->controls[$control_key];
			if(is_array($control))
				$type = $control['type'];
			else
				$type = 'compare:input';
			
			$types = explode(':', $type);
			
			foreach($types as $type) {
				if(preg_match('/^compare/', $type)) {
					if(!empty($values['compare'])) {
						$value = $values['compare'];
						
						if($type == 'compare')
							$type = 'compare[!=;=;>=;<=;>;<]';
						
						$allowed_values = explode(';', substr($type, strlen('compare') + 1, -1));
						if(!in_array($value, $allowed_values))
							return 'compare-style-is-not-correct:' . $key;
					} else
						return 'field-is-empty:' . $key . ':compare';
				} elseif($type != 'rates' && $type != 'regions') {
					if(!empty($values[$type])) {
						$value = $values[$type];

						if($type == 'input') {
							if(!preg_match('/^-?[0-9]*\.?[0-9]+$/', $value)) {
								return 'field-can-only-be-integer:' . $key . ':' . $type;
								/*
								$view = wa()->getView();
								try {
									$view->fetch('string:' . $value);
								} catch(SmartyCompilerException $e) {
									return 'field-can-only-be-integer:' . $key . ':' . $type;
								}
								*/
							}
						}
					} else
						return 'field-is-empty:' . $key . ':' . $type;
				}
			}
			
			return true;
		} else
			return 'control-does-not-exists';
	}
	
	public function checkConditions($type = 'new')
	{
		foreach($this->conditions as $key => $condition) {
			if(empty($condition['control']))
				continue;

			$control = $condition['control'];
			
			if(array_key_exists($control, $this->controls)) {
				$check = $this->checkCondition($control, $condition, $key);
				
				if($check !== true) {
					$this->setError(_wp('Some fields are not filled correctly') . ': ' . $this->getControlTitle($control), $check);
					return false;
				}
			} elseif(preg_match('/^group:(-?[0-9])+$/', $control)) {
				preg_match('/^group:(-?([0-9])+)$/', $control, $group_matches);
				$group_key = $group_matches[1];
				
				if(!empty($this->group_conditions[$group_key])) {
					foreach($this->group_conditions[$group_key] as $group_condition_key => $group_condition) {
						$group_condition_control = $group_condition['control'];

						$check = $this->checkCondition($group_condition_control, $group_condition, $group_key);
						
						if($check !== true && $check == 'control-does-not-exists') {
							$this->setError(_wp('Control') . ' ' . $group_condition_control . ' ' . _wp('does not exists'), $check);
							return false;
						} elseif($check !== true) {
							$this->setError(_wp('Some fields are not filled correctly') . ': ' . $this->getControlTitle($group_condition_control), $check);
							return false;
						}
					}
				} else {
					$this->setError(_wp('Inside the group there must be at least one condition'), 'at-least-one-condition:' . $key);
					return false;
				}
			} else {
				$this->setError(_wp('Control') . ' ' . $control . ' ' . _wp('does not exists'), 'control-does-not-exists');
				return false;
			}
		}
		
		return true;
	}
	
	public function getControlTitle($control) {
		$control = $this->controls[$control];
		if(is_array($control))
			return _wp($control['title']);
		else
			return _wp($control);
	}
}