<?php


class shopBuy1clickSettings
{
	const SHIPPING_MODE_DISABLED = shopBuy1clickFormSettings::SHIPPING_MODE_DISABLED;
	const SHIPPING_MODE_ALL = shopBuy1clickFormSettings::SHIPPING_MODE_ALL;
	const SHIPPING_MODE_SELECTED = shopBuy1clickFormSettings::SHIPPING_MODE_SELECTED;
	const PAYMENT_MODE_DISABLED = shopBuy1clickFormSettings::PAYMENT_MODE_DISABLED;
	const PAYMENT_MODE_ALL = shopBuy1clickFormSettings::PAYMENT_MODE_ALL;
	const PAYMENT_MODE_SELECTED = shopBuy1clickFormSettings::PAYMENT_MODE_SELECTED;

	private $basic_settings;
	private $storefront_settings;
	private $form_settings;

	public function __construct(
		shopBuy1clickBasicSettings $basic_settings,
		shopBuy1clickStorefrontSettings $storefront_settings,
		shopBuy1clickFormSettings $form_settings
	)
	{
		$this->basic_settings = $basic_settings;
		$this->storefront_settings = $storefront_settings;
		$this->form_settings = $form_settings;
	}

	public function toArray()
	{
		return array_merge(
			$this->basic_settings->toArray(),
			$this->storefront_settings->toArray(),
			$this->form_settings->toArray()
		);
	}

	public function isEnabled()
	{
		return $this->basic_settings->isEnabled();
	}

	public function isEqualFormSettings()
	{
		return $this->storefront_settings->isEqualFormSettings();
	}

	public function isEnabledButton()
	{
		return $this->form_settings->isEnabledButton();
	}

	public function isUseHook()
	{
		return $this->form_settings->isUseHook();
	}

	public function getFormView()
	{
		return $this->form_settings->getFormView();
	}

	public function getButtonText()
	{
		return $this->form_settings->getButtonText();
	}

	public function getFormName()
	{
		return $this->form_settings->getFormName();
	}

	public function getFormText()
	{
		return $this->form_settings->getFormText();
	}

	public function getFormMainColor()
	{
		return $this->form_settings->getFormMainColor();
	}

	public function getFormButtonTextColor()
	{
		return $this->form_settings->getFormButtonTextColor();
	}

	public function getFormButtonBorderWidth()
	{
		return $this->form_settings->getFormButtonBorderWidth();
	}

	public function getFormButtonBorderColor()
	{
		return $this->form_settings->getFormButtonBorderColor();
	}

	public function getButtonColor()
	{
		return $this->form_settings->getButtonColor();
	}

	public function getButtonTextColor()
	{
		return $this->form_settings->getButtonTextColor();
	}

	public function getButtonBorderWidth()
	{
		return $this->form_settings->getButtonBorderWidth();
	}

	public function getButtonBorderColor()
	{
		return $this->form_settings->getButtonBorderColor();
	}

	public function getButtonClass()
	{
		return $this->form_settings->getButtonClass();
	}

	public function getFormFields()
	{
		return $this->form_settings->getFormFields();
	}

	public function getFormButtonText()
	{
		return $this->form_settings->getFormButtonText();
	}

	public function getOrderMinTotal()
	{
		return $this->form_settings->getOrderMinTotal();
	}

	public function getHideButtonIfOutOfStock()
	{
		return $this->form_settings->getHideButtonIfOutOfStock();
	}

	public function getIgnoreShippingRateInTotal()
	{
		return $this->form_settings->getIgnoreShippingRateInTotal();
	}

	public function isEnabledFormQuantity()
	{
		return $this->form_settings->isEnabledFormQuantity();
	}

	public function isEnabledFormCoupon()
	{
		return $this->form_settings->isEnabledFormCoupon();
	}

	public function isEnabledFormComment()
	{
		return $this->form_settings->isEnabledFormComment();
	}

	public function isEnabledFormCaptcha()
	{
		return $this->form_settings->isEnabledFormCaptcha();
	}

	public function isEnabledFormShowPhoto()
	{
		return $this->form_settings->isEnabledFormShowPhoto();
	}

	public function getFormPhotoWidth()
	{
		return $this->form_settings->getFormPhotoWidth();
	}

	public function getFormPhotoHeight()
	{
		return $this->form_settings->getFormPhotoHeight();
	}

	public function isEnabledFormPolicy()
	{
		return $this->form_settings->isEnabledFormPolicy();
	}

	public function getFormPolicyText()
	{
		return $this->form_settings->getFormPolicyText();
	}

	public function isEnabledFormIncludeCartItems()
	{
		return $this->form_settings->isEnabledFormIncludeCartItems();
	}

	public function isEnabledFormShowItems()
	{
		return $this->form_settings->isEnabledFormShowItems();
	}

	public function isEnabledFormShowTotal()
	{
		return $this->form_settings->isEnabledFormShowItems();
	}

	public function isEnabledGeoIp()
	{
		return $this->form_settings->isEnabledGeoIp();
	}

	public function getFormShippingMode()
	{
		return $this->form_settings->getFormShippingMode();
	}

	public function getFormShipping()
	{
		return $this->form_settings->getFormShipping();
	}

	public function isEnabledFormShippingCustomFields()
	{
		return $this->form_settings->isEnabledFormShippingCustomFields();
	}

	public function showInvalidFormShippingMethods()
	{
		return $this->form_settings->showInvalidFormShippingMethods();
	}

	public function allowCheckoutWithoutShipping()
	{
		return $this->form_settings->allowCheckoutWithoutShipping();
	}

	public function getFormPaymentMode()
	{
		return $this->form_settings->getFormPaymentMode();
	}

	public function getFormPayment()
	{
		return $this->form_settings->getFormPayment();
	}

	public function getTargetYmCounter()
	{
		return $this->form_settings->getTargetYmCounter();
	}

	public function getTargetYmOpenForm()
	{
		return $this->form_settings->getTargetYmOpenForm();
	}

	public function getTargetYmSendForm()
	{
		return $this->form_settings->getTargetYmSendForm();
	}

	public function getTargetYmSendFailForm()
	{
		return $this->form_settings->getTargetYmSendFailForm();
	}

	public function getTargetGaCategoryOpenForm()
	{
		return $this->form_settings->getTargetGaCategoryOpenForm();
	}

	public function getTargetGaCategorySendForm()
	{
		return $this->form_settings->getTargetGaCategorySendForm();
	}

	public function getTargetGaCategorySendFailForm()
	{
		return $this->form_settings->getTargetGaCategorySendFailForm();
	}

	public function getTargetGaActionOpenForm()
	{
		return $this->form_settings->getTargetGaActionOpenForm();
	}

	public function getTargetGaActionSendForm()
	{
		return $this->form_settings->getTargetGaActionSendForm();
	}

	public function getTargetGaActionSendFailForm()
	{
		return $this->form_settings->getTargetGaActionSendFailForm();
	}
}
