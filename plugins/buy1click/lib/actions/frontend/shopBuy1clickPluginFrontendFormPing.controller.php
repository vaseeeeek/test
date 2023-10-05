<?php


class shopBuy1clickPluginFrontendFormPingController extends waJsonController
{
	private $env;
	private $form_service;
	
	public function __construct()
	{
		$this->env = shopBuy1clickPlugin::getContext()->getEnv();
		$this->form_service = shopBuy1clickPlugin::getContext()->getFormService();
	}
	
	public function execute()
	{
		$state_json = waRequest::post('state');
		$storefront_id = $this->env->getCurrentStorefront();
		$state = json_decode($state_json, true);
		$type = ifset($state['type']);
		$code = ifset($state['cart']['code']);
		
		$this->form_service->getByCode($code, $type, $storefront_id);
	}
}