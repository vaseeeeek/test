<?php

class shopEditPluginCategoryToggleClientSortingController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!isset($this->state['category_selection']) || !is_array($this->state['category_selection']))
		{
			$this->errors['category_selection'] = 'Нет параметра';
		}

		if (!array_key_exists('toggle', $this->state))
		{
			$this->errors['toggle'] = 'Нет параметра';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$category_selection = new shopEditCategorySelection($this->state['category_selection']);
		$toggle = !!$this->state['toggle'];

		$action = new shopEditCategoryToggleClientSortingAction($category_selection, $toggle);
		$action_result = $action->run();

		$this->response['affected_category_ids'] = array_fill_keys($action_result->params['affected_category_ids'], true);
		$this->response['toggle'] = $toggle;

		$this->response['log'] = $action_result->assoc();
	}
}