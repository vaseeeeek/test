<?php

class shopEditPluginBrandChangeFiltersController extends shopEditBackendJsonController
{
	public function execute()
	{
		$brand_helper = new shopEditBrandPluginHelper();
		if (!$brand_helper->isPluginInstalled())
		{
			$this->errors['plugin'] = 'Плагин "Бренды PRO" не установлен';
		}

		if (!array_key_exists('mode', $this->state))
		{
			$this->errors['mode'] = 'Нет параметра';
		}

		if (!array_key_exists('all_brands_features_selection', $this->state) || !is_array($this->state['all_brands_features_selection']))
		{
			$this->errors['all_brands_features_selection'] = 'Нет параметра';
		}

		if (!array_key_exists('brands_filter', $this->state) || !is_array($this->state['brands_filter']))
		{
			$this->errors['brands_filter'] = 'Нет параметра';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$action = new shopEditBrandChangeFiltersAction($this->state['mode'], $this->state['all_brands_features_selection'], $this->state['brands_filter']);
		$action_result = $action->run();

		$this->response = array(
			'brands_filter' => $this->getBrandsFilter(),
		);

		$this->response['log'] = $action_result->assoc();
	}

	private function getBrandsFilter()
	{
		$brand_model = new shopBrandBrandModel();

		$brands_filter = array();

		$query = $brand_model->select('id,filter')->query();
		foreach ($query as $brand)
		{
			$brands_filter[$brand['id']] = json_decode($brand['filter'], true);
		}

		return $brands_filter;
	}
}
