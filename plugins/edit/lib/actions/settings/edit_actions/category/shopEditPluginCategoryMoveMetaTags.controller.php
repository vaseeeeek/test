<?php

class shopEditPluginCategoryMoveMetaTagsController extends shopEditBackendJsonController
{
	public function execute()
	{
		$seo_helper = new shopEditSeoPluginHelper();

		if (!$seo_helper->isPluginInstalled())
		{
			$this->errors['seo'] = 'Плагин SEO-оптимизация не установлен';

			return;
		}

		if (!isset($this->state['settings']) || !is_array($this->state['settings']))
		{
			$this->errors['settings'] = 'Нет параметра';

			return;
		}

		$settings = new shopEditCategoryMoveMetaTagsFormState($this->state['settings']);

		if ($settings->source_is_general && $settings->destination_is_general)
		{
			$this->errors['storefronts'] = 'Копирование с основной на основную - зачем?';

			return;
		}

		try
		{
			$action = $seo_helper->getCategoryMoveMetaTagsAction($settings);
		}
		catch (shopEditActionInvalidParamException $e)
		{
			$this->errors[$e->getParam()] = $e->getParamError();

			return;
		}

		$action_result = $action->run();

		$this->response['log'] = $action_result->assoc();
	}
}