<?php

class shopEditPluginBrandInfoGetFilterController extends shopEditBackendJsonController
{
	public function execute()
	{
		// todo пока не используется. надо сначала загружать бренды без filter

		$brand_helper = new shopEditBrandPluginHelper();
		if (!$brand_helper->isPluginInstalled())
		{
			$this->errors['plugin'] = 'Плагин "Бренды PRO" не установлен';
		}

		$brand_id = waRequest::get('brand_id');
		if (!($brand_id > 0))
		{
			$this->errors['brand_id'] = 'Нужен id категории';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$this->response['filters'] = $brand_helper->getBrandFilter($brand_id);
	}

	protected function stateIsRequired()
	{
		return false;
	}
}