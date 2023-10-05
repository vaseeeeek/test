<?php

class shopEditPluginMetaDeleteController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!array_key_exists('settings', $this->state) || !is_array($this->state['settings']))
		{
			$this->errors['settings'] = '!';

			return;
		}

		$settings_params = $this->state['settings'];

		if (!array_key_exists('fields', $settings_params) || !is_array($settings_params['fields']))
		{
			$this->errors['fields'] = '';
		}

		if (!array_key_exists('source_type', $settings_params))
		{
			$this->errors['source_type'] = '';
		}

		if (!array_key_exists('storefront_selection', $settings_params) || !is_array($settings_params['storefront_selection']))
		{
			$this->errors['storefront_selection'] = '';
		}

		if (!array_key_exists('delete_seo_plugin_data', $settings_params))
		{
			$this->errors['delete_seo_plugin_data'] = '';
		}

		if (count($this->errors) > 0)
		{
			return;
		}

		try
		{
			$settings = new shopEditMetaDeleteSettings($settings_params);
		}
		catch (shopEditActionInvalidParamException $e)
		{
			$this->errors[$e->getParam()] = $e->getParamError();

			return;
		}


		$action = new shopEditMetaDeleteAction($settings);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}