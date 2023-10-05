<?php


class shopBuy1clickFormService
{
	private $contact_info_service;
	private $session_service;
	private $temp_cart_service;
	private $shipping_service;
	private $payment_service;
	private $order_service;
	private $settings_service;
	/** @var shopFreedeliveryPlugin */
	private $freedelivery_plugin;
	private $env;
	private $confirmation_channel_service;

	public function __construct(
		shopBuy1clickContactInfoService $contact_info_service,
		shopBuy1clickSessionService $session_service,
		shopBuy1clickTempCartService $temp_cart_service,
		shopBuy1clickShippingService $shipping_service,
		shopBuy1clickPaymentService $payment_service,
		shopBuy1clickOrderService $order_service,
		shopBuy1clickSettingsService $settings_service,
		$freedelivery_plugin,
		shopBuy1clickWaEnv $env,
		shopBuy1clickConfirmationChannelService $confirmation_channel_service
	)
	{
		$this->contact_info_service = $contact_info_service;
		$this->session_service = $session_service;
		$this->temp_cart_service = $temp_cart_service;
		$this->shipping_service = $shipping_service;
		$this->payment_service = $payment_service;
		$this->order_service = $order_service;
		$this->settings_service = $settings_service;
		$this->freedelivery_plugin = $freedelivery_plugin;
		$this->env = $env;
		$this->confirmation_channel_service = $confirmation_channel_service;
	}

	/**
	 * @param $code
	 * @param $type
	 * @param $storefront_id
	 * @return shopBuy1clickForm
	 */
	public function getByCode($code, $type, $storefront_id)
	{
		$settings = $this->settings_service->getSettings($storefront_id, $type);
		$contact_info = $this->contact_info_service->getCurrent();

		$session = $this->session_service->getByCode($code);

		$cart = new shopBuy1clickWaTempCart($code);
		$this->temp_cart_service->update($cart);

		$form = new shopBuy1clickForm();
		$form->setType($type);
		$form->setSettings($settings);
		$form->setCart($cart);
		$form->setContactInfo($contact_info);
		$form->setSession($session);

		return $form;
	}

	/**
	 * @param $item
	 * @param $storefront_id
	 * @return shopBuy1clickForm
	 */
	public function createByItem($item, $storefront_id)
	{
		$temp_cart = new shopBuy1clickWaTempCart();
		$this->temp_cart_service->update($temp_cart);
		$settings = $this->settings_service->getSettings($storefront_id, 'product');

		$temp_cart_items = array();
		if ($settings->isEnabledFormIncludeCartItems())
		{
			$current_cart = new shopBuy1clickWaCart();

			foreach ($current_cart->getHierarchyItems() as $_item)
			{
				if ($_item['type'] === 'product' && $_item['sku_id'] == $item['sku_id'])
				{
					continue;
				}

				$temp_cart_items[] = $_item;
			}
		}
		$temp_cart_items[] = $item;

		$cart_memento = new shopBuy1clickCartMemento();
		$cart_memento->replaceTo($temp_cart->getCode());

		foreach ($temp_cart_items as $temp_cart_item)
		{
			$temp_cart->addItem($temp_cart_item);
		}

		$cart_memento->rollback();

		return $this->getByCode($temp_cart->getCode(), shopBuy1clickForm::TYPE_ITEM, $storefront_id);
	}

	/**
	 * @param $storefront_id
	 * @return shopBuy1clickForm
	 */
	public function createByCart($storefront_id)
	{
		$cart = new shopBuy1clickWaTempCart();
		$this->temp_cart_service->update($cart);
		$shop_cart = new shopBuy1clickWaCart();
		$cart_memento = new shopBuy1clickCartMemento();
		$cart_memento->replaceTo($cart->getCode());

		foreach ($shop_cart->getHierarchyItems() as $item)
		{
			$cart->addItem($item);
		}

		$cart_memento->rollback();

		return $this->getByCode($cart->getCode(), shopBuy1clickForm::TYPE_CART, $storefront_id);
	}

