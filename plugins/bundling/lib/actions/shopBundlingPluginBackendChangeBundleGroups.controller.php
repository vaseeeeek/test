<?php

class shopBundlingPluginBackendChangeBundleGroupsController extends waJsonController
{
	public function execute()
	{
		$value = waRequest::get('value');
		
		if(in_array($value, array('custom', 'main_category')))
			wao(new waAppSettingsModel())->set(array('shop', 'bundling'), 'bundle_groups', $value);
	}
}