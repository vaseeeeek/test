<?php

class shopEditPluginCategorySetDefaultSortingController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!isset($this->state['category_selection']) || !is_array($this->state['category_selection']))
		{
			$this->errors['category_selection'] = 'Нет параметра';

			return;
		}

		if (!array_key_exists('sorting', $this->state))
		{
			$this->errors['sorting'] = 'Нет параметра';

			return;
		}

		$category_selection = new shopEditCategorySelection($this->state['category_selection']);
		$sorting = trim($this->state['sorting']);

		$action = new shopEditCategorySetDefaultSortingAction($category_selection, $sorting);
		$action_result = $action->run();

		$this->response['affected_category_ids'] =array_fill_keys($action_result->params['affected_category_ids'], true);
		$this->response['sorting'] = $sorting;

		$this->response['log'] = $action_result->assoc();
	}
}