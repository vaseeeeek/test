<?php


class shopBuy1clickPluginFrontendStyleController extends waController
{
	private $view;
	private $env;
	private $settings_service;
	
	public function __construct()
	{
		$this->view = wa()->getView();
		$this->env = shopBuy1clickPlugin::getContext()->getEnv();
		$this->settings_service = shopBuy1clickPlugin::getContext()->getSettingsService();
	}
	
	public function execute()
	{
		$storefront_id = $this->env->getCurrentStorefront();
		$product_settings = $this->settings_service->getSettings($storefront_id, 'product');
		$cart_settings = $this->settings_service->getSettings($storefront_id, 'cart');
		
		$this->view->assign(array(
			'product_settings' => $product_settings->toArray(),
			'cart_settings' => $cart_settings->toArray()
		));
	}
	
	public function run($params = null)
	{
		parent::run($params);
		$this->display();
	}
	
	public function display()
	{
		$this->getResponse()->addHeader('Content-Type', 'text/css');
		$this->getResponse()->sendHeaders();
		
		echo $this->view->fetch(wa()->getAppPath('plugins/buy1click/templates/Style.html', shopBuy1clickPlugin::SHOP_ID));
	}
}
