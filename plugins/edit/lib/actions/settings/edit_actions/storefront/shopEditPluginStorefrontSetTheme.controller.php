<?php

class shopEditPluginStorefrontSetThemeController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!array_key_exists('app_id', $this->state))
		{
			$this->errors['app_id'] = 'Обязательный параметр';
		}

		if (!array_key_exists('storefront_selection', $this->state) || !is_array($this->state['storefront_selection']))
		{
			$this->errors['storefront_selection'] = 'Обязательный параметр';
		}

		if (!array_key_exists('theme_id', $this->state))
		{
			$this->errors['theme_id'] = 'Обязательный параметр';
		}

		if (!array_key_exists('theme_mobile_id', $this->state))
		{
			$this->errors['theme_mobile_id'] = 'Обязательный параметр';
		}

		$app_id = $this->state['app_id'];
		$storefront_selection = new shopEditStorefrontSelection($this->state['storefront_selection']);
		$theme_id = $this->state['theme_id'];
		$theme_mobile_id = $this->state['theme_mobile_id'];

		$action = new shopEditStorefrontSetThemeAction($app_id, $storefront_selection, $theme_id, $theme_mobile_id);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}