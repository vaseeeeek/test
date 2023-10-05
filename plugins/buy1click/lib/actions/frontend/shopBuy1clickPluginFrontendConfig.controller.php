<?php

class shopBuy1clickPluginFrontendConfigController extends waController
{
	private $config;

	public function execute()
	{
		$plugin_id = shopBuy1clickPlugin::PLUGIN_ID;

		$form_url = wa()->getRouteUrl('shop/frontend/form', array(
			'plugin' => $plugin_id,
		));

		$update_form_url = wa()->getRouteUrl('shop/frontend/formUpdateState', array(
			'plugin' => $plugin_id,
		));

		$send_form_url = wa()->getRouteUrl('shop/frontend/formSend', array(
			'plugin' => $plugin_id,
		));

		$ping_form_url = wa()->getRouteUrl('shop/frontend/formPing', array(
			'plugin' => $plugin_id,
		));

        $close_form_url = wa()->getRouteUrl('shop/frontend/formClose', array(
            'plugin' => $plugin_id,
        ));

		$send_channel_address_url = wa()->getRouteUrl('shop/frontend/requestChannelCode', array(
			'plugin' => $plugin_id,
		));

		$send_channel_code_url = wa()->getRouteUrl('shop/frontend/validateChannelCode', array(
			'plugin' => $plugin_id,
		));

		$this->config = array(
			'wa_url' => wa()->getUrl(),
			'form_url' => $form_url,
			'update_form_url' => $update_form_url,
			'send_form_url' => $send_form_url,
			'ping_form_url' => $ping_form_url,
            'close_form_url' => $close_form_url,
			'send_channel_address_url' => $send_channel_address_url,
			'send_channel_code_url' => $send_channel_code_url,
			'is_increase_plugin_enabled' => shopBuy1clickPlugin::getContext()->getEnv()->isIncreasePluginEnabled(),
		);
	}

	public function run($params = null)
	{
		parent::run($params);

		$this->display();
	}

	public function display()
	{
		$this->getResponse()->addHeader('Cache-Control', 'no-store');
		$this->getResponse()->addHeader('Content-Type', 'application/javascript');
		$this->getResponse()->sendHeaders();

		$json_config = json_encode($this->config);
		echo "window.shop_buy1click_config = {$json_config};";
	}
}
