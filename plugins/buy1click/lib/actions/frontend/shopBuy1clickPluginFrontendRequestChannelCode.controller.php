<?php

class shopBuy1clickPluginFrontendRequestChannelCodeController extends waJsonController
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
		$address = waRequest::post('address');

		$confirmation_channel_service = new shopBuy1clickConfirmationChannelService($this->env);
		$confirmation_channel_validator = $confirmation_channel_service->getConfirmationChannelValidator();


		$this->errors = $confirmation_channel_validator->getConfirmationSourceErrors($address);
		if (count($this->errors) > 0)
		{
			return;
		}

		if (!$confirmation_channel_validator->sendConfirmationCode($address))
		{
			$this->errors[] = [
				'id' => 'send_error',
				'text' => _w('Code sending error'),
			];

			return;
		}

		$channel_type = $confirmation_channel_validator->getActiveType();

		$address_prepared = $channel_type === 'phone'
			? $this->env->transformPhone($address)
			: $address;

		$form = $this->getForm();
		$this->updateContact($form, $channel_type, $address_prepared);

		$this->response = [
			'success' => true,
			'channel_type' => $channel_type,
			'address' => $address_prepared
		];
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

	private function updateContact(shopBuy1clickForm $form, $channel_type, $address)
	{
		$contact_info = $form->getContactInfo();

		if ($channel_type === 'email')
		{
			$contact_info->setEmail($address);
		}
		elseif ($channel_type === 'phone')
		{
			$contact_info->setPhone($address);
		}
		else
		{
			return;
		}

		$this->form_service->store($form);
	}
}
