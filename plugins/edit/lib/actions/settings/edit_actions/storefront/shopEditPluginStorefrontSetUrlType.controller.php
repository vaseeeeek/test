<?php

class shopEditPluginStorefrontSetUrlTypeController extends shopEditBackendJsonController
{
	public function execute()
	{
		$url_types = array('0' => '0', '1' => '1', '2' => '2');

		if (!isset($this->state['storefront_selection']) || !is_array($this->state['storefront_selection']))
		{
			$this->errors['storefront_selection'] = 'Обязательный параметр';
		}

		if (!array_key_exists('url_type', $this->state) || !array_key_exists($this->state['url_type'], $url_types))
		{
			$this->errors['url_type'] = 'Обязательный параметр';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		$storefront_selection = new shopEditStorefrontSelection($this->state['storefront_selection']);
		$url_type = $this->state['url_type'];

		$action = new shopEditStorefrontSetUrlTypeAction($storefront_selection, $url_type);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}