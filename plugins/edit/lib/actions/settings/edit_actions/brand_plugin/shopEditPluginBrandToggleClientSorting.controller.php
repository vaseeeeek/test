<?php

class shopEditPluginBrandToggleClientSortingController extends shopEditBackendJsonController
{
	public function execute()
	{
		$brand_helper = new shopEditBrandPluginHelper();
		if (!$brand_helper->isPluginInstalled())
		{
			$this->errors['plugin'] = 'Плагин "Бренды PRO" не установлен';
		}

		if (!isset($this->state['brand_selection']) || !is_array($this->state['brand_selection']))
		{
			$this->errors['brand_selection'] = 'Нет параметра';
		}

		if (!array_key_exists('toggle', $this->state))
		{
			$this->errors['toggle'] = 'Нет параметра';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$brand_selection = new shopEditBrandSelection($this->state['brand_selection']);
		$toggle = !!$this->state['toggle'];

		$action = new shopEditBrandToggleClientSortingAction($brand_selection, $toggle);
		$action_result = $action->run();

		$this->response['affected_brand_ids'] = array_fill_keys($action_result->params['affected_brand_ids'], true);
		$this->response['toggle'] = $toggle;

		$this->response['log'] = $action_result->assoc();
	}
}
