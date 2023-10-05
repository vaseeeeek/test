<?php

class shopEditPluginSeofilterMoveMetaTagsController extends shopEditBackendJsonController
{
	public function execute()
	{
		$seofilter_helper = new shopEditSeofilterPluginHelper();

		if (!$seofilter_helper->isPluginInstalled())
		{
			$this->errors['seofilter'] = 'Плагин SEO-фильтр не установлен';

			return;
		}

		if (!isset($this->state['settings']) || !is_array($this->state['settings']))
		{
			$this->errors['settings'] = 'Нет параметра';

			return;
		}

		$settings = new shopEditSeofilterMoveMetaTagsFormState($this->state['settings']);

		$action = new shopEditSeofilterMoveMetaTagsAction($settings);
		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}