<?php


class shopBuy1clickPluginFrontendFormSendController extends shopBuy1clickPluginFrontendFormUpdateStateController
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function handleForm(shopBuy1clickForm $form)
	{
		$this->form_service->loadShipping($form);
		$this->form_service->loadCurrentShippingRates($form);
		$this->form_service->loadCurrentShippingCustomFields($form);
		$this->form_service->updateSelectedShipping($form);

		$this->form_service->loadPayments($form);
		$this->form_service->loadOrder($form);

		$this->form_service->loadConfirmationChannel($form);

		$form->validate(new shopBuy1clickWaFormSendValidator());

		if (count($form->getErrors()) != 0)
		{
			$this->form_service->loadShippingRates($form);
			$this->form_service->loadShippingCustomFields($form);

			$form_view = new shopBuy1clickFormView($form);

			$this->response = array(
				'state' => $form_view->getState(),
				'html' => $form_view->render(),
			);

			return;
		}

		$this->form_service->updateCurrentContactByConfirmation($form);

		$this->finishCheckout($form);
	}

	private function finishCheckout(shopBuy1clickForm $form)
	{
		$this->form_service->checkoutOrder($form);

		$saved_order_contact_id = $form->getOrder()->getContactInfo()->getID();


		$wa_version = wa()->getVersion('webasyst');
		$order_without_auth = $this->env->getCheckoutConfig()->getOrderWithoutAuth();
		if (version_compare($wa_version, '1.10', '>') && $order_without_auth !== 'create_contact')
		{
			$this->sendRegisterMail($saved_order_contact_id);
		}

		$form->getConfirmationChannel()->finishContactConfirmation(
			$saved_order_contact_id,
			$form->getContactInfo()->getID() > 0
		);


		$this->updateSessionStorageOnCheckoutSuccess($form);


		$redirect_url = wa(shopBuy1clickPlugin::SHOP_ID)->getRouteUrl('shop/frontend/checkout', array('step' => 'success'));

		$this->response = array(
			'redirect_url' => $redirect_url,
		);
	}

	private function updateSessionStorageOnCheckoutSuccess(shopBuy1clickForm $form)
	{
		wa()->getStorage()->set('shop/order_id', $form->getOrder()->getID());

		$cart = $form->getCart();
		$cart->clear();

		if ($form->getSettings()->isEnabledFormIncludeCartItems() || $form->getType() == shopBuy1clickForm::TYPE_CART)
		{
			$shop_cart = new shopCart();
			$shop_cart->clear();
			wa()->getStorage()->del('shop/checkout');
		}
	}

	// копипаста из shopFrontendOrderActions
	private function sendRegisterMail($contact_id)
	{
		$contact = new waContact($contact_id);
		$result = $password = $template_variables = null;
		$template_variables = array();

		// If there is a password, do not need to register
		if ($contact['password'])
		{
			return $result;
		}

		$password = waContact::generatePassword();

		$contact->setPassword($password);
		$contact->save();

		$auth_config = waDomainAuthConfig::factory();
		$channels = $auth_config->getVerificationChannelInstances();

		// You do not need to transfer the password in the one-time password mode
		if ($auth_config->getAuthType() !== $auth_config::AUTH_TYPE_ONETIME_PASSWORD)
		{
			$template_variables = ['password' => $password];
		}

		foreach ($channels as $channel)
		{
			$result = $channel->sendSignUpSuccessNotification($contact, $template_variables);
			if ($result)
			{
				break;
			}
		}

		return $result;
	}

	/**
	 * @param int $len
	 * @return string
	 */
	private function generatePassword($len = 11)
	{
		$alphabet = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789!@#$%^&*-?';
		$alphabet = str_split($alphabet, 1);
		shuffle($alphabet);
		$password = array();
		for ($i = 0; $i < $len; $i++) {
			$key = array_rand($alphabet);
			$password[] = $alphabet[$key];
		}
		return join('', $password);
	}
}
