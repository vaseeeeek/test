<?php


class shopRegionsViewHelper extends waViewHelper
{
	public static function getPageContent()
	{
		return self::getContentPage();
	}

	public static function getContentPage()
	{
		$helper = new shopRegionsHelper();
		if (!$helper->isRegionsInstalled() || waRequest::param('action') != 'page')
		{
			/** @var array $page */
			$page = wa()->getView()->getVars('page');

			return $page['content'];
		}

		$context = new shopRegionsPluginContext();
		$optimizer_manager = new shopRegionsOptimizerManager($context);

		$optimizer = $optimizer_manager->getPageOptimizer();
		$optimizer->execute();

		/** @var array $page */
		$page = wa()->getView()->getVars('page');

		return $page['content'];
	}

	public static function getWindow()
	{
		$helper = new shopRegionsHelper();
		if (!$helper->isRegionsInstalled())
		{
			return '';
		}

		shopRegionsPlugin::saveUserEnvironment();

		$routing = new shopRegionsRouting();
		$settings = new shopRegionsSettings();

		$current_city = $routing->getCurrentCity();

		$view_vars = array();

		$plugin = wa('shop')->getPlugin('regions');

		$view_vars['settings'] = $settings->get();
		$view_vars['js_src'] = wa()->getAppStaticUrl('shop') . 'plugins/regions/js/window.js';
		$view_vars['style_src'] = $settings->getWindowStyleUrl();
		$view_vars['version'] = waSystemConfig::isDebug()
			? '?v=' . time()
			: '?v=' . $plugin->getVersion();
		$view_vars['cookie_domain'] = $current_city ? $helper->getCookieDomain($current_city) : wa()->getRouting()->getDomain();
		$view_vars['current'] = self::getCurrentCityArray();
		$view_vars['trigger_environment_restore_url'] = wa('shop')->getRouteUrl('shop/frontend/restoreUserEnvironment');
		$view_vars['request_redirect_url'] = wa('shop')->getRouteUrl('shop/frontend/getRedirectUrl');
		$view_vars['load_popup_content_url'] = wa('shop')->getRouteUrl('shop/frontend/popupContent');
		$view_vars['button_html'] = self::getButton();

		$confirm_cookie = waRequest::cookie('shop_regions_confirm');
		if ($settings->ip_analyzer_enable && $settings->ip_analyzer_show && $confirm_cookie === null)
		{
			$ip_city = $routing->getIpCity();

			$view_vars['ip_city'] = $ip_city !== null && ($ip_city instanceof shopRegionsCity)
				? $ip_city->toArray(false, false)
				: $view_vars['current'];
		}

		$path = wa()->getAppPath('plugins/regions/templates/', 'shop');


		$view = wa()->getView();

		$view->assign(array(
			'regions' => $view_vars,
			'ip_city' => isset($view_vars['ip_city']) ? $view_vars['ip_city'] : null
		));

		$view_vars['ip_analyzer_html'] = '';
		if (isset($view_vars['ip_city']))
		{
			$ip_city_confirm_window_header = $view->fetch('string:' . $settings->ip_city_confirm_window_header_template);

			$view->assign('ip_city_confirm_window_header', $ip_city_confirm_window_header);

			$view_vars['ip_analyzer_html'] = $view->fetch($path . 'RegionsIpAnalyzer.html');
		}

		$view->assign(array('regions' => $view_vars));


		return $view->fetch($path . 'RegionsBlock.html');
	}


	public static function parseTemplate($template)
	{
		$helper = new shopRegionsHelper();
		if (!$helper->isRegionsInstalled())
		{
			return $template;
		}

		$context = new shopRegionsPluginContext();
		$view = $context->getTemplateView();

		return $view->fetch($template);
	}

	public static function getCurrentContact()
	{
		$contact = shopRegionsCurrentContact::getCurrentContact();

		return $contact instanceof $contact ? $contact : wa()->getUser();
	}

	private static function getButton()
	{
		$helper = new shopRegionsHelper();
		if (!$helper->isRegionsInstalled())
		{
			return '';
		}

		$settings = new shopRegionsSettings();
		$context = new shopRegionsPluginContext();

		return $context->getTemplateView()->fetch($settings->button_html);
	}

	private static function getCurrentCityArray()
	{
		$current_city_array = array(
			'id' => '',
			'region_code' => '',
			'country_iso3' => '',
			'name' => '',
		);

		$helper = new shopRegionsHelper();
		if (!$helper->isRegionsInstalled())
		{
			return $current_city_array;
		}

		$routing = new shopRegionsRouting();

		$current_city = $routing->getCurrentCity();

		if ($current_city)
		{
			$current_city->getCountryName();
			$current_city_array = $current_city->toArray(false, false);
		}

		return $current_city_array;
	}
}