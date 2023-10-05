<?php

class shopEditPluginSiteCopyAuthConfigController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!isset($this->state['destination_site_selection']) || !is_array($this->state['destination_site_selection']))
		{
			$this->errors['destination_site_selection'] = 'Обязательное поле';

			return;
		}

		$source_site_id = ifset($this->state['source_site_id'], 0);
		$destination_site_selection = new shopEditSiteSelection($this->state['destination_site_selection']);

		try
		{
			$action = new shopEditSiteCopyAuthConfigAction($source_site_id, $destination_site_selection);
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