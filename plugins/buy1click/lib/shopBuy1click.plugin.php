<?php


class shopBuy1clickPlugin extends shopPlugin
{
	private static $context;
	const SHOP_ID = 'shop';
	const PLUGIN_ID = 'buy1click';
	
	private $env;
	private $settings_service;
	
	public function __construct($info)
	{
		parent::__construct($info);
		
		$this->env = shopBuy1clickPlugin::getContext()->getEnv();
		$this->settings_service = shopBuy1clickPlugin::getContext()->getSettingsService();
	}
	
	public static function getStaticUrl($url, $absolute = false)
	{
		/** @var shopBuy1clickPlugin $plugin */
		$plugin = wa(self::SHOP_ID)->getPlugin(self::PLUGIN_ID);

		return "{$plugin->getPluginStaticUrl($absolute)}{$url}";
	}

	public static function getPath($path)
	{
		/** @var shopBuy1clickPlugin $plugin */
		$plugin = wa(self::SHOP_ID)->getPlugin(self::PLUGIN_ID);

		return "{$plugin->path}{$path}";
	}
	
	/**
	 * @return shopBuy1clickPluginContext
	 */
	public static function getContext()
	{
		if (!isset(self::$context))
		{
			self::$context = new shopBuy1clickPluginContext();
		}
		
		return self::$context;
	}
	
	/**
	 * @return array
	 */
	public function getRoutingRules()
	{
		$settings = self::getContext()->getSettingsService()->getBasicSettings();
		
		if (!$settings->isEnabled())
		{
			return array();
		}
		
		$plugin_id = self::PLUGIN_ID;

		return array(
			"{$plugin_id}/form/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'form',
			),
			"{$plugin_id}/style/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'style',
			),
			"{$plugin_id}/config/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'config',
			),
			"{$plugin_id}/update_state/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'formUpdateState',
			),
			"{$plugin_id}/send_form/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'formSend',
			),
			"{$plugin_id}/ping_form/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'formPing',
			),
			"{$plugin_id}/request_channel_code/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'requestChannelCode',
			),
			"{$plugin_id}/validate_channel_code/" => array(
				'plugin' => self::PLUGIN_ID,
				'module' => 'frontend',
				'action' => 'validateChannelCode',
			),
            "{$plugin_id}/close/" => array(
                'plugin' => self::PLUGIN_ID,
                'module' => 'frontend',
                'action' => 'formClose',
            ),
		);
	}

	public function handleFrontendHead()
	{
		shopBuy1clickViewHelper::initAssets(true);
	}
	
	public function handleFrontendProduct($params)
	{
		$storefront_id = $this->env->getCurrentStorefront();
		$settings = $this->settings_service->getSettings($storefront_id, 'product');
		
		if (!$settings->isEnabled() || !$settings->isUseHook())
		{
			return array();
		}
		
		return array(
			'cart' => shopBuy1clickViewHelper::getButton($params['data']['id'])
		);
	}
	
	public function handleFrontendCart()
	{
		$storefront_id = $this->env->getCurrentStorefront();
		$settings = $this->settings_service->getSettings($storefront_id, 'cart');
		
		if (!$settings->isEnabled() || !$settings->isUseHook())
		{
			return '';
		}
		
		return shopBuy1clickViewHelper::getCartButton();
	}
	
	public function handleBackendOrder($order)
	{
		$settings = self::getContext()->getSettingsService()->getBasicSettings();
		
		if (!$settings->isEnabled())
		{
			return array();
		}
		
		return array(
			'title_suffix' => shopBuy1clickViewHelper::getOrderTitleSuffix($order)
		);
	}
	
	public function handleBackendReportsChannels(&$channels)
	{
		$sales_channel_parser = new shopBuy1clickSalesChannelParser();
		
		foreach ($channels as $sales_channel => $_)
		{
			$channel = $sales_channel_parser->parse($sales_channel);
			
			if (!isset($channel))
			{
				continue;
			}
			
			$channels[$sales_channel] = "Купить в 1 клик ({$channel['storefront']})";
		}
	}

	public function handleEditOrderAction(&$action_params)
	{
		$order_id = $action_params['order_id'];
		if (!$order_id)
		{
			return;
		}

		$order_param_model = new shopOrderParamsModel();

		$order_params = $order_param_model->get($order_id);

		if (isset($order_params['sales_channel']) && isset($order_params[shopBuy1clickOrderService::PARAM_IS_BUY1CLICK]))
		{
			$buy1click_sales_channel = str_replace('storefront:', 'buy1click:', $order_params['sales_channel']);

			$order_param_model->setOne($order_id, 'sales_channel', $buy1click_sales_channel);
		}
	}
}
