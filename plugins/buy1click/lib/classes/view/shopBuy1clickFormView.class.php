<?php


class shopBuy1clickFormView
{
	private $wa_customer_form;
	private $form;
	
	public function __construct(shopBuy1clickForm $form_model)
	{
		$this->wa_customer_form = shopBuy1clickPlugin::getContext()->getWaCustomerForm();
		$this->form = $form_model;
	}
	
	public function getState()
	{
		$data = shopBuy1clickWaCurrency::getData();
		$data['RUB']['sign_html'] = '<span class="buy1click-ruble ruble"><span class="buy1click-ruble__inner">Р</span></span>';
		shopBuy1clickWaCurrency::setData($data);
		
		$fields = $this->wa_customer_form->getFields();
		$array_fields = array();
		
		foreach ($fields as $field)
		{
			$array_fields[$field['code']] = $field;
		}
		
		$state = array(
			'fields' => $array_fields,
			'settings' => $this->form->getSettings()->toArray(),
			'type' => $this->form->getType(),
			'cart' => $this->form->getCart()->toArray(),
			'contact_info' => $this->form->getContactInfo()->toArray(),
			'country_variants' => $this->getCountryVariants(),
			'region_variants' => $this->getRegionVariants(),
			'shipping' => $this->getShippingState(),
			'payments' => $this->getPaymentState(),
			'session' => $this->form->getSession()->toArray(),
			'order' => $this->form->getOrder()->toArray(),
			'contact_confirmation' => $this->getContactConfirmation(),
			'errors' => $this->form->getErrors(),
		);
		
		shopBuy1clickWaCurrency::rollback();
		
		return $state;
	}

	public function render()
	{
		$state = $this->getState();

		$view = new waSmarty3View(wa());
		$view->assign('state', $state);
		$view->assign('wa_plugin_url', shopBuy1clickPlugin::getStaticUrl(''));

		$data = shopBuy1clickWaCurrency::getData();
		$data['RUB']['sign_html'] = '<span class="buy1click-ruble ruble"><span class="buy1click-ruble__inner">Р</span></span>';
		shopBuy1clickWaCurrency::setData($data);

		$template_file = $state['contact_confirmation']['is_shown']
			? 'ConfirmationForm.html'
			: 'OrderForm.html';
		$view->assign('template_file', $template_file);
		$html = $view->fetch(shopBuy1clickPlugin::getPath('/templates/FormLayout.html'));

		shopBuy1clickWaCurrency::rollback();

		return $html;
	}
	
	private function getCountryVariants()
	{
		$model = new waCountryModel();
		
		$countries = $model->all();
		
		foreach ($countries as $i => $country)
		{
			$countries[$i] = $country['name'];
		}
		
		return $countries;
	}
	
	private function getRegionVariants()
	{
		$contact_info = $this->form->getContactInfo();
		$country_code = $contact_info->getShippingAddress()->getCountry();
		$model = new waRegionModel();
		$regions = $model->getByCountry($country_code);
		
		foreach ($regions as $i => $region)
		{
			$regions[$i] = $region['name'];
		}
		
		return $regions;
	}
	
	private function getShippingState()
	{
		$shipping = $this->form->getShipping();
		$array_shipping = array();
		
		foreach ($shipping as $i => $shipping_one)
		{
			$array_shipping[$i] = $shipping_one->toArray();
		}
		
		return $array_shipping;
	}

	private function getPaymentState()
	{
		$payments = $this->form->getPayments();
		$array_payments = array();

		foreach ($payments as $i => $payment)
		{
			$array_payments[$i] = $payment->toArray();
		}

		return $array_payments;
	}

	private function getContactConfirmation()
	{
		$errors = $this->form->getErrors();

		if (!isset($errors['confirm_channel']) || count($errors) !== 1)
		{
			return array(
				'is_shown' => false,
				'type' => '',
				'step' => '',
			);
		}

		$type = $this->form->getConfirmationChannel()->getCurrentUnconfirmedChannel();
		$address = '';
		if ($type === 'phone')
		{
			$address = $this->form->getOrder()->getContactInfo()->getPhone();
		}
		elseif ($type === 'email')
		{
			$address = $this->form->getOrder()->getContactInfo()->getEmail();
		}

		$is_last_channel = true;

		$unconfirmed_channels_count = $this->form->getConfirmationChannel()->countUnconfirmedChannels();
		if ($unconfirmed_channels_count > 1)
		{
			$is_last_channel = false;
		}

		return array(
			'is_shown' => true,
			'type' => $type,
			'address' => $address,
			'is_last_channel' => $is_last_channel,
		);
	}
}
