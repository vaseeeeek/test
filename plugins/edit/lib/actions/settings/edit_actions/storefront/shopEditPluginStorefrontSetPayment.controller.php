<?php

class shopEditPluginStorefrontSetPaymentController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!isset($this->state['storefront_selection']) || !is_array($this->state['storefront_selection']))
		{
			$this->errors['storefront_selection'] = 'Обязательный параметр';
		}

		if (!isset($this->state['payment_selection']) || !is_array($this->state['payment_selection']))
		{
			$this->errors['payment_selection'] = 'Обязательный параметр';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$storefront_selection = new shopEditStorefrontSelection($this->state['storefront_selection']);
		$payment_selection = new shopEditPaymentSelection($this->state['payment_selection']);

		$action = new shopEditStorefrontSetPaymentAction($storefront_selection, $payment_selection);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}