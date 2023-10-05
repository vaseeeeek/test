<?php

class shopEditPluginSiteSetAppsMenuController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!array_key_exists('apps_menu_settings', $this->state) && !is_array($this->state['apps_menu_settings']))
		{
			$this->errors['apps_menu_settings'] = 'Нет параметра';

			return;
		}

		$apps_menu_settings = new shopEditSiteAppsMenuSettings($this->state['apps_menu_settings']);

		$action = new shopEditSiteSaveAppsMenuAction($apps_menu_settings);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}