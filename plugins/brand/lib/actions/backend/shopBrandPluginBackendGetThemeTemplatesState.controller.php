<?php

class shopBrandPluginBackendGetThemeTemplatesStateController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$theme_id = waRequest::get('theme_id');

		$storage = new shopBrandActionThemeTemplateStorage();
		$this->response['action_theme_content'] = $storage->getThemeTemplatesState($theme_id);

		$this->response['success'] = true;
	}
}