	public function loadShipping(shopBuy1clickForm $form)
	{
		$settings = $form->getSettings();
		$contact_info = $form->getContactInfo();

		if ($settings->getFormShippingMode() == shopBuy1clickSettings::SHIPPING_MODE_DISABLED)
		{
			$shipping = array();
		}
		else
		{
			$delpayfilter_cart = $this->buildCartCopy($form->getCart());

			$shipping_condition = new shopBuy1clickShippingFilterCondition();
			$shipping_condition
				->filterByCurrentStorefront()
				->filterDelpayfilter($delpayfilter_cart)
				->filterCheckcustomer();

			if (!$settings->showInvalidFormShippingMethods())
			{
				$shipping_condition->filterByShippingAddress($contact_info->getShippingAddress());
			}

			if ($settings->getFormShippingMode() == shopBuy1clickSettings::SHIPPING_MODE_SELECTED)
			{
				$shipping_condition->filterByShippingIDs($settings->getFormShipping());
			}

			$shipping = $this->shipping_service->getByCondition($shipping_condition);

            $delpayfilter_cart->clear();
		}

		$form->setShipping($shipping);
	}

	public function loadShippingCustomFields(shopBuy1clickForm $form)
	{
		if (!$form->getSettings()->isEnabledFormShippingCustomFields())
		{
			return;
		}

		foreach ($form->getShipping() as $shipping)
		{
			if ($shipping->getPlugin()->getExternal())
			{
				continue;
			}

			$shipping->setCustomFields($this->getShippingCustomFields($form, $shipping));
		}
	}

	public function loadExternalShippingCustomFields(shopBuy1clickForm $form)
	{
		if (!$form->getSettings()->isEnabledFormShippingCustomFields())
		{
			return;
		}

		foreach ($form->getShipping() as $shipping)
		{
			if (!$shipping->getPlugin()->getExternal())
			{
				continue;
			}

			$shipping->setCustomFields($this->getShippingCustomFields($form, $shipping));
		}
	}

	public function updateSelectedShipping(shopBuy1clickForm $form)
	{
		$session = $form->getSession();
		$shipping_methods = $form->getShipping();

		if (array_key_exists($session->getSelectedShippingId(), $shipping_methods) || count($shipping_methods) === 0)
		{
			return;
		}

		$selected_shipping = null;

		foreach ($shipping_methods as $shipping)
		{
			$rates = $shipping->getRates();

			$has_rates = is_array($rates) && count($rates) > 0;
			$has_error = !empty($shipping->getError())
				|| (is_array($rates) && array_key_exists(0, $rates) && $rates[0]->getRate() === null);
			$is_disabled = $has_error || !$has_rates;

			if ($is_disabled && !is_null($rates))
			{
				continue;
			}

			if ($selected_shipping === null)
			{
				$selected_shipping = $shipping;
			}

			if (!$shipping->getPlugin()->getExternal())
			{
				$selected_shipping = $shipping;
				break;
			}
		}

		if ($selected_shipping)
		{
			$session->setSelectedShippingId($selected_shipping->getId());
		}
	}

	public function loadCurrentShippingCustomFields(shopBuy1clickForm $form)
	{
		$selected_shipping = null;

		foreach ($form->getShipping() as $shipping)
		{
			$is_selected_shipping = $form->getSession()->getSelectedShippingId() == $shipping->getId();

			if ($is_selected_shipping)
			{
				$selected_shipping = $shipping;
				break;
			}
		}

		if ($selected_shipping)
		{
			$selected_shipping->setCustomFields($this->getShippingCustomFields($form, $selected_shipping));
		}
	}

