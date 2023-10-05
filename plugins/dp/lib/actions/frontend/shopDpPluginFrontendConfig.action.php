<?php

class shopDpPluginFrontendConfigAction extends waViewAction
{
	protected function route($action)
	{
		return wa()->getRouteUrl("shop/frontend/{$action}/", array('plugin' => 'dp'));
	}

	public function execute()
	{
		$this->setTemplate(wa()->getAppPath('plugins/dp/templates/actions/frontend/FrontendConfig.js', 'shop'));

		$this->getResponse()->addHeader('Content-type', 'text/javascript');
		$this->getResponse()->addHeader('X-Content-Type-Options', 'nosniff');

		$this->view->assign('plugin_url', shopDpPluginHelper::getPluginUrl());
		$this->view->assign('dialog_url', $this->route('dialog'));
		$this->view->assign('service_url', $this->route('service'));
		$this->view->assign('calculate_url', $this->route('calculate'));
		$this->view->assign('svg_url', $this->route('svg'));
		$this->view->assign('point_url', $this->route('point'));
		$this->view->assign('city_search_url', $this->route('citySearch'));
		$this->view->assign('city_save_url', $this->route('citySave'));

		$location = new shopDpLocation('frontend_config');
		$this->view->assign('country_code', $location->getCountry());
		$this->view->assign('country_name', $location->getCountry('name'));
		$this->view->assign('region_code', $location->getRegion());
		$this->view->assign('region_name', $location->getRegion('name'));
		$this->view->assign('city', $location->getCity());

		$storage = new shopDpSettingsStorage();
		$this->view->assign('map_service', $storage->getBasicSettings('map_service'));
		$this->view->assign('map_params', $storage->getBasicSettings('map_params'));
	}
}
