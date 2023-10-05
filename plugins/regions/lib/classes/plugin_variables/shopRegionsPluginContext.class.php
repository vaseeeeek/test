<?php

class shopRegionsPluginContext
{
	/** @var shopRegionsViewBuffer */
	private static $view = null;
	private static $region_view_vars = null;

	public function __construct()
	{
		if (self::$view === null)
		{
			$this->init();
		}

		$this->updateMetaTemplate();
	}

	/**
	 * @return shopRegionsViewBuffer
	 */
	public function getTemplateView()
	{
		return self::$view;
	}

	public function getPluginViewVariables()
	{
		if (self::$region_view_vars !== null)
		{
			return self::$region_view_vars;
		}

		$routing = new shopRegionsRouting();
		$city = $routing->getCurrentCity();

		if ($city instanceof shopRegionsCity)
		{
			$settings = new shopRegionsSettings();
			self::$region_view_vars = array(
				'region' => array(
					'id' => $city->getID(),
					'name' => $city->getName(),
					'phone' => $city->getPhone(),
					'email' => $city->getEmail(),
					'schedule' => $city->getSchedule(),
					'area' => $city->getAreaName(),
					'country' => $city->getCountryName(),
					'field' => array(),
					'routing' => array(
						'route' => $city->getShopRoute(),
						'route_url' => $city->getRoute() ? trim($city->getRoute(), '*') : '',
						'storefront' => $city->getStorefront(),
						'domain' => $city->getDomainName(),
					),
				)
			);

			foreach ($settings->getParams() as $_param)
			{
				$param = shopRegionsParam::build($_param);

				self::$region_view_vars['region']['field'][$param->getID()] = $city->getParam($param->getID());
			}
		}
		else
		{
			self::$region_view_vars = false;
		}

		return self::$region_view_vars;
	}

	private function init()
	{
		$view = new shopRegionsViewBuffer();
		$this->assignPluginVariables($view);

		$legacy_replacer = new shopRegionsCityReplacesSet($view);
		$view->setLegacyReplacer($legacy_replacer);

		self::$view = $view;
	}

	/**
	 * @param shopRegionsViewBuffer $view
	 */
	private function assignPluginVariables(shopRegionsViewBuffer $view)
	{
		$outer_view = wa()->getView();
		$view->assign($outer_view->getVars());

		$region_view_vars = $this->getPluginViewVariables();

		if (is_array($region_view_vars))
		{
			$view->assign($region_view_vars);
		}
	}

	private function updateMetaTemplate()
	{
		$meta_response = new shopRegionsMetaResponse();

		self::$view->assign(array(
			'title' => $meta_response->getMetaTitle(),
			'meta_keywords' => $meta_response->getMetaKeywords(),
			'meta_description' => $meta_response->getMetaDescription(),
		));
	}
}