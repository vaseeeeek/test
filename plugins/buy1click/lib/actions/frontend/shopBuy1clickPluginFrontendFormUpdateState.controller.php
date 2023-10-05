<?php


class shopBuy1clickPluginFrontendFormUpdateStateController extends waJsonController
{
	protected $env;
	protected $form_service;
	
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
		$this->updateForm($form, $state);
		$this->handleForm($form);
	}
	
	protected function updateForm(shopBuy1clickForm $form, $new_state)
	{
		$this->updateCartQuantity($form, $new_state);
		$this->updateContactInfo($form, $new_state);
		$this->updateSession($form, $new_state);
		
		$this->form_service->store($form);
	}
	
	protected function handleForm(shopBuy1clickForm $form)
	{
		$this->form_service->loadShipping($form);
		$this->form_service->loadShippingRates($form);
		$this->form_service->loadShippingCustomFields($form);

		if ($this->isExternal())
		{
			$this->form_service->loadExternalShippingRates($form);
			$this->form_service->loadExternalShippingCustomFields($form);
		}

		$this->form_service->updateSelectedShipping($form);
		
		$this->form_service->loadPayments($form);
		$this->form_service->loadOrder($form);
		$this->form_service->loadConfirmationChannel($form);
		
		$form->validate(new shopBuy1clickWaFormUpdateValidator());

		$form_view = new shopBuy1clickFormView($form);

		$this->response = array(
			'state' => $form_view->getState(),
			'html' => $form_view->render(),
		);
	}
	
	protected function isExternal()
	{
		return waRequest::post('external', false) == 'true';
	}
	
	private function updateCartQuantity(shopBuy1clickForm $form, $new_state)
	{
		$cart_state = $new_state['cart'];
		$quantities = array();
		
		foreach ($cart_state['items'] as $item_id => $item)
		{
			$quantities[$item_id] = $item['quantity'];
		}
		
		foreach ($quantities as $item_id => $quantity)
		{
			$form->getCart()->setQuantity($item_id, $quantity);
		}
	}

	private function updateContactInfo(shopBuy1clickForm $form, $new_state)
	{
		$state_contact_info = $new_state['contact_info'];
		$contact_info = $form->getContactInfo();
		
		foreach ($state_contact_info as $code => $value)
		{
			if ($code == 'name')
			{
				$contact_info->setName($value);
			}
			elseif ($code == 'firstname')
			{
				$contact_info->setFirstName($value);
			}
			elseif ($code == 'lastname')
			{
				$contact_info->setLastName($value);
			}
			elseif ($code == 'middlename')
			{
				$contact_info->setMiddleName($value);
			}
			elseif ($code == 'phone')
			{
				$contact_info->setPhone($value);
			}
			elseif ($code == 'email')
			{
				$contact_info->setEmail($value);
			}
			elseif ($code == 'shipping_address')
			{
				$address_fields = $value;
				
				foreach ($address_fields as $address_code => $address_value)
				{
					if ($address_code == 'country')
					{
						$contact_info->getShippingAddress()->setCountry($address_value);
					}
					elseif ($address_code == 'region')
					{
						$contact_info->getShippingAddress()->setRegion($address_value);
					}
					elseif ($address_code == 'city')
					{
						$contact_info->getShippingAddress()->setCity($address_value);
					}
					elseif ($address_code == 'street')
					{
						$contact_info->getShippingAddress()->setStreet($address_value);
					}
					elseif ($address_code == 'zip')
					{
						$contact_info->getShippingAddress()->setZip($address_value);
					}
					else
					{
						$custom_fields = $contact_info->getShippingAddress()->getCustomFields();
						$custom_fields[$address_code] = $address_value;
						$contact_info->getShippingAddress()->setCustomFields($custom_fields);
					}
				}
			}
			else
			{
				$custom_fields = $contact_info->getCustomFields();
				$custom_fields[$code] = $value;
				$contact_info->setCustomFields($custom_fields);
			}
		}
	}
	
	private function updateSession(shopBuy1clickForm $form, $new_state)
	{
		$form->getSession()->setIsCheckedPolicy(ifset($new_state['session']['is_checked_policy']));
		$form->getSession()->setShippingParams(ifset($new_state['session']['shipping_params']));
		$form->getSession()->setSelectedShippingID(ifset($new_state['session']['selected_shipping_id']));
		$form->getSession()->setSelectedShippingRateID(ifset($new_state['session']['selected_shipping_rate_id']));
		$form->getSession()->setSelectedPaymentID(ifset($new_state['session']['selected_payment_id']));
		$form->getSession()->setCoupon(ifset($new_state['session']['coupon']));
		$form->getSession()->setComment(ifset($new_state['session']['comment']));
		$form->getSession()->setConfirmationChannelType(ifset($new_state['session']['confirmation_step']));
		$form->getSession()->setConfirmationChannelAddress(ifset($new_state['session']['confirmation_channel_address']));
		$form->getSession()->setConfirmationChannelIsLastChannel(ifset($new_state['session']['confirmation_channel_code']));
	}
}
