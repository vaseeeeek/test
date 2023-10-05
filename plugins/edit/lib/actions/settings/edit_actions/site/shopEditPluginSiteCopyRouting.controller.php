<?php

class shopEditPluginSiteCopyRoutingController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!array_key_exists('copy_apps_menu_settings', $this->state) && !is_array($this->state['copy_apps_menu_settings']))
		{
			$this->errors['copy_apps_menu_settings'] = 'Нет параметра';

			return;
		}

		$copy_apps_menu_settings = new shopEditCopySiteSettings($this->state['copy_apps_menu_settings']);

		try
		{
			$action = new shopEditSiteCopyRoutingAction($copy_apps_menu_settings);
			$action_result = $action->run();
		}
		catch (shopEditActionInvalidParamException $e)
		{
			$this->errors[$e->getParam()] = $e->getParamError();

			return;
		}
		catch (waException $e)
		{
			$this->errors['save'] = $e->getMessage();

			return;
		}

		$this->response['log'] = $action_result->assoc();
	}
}