<?php

class shopEditPluginStorefrontSetDropOutOfStockController extends shopEditBackendJsonController
{
	public function execute()
	{
		$drop_out_of_stocks = array('0' => '0', '1' => '1', '2' => '2');

		if (!isset($this->state['storefront_selection']) || !is_array($this->state['storefront_selection']))
		{
			$this->errors['storefront_selection'] = 'Обязательный параметр';
		}

		if (!array_key_exists('drop_out_of_stock', $this->state) || !array_key_exists($this->state['drop_out_of_stock'], $drop_out_of_stocks))
		{
			$this->errors['drop_out_of_stock'] = 'Обязательный параметр';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$storefront_selection = new shopEditStorefrontSelection($this->state['storefront_selection']);
		$drop_out_of_stock = $this->state['drop_out_of_stock'];

		$action = new shopEditStorefrontSetDropOutOfStockAction($storefront_selection, $drop_out_of_stock);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}