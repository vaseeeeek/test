<?php

class shopBrandPluginFrontendRedirectBrandsController extends waViewController
{
	public function execute()
	{
		$params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'brands',
		);

		$url = wa()->getRouteUrl('shop', $params);

		$this->redirect($url, 301);
	}
}