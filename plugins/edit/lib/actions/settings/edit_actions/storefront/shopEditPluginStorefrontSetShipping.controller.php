<?php

class shopEditPluginStorefrontSetShippingController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!isset($this->state['storefront_selection']) || !is_array($this->state['storefront_selection']))
		{
			$this->errors['storefront_selection'] = 'Обязательный параметр';
		}

		if (!isset($this->state['shipping_selection']) || !is_array($this->state['shipping_selection']))
		{
			$this->errors['shipping_selection'] = 'Обязательный параметр';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$storefront_selection = new shopEditStorefrontSelection($this->state['storefront_selection']);
		$shipping_selection = new shopEditShippingSelection($this->state['shipping_selection']);

		$action = new shopEditStorefrontSetShippingAction($storefront_selection, $shipping_selection);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}