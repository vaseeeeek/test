<?php

class shopDpPluginBackendGetRegionsController extends waJsonController
{
	public function execute()
	{
		$country = waRequest::get('country');
		$group = waRequest::get('group', 0, 'int');

		$this->response = shopDpPluginHelper::getRegions($country, $group ? true : false);
	}
}