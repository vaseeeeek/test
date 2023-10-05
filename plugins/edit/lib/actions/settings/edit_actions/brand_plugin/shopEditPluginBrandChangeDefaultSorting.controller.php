<?php

class shopEditPluginBrandChangeDefaultSortingController extends shopEditBackendJsonController
{
	public function execute()
	{
		$brand_helper = new shopEditBrandPluginHelper();
		if (!$brand_helper->isPluginInstalled())
		{
			$this->errors['plugin'] = 'Плагин "Бренды PRO" не установлен';

			return;
		}

		if (!isset($this->state['brand_selection']) || !is_array($this->state['brand_selection']))
		{
			$this->errors['brand_selection'] = 'Нет параметра';

			return;
		}

		if (!array_key_exists('sorting', $this->state))
		{
			$this->errors['sorting'] = 'Нет параметра';

			return;
		}

		$brand_selection = new shopEditBrandSelection($this->state['brand_selection']);
		$sorting = trim($this->state['sorting']);

		$action = new shopEditBrandSetDefaultSortingAction($brand_selection, $sorting);
		$action_result = $action->run();

		$this->response['affected_brand_ids'] =array_fill_keys($action_result->params['affected_brand_ids'], true);
		$this->response['sorting'] = $sorting;

		$this->response['log'] = $action_result->assoc();
	}
}