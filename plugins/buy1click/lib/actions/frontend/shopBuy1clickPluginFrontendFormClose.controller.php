<?php


class shopBuy1clickPluginFrontendFormCloseController extends waJsonController
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

        $form = $this->form_service->getByCode($code, $type, $storefront_id);
        $this->clearForm($form);
    }

    protected function clearForm(shopBuy1clickForm $form)
    {
        $form->getCart()->clear();
    }

}