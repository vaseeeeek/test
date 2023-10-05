<?php

class shopDpPluginBackendGetCountriesController extends waJsonController
{
	public function execute()
	{
		$group = waRequest::get('group', 0, 'int');

		$this->response = shopDpPluginHelper::getCountries($group);
	}
}