	private function getShippingCustomFields(shopBuy1clickForm $form, shopBuy1clickShipping $shipping)
	{
		try
		{
			/** @var waShipping $wa_plugin */
			$wa_plugin = shopShipping::getPlugin(null, $shipping->getId());
			$wa_contact_converter = new shopBuy1clickWaContactInfoConverter();

			$contact_info = $form->getContactInfo();
			$order_without_auth = $this->env->getCheckoutConfig()->getOrderWithoutAuth();

			$wa_contact = $contact_info->getID() > 0 && $order_without_auth !== 'create_contact'
				? $wa_contact_converter->toExistingWaContact($contact_info)
				: $wa_contact_converter->toNewWaContact($contact_info);

			$shipping_params = $form->getSession()->getShippingParams();

			$order = new waOrder(array(
				'contact'    => $wa_contact,
				'contact_id' => $wa_contact ? $wa_contact->getId() : null,
				'params'     => $shipping_params,
			));

			$custom_fields = $wa_plugin->customFields($order);

			if ($form->getSession()->getSelectedShippingId() == $shipping->getId())
			{
				foreach ($custom_fields as $name => $row)
				{
					if (isset($shipping_params[$name]))
					{
						$custom_fields[$name]['value'] = $shipping_params[$name];
					}
				}
			}

			$params = array();
			$params['namespace'] = "shipping_{$shipping->getId()}";
			$params['title_wrapper'] = '%s';
			$params['description_wrapper'] = '';
			$params['control_wrapper'] = '<div class="buy1click-form__field buy1click-form-field"><div class="buy1click-form-field__label">%s</div><div class="buy1click-form-field__box">%3$s %2$s</div></div>';
			$params['control_separator'] = '</div><div class="buy1click-form-field__box">';

			foreach ($custom_fields as $name => $custom_field)
			{
				$custom_field = array_merge($custom_field, $params);

				try
				{
					$custom_fields[$name]['html'] = waHtmlControl::getControl($custom_field['control_type'], $name, $custom_field);
				}
				catch (Exception $e)
				{
					$custom_fields[$name]['html'] = '';
				}
			}

			return $custom_fields;
		}
		catch (waException $ignored)
		{
		}

		return null;
	}

	public function loadShippingRates(shopBuy1clickForm $form)
	{
		$condition = new shopBuy1clickShippingRateCondition();
		$condition->setShippingAddress($form->getContactInfo()->getShippingAddress());
		$condition->setCart($form->getCart());

		foreach ($form->getShipping() as $shipping)
		{
			$is_selected_shipping = $form->getSession()->getSelectedShippingId() == $shipping->getId();

			if ($shipping->getPlugin()->getExternal())
			{
				continue;
			}

			$this->shipping_service->loadRates($shipping, $condition);

			$rates = $shipping->getRates();
			$rates = $this->handleRates($form, $shipping, $rates);

			if ($is_selected_shipping && !array_key_exists($form->getSession()->getSelectedShippingRateId(), $rates) && count($rates) > 0)
			{
				$first_rate = reset($rates);
				$form->getSession()->setSelectedShippingRateId($first_rate->getId());
			}
		}
	}

	public function loadCurrentShippingRates(shopBuy1clickForm $form)
	{
		$selected_shipping = null;

		foreach ($form->getShipping() as $shipping)
		{
			$is_selected_shipping = $form->getSession()->getSelectedShippingId() == $shipping->getId();

			if ($is_selected_shipping)
			{
				$selected_shipping = $shipping;
				break;
			}
		}

		if ($selected_shipping)
		{
			$condition = new shopBuy1clickShippingRateCondition();
			$condition->setShippingAddress($form->getContactInfo()->getShippingAddress());
			$condition->setCart($form->getCart());
			$this->shipping_service->loadRates($selected_shipping, $condition);
			$rates = $selected_shipping->getRates();
			$rates = $this->handleRates($form, $selected_shipping, $rates);

			if (!array_key_exists($form->getSession()->getSelectedShippingRateId(), $rates) && count($rates) > 0)
			{
				$first_rate = reset($rates);
				$form->getSession()->setSelectedShippingRateId($first_rate->getId());
			}
		}
	}

	public function loadExternalShippingRates(shopBuy1clickForm $form)
	{
		$condition = new shopBuy1clickShippingRateCondition();
		$condition->setShippingAddress($form->getContactInfo()->getShippingAddress());
		$condition->setCart($form->getCart());

		foreach ($form->getShipping() as $shipping)
		{
			$is_selected_shipping = $form->getSession()->getSelectedShippingId() == $shipping->getId();

			if (!$shipping->getPlugin()->getExternal())
			{
				continue;
			}

			$this->shipping_service->loadRates($shipping, $condition);
			$rates = $shipping->getRates();
			$rates = $this->handleRates($form, $shipping, $rates);

			if ($is_selected_shipping && !array_key_exists($form->getSession()->getSelectedShippingRateId(), $rates) && count($rates) > 0)
			{
				$first_rate = reset($rates);
				$form->getSession()->setSelectedShippingRateId($first_rate->getId());
			}
		}
	}

