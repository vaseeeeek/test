<?php

class shopBuy1clickPluginFrontendValidateChannelCodeController extends waJsonController
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
		$code = waRequest::post('code');

		$confirmation_channel_service = new shopBuy1clickConfirmationChannelService($this->env);
		$confirmation_channel_validator = $confirmation_channel_service->getConfirmationChannelValidator();

		$this->errors = $confirmation_channel_validator->getConfirmationCodeErrors($code, $source);

		if (count($this->errors) > 0)
		{
			return;
		}

		$confirmation_channel_validator->setConfirmed();

		$form = $this->getForm();

		$this->response = array(
			'contact_info' => $form->getContactInfo()->toArray(),
		);
	}

	private function getForm()
	{
		$state_json = waRequest::post('state');
		$storefront_id = $this->env->getCurrentStorefront();
		$state = json_decode($state_json, true);
		$type = ifset($state['type']);
		$code = ifset($state['cart']['code']);

		return $this->form_service->getByCode($code, $type, $storefront_id);
	}
}
