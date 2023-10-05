<?php

class shopEditPluginSiteClearHeadJsController extends shopEditBackendJsonController
{
	public function execute()
	{
		if (!array_key_exists('site_selection', $this->state) && !is_array($this->state['site_selection']))
		{
			$this->errors['site_selection'] = 'Нет параметра';

			return;
		}

		$site_selection = new shopEditSiteSelection($this->state['site_selection']);

		$action = new shopEditSiteClearHeadJsAction($site_selection);
		$action_result = $action->run();

		$this->response['affected_site_ids'] = $action_result->params['affected_site_ids'];

		$this->response['log'] = $action_result->assoc();
	}
}