	public function loadPayments(shopBuy1clickForm $form)
	{
		$settings = $form->getSettings();
		$session = $form->getSession();

		if ($settings->getFormPaymentMode() == shopBuy1clickSettings::PAYMENT_MODE_DISABLED)
		{
			$payments = array();
		}
		else
		{
			$delpayfilter_cart = $this->buildCartCopy($form->getCart());

			$payment_condition = new shopBuy1clickPaymentFilterCondition();
			$payment_condition->filterAllow()
				->filterDelpayfilter($delpayfilter_cart)
				->filterCheckcustomer();

			if ($session->getSelectedShippingId())
			{
				$payment_condition->filterByShippingID($session->getSelectedShippingId());
			}

			if ($settings->getFormPaymentMode() == shopBuy1clickSettings::PAYMENT_MODE_SELECTED)
			{
				$payment_condition->filterByPaymentIDs($settings->getFormPayment());
			}

			$payments = $this->payment_service->getByCondition($payment_condition);

			$delpayfilter_cart->clear();
		}

		if (!array_key_exists($session->getSelectedPaymentId(), $payments) && count($payments) > 0)
		{
			$first_payment = reset($payments);
			$session->setSelectedPaymentId($first_payment->getId());
		}

		$form->setPayments($payments);
	}

	public function loadOrder(shopBuy1clickForm $form)
	{
		$order = $this->order_service->buildByForm($form);

		if ($form->getSettings()->getIgnoreShippingRateInTotal())
		{
			$this->order_service->loadTotalWithoutShipping($order);
		}
		else
		{
			$this->order_service->loadTotalWithShipping($order);
		}

		$form->setOrder($order);
	}

	public function store(shopBuy1clickForm $form)
	{
		$this->contact_info_service->store($form->getContactInfo());
		$this->session_service->store($form->getSession());
	}

	public function checkoutOrder(shopBuy1clickForm $form)
	{
		$this->order_service->checkout($form->getOrder());
	}

	/**
	 * @param shopBuy1clickForm $form
	 * @param shopBuy1clickShipping $shipping
	 * @param shopBuy1clickShippingRate[] $rates
	 * @return mixed
	 */
	private function handleRates(shopBuy1clickForm $form, shopBuy1clickShipping $shipping, $rates)
	{
		$is_enabled_freedelivery_plugin = isset($this->freedelivery_plugin);

		if ($is_enabled_freedelivery_plugin)
		{
			$this->freedelivery_plugin->cart = $form->getCart();

			foreach ($rates as $rate)
			{
				$is_free_shipping = $this->freedelivery_plugin->isFreeShipping($shipping->getId(), $rate->getId());

				if ($is_free_shipping)
				{
					$rate->setCompareRate($rate->getRate());
					$rate->setRate(0);
				}
			}
		}

		return $rates;
	}

	public function loadConfirmationChannel(shopBuy1clickForm $form)
	{
		$email = $form->getOrder()->getContactInfo()->getEmail();
		$phone = $form->getOrder()->getContactInfo()->getPhone();

		$confirmation_channel = $this->confirmation_channel_service->getConfirmationChannel($email, $phone);

		$form->setConfirmationChannel($confirmation_channel);
	}

	public function updateCurrentContactByConfirmation(shopBuy1clickForm $form)
	{
		$order_without_auth = $this->env->getCheckoutConfig()->getOrderWithoutAuth();

		if ($order_without_auth === 'existing_contact' || $order_without_auth === 'confirm_contact')
		{
			$saved_contact = $form->getConfirmationChannel()->getContact();
			if ($saved_contact['id'] > 0)
			{
				$form_contact_info = $form->getContactInfo();
				$form_contact_info->setID($saved_contact['id']);
			}
		}
	}

	public function resetConfirmation(shopBuy1clickForm $form)
	{
		$session = $form->getSession();

		$session->setConfirmationChannelType('');
		$session->setConfirmationChannelAddress('');
		$session->setConfirmationChannelIsLastChannel('');

		$form->getConfirmationChannel()->clearConfirmedStorage();

		$this->session_service->store($session);
	}

	private function buildCartCopy(shopBuy1clickCart $copy)
	{
		$cart_copy = new shopBuy1clickWaTempCart(md5('cart_copy' . microtime()));
		foreach ($copy->getItems() as $item) {
			$cart_copy->addItem($item);
		}

		return $cart_copy;
	}
}
