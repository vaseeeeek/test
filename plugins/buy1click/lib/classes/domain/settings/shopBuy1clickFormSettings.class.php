<?php


class shopBuy1clickFormSettings
{
	const SHIPPING_MODE_DISABLED = 'disabled';
	const SHIPPING_MODE_ALL = 'all';
	const SHIPPING_MODE_SELECTED = 'selected';
	const PAYMENT_MODE_DISABLED = 'disabled';
	const PAYMENT_MODE_ALL = 'all';
	const PAYMENT_MODE_SELECTED = 'selected';

	private $data;
	private $env;

	public function __construct(shopBuy1clickSettingsData $data, shopBuy1clickEnv $env)
	{
		$this->data = $data;
		$this->env = $env;
	}

	public function toArray()
	{
		return array(
			'btn_is_enabled' => $this->isEnabledButton(),
			'btn_use_hook' => $this->isUseHook(),
			'form_view' => $this->getFormView(),
			'btn_text' => $this->getButtonText(),
			'form_name' => $this->getFormName(),
			'form_text' => $this->getFormText(),
			'form_main_color' => $this->getFormMainColor(),
			'form_button_text_color' => $this->getFormButtonTextColor(),
			'form_button_border_width' => $this->getFormButtonBorderWidth(),
			'form_button_border_color' => $this->getFormButtonBorderColor(),
			'btn_color' => $this->getButtonColor(),
			'btn_text_color' => $this->getButtonTextColor(),
			'btn_border_width' => $this->getButtonBorderWidth(),
			'btn_border_color' => $this->getButtonBorderColor(),
			'btn_class' => $this->getButtonClass(),
			'form_selected_fields' => $this->getFormFields(),
			'form_btn_text' => $this->getFormButtonText(),
			'order_min_total' => $this->getOrderMinTotal(),
			'hide_button_if_out_of_stock' => $this->getHideButtonIfOutOfStock(),
			'ignore_shipping_rate_in_total' => $this->getIgnoreShippingRateInTotal(),
			'form_quantity' => $this->isEnabledFormQuantity(),
			'form_coupon' => $this->isEnabledFormCoupon(),
			'form_comment' => $this->isEnabledFormComment(),
			'form_captcha' => $this->isEnabledFormCaptcha(),
			'form_show_photo' => $this->isEnabledFormShowPhoto(),
			'form_photo_width' => $this->getFormPhotoWidth(),
			'form_photo_height' => $this->getFormPhotoHeight(),
			'form_is_enabled_policy' => $this->isEnabledFormPolicy(),
			'form_policy_text' => $this->getFormPolicyText(),
			'form_include_cart_items' => $this->isEnabledFormIncludeCartItems(),
			'form_show_items' => $this->isEnabledFormShowItems(),
			'form_show_total' => $this->isEnabledFormShowTotal(),
			'form_geo_ip' => $this->isEnabledGeoIp(),
			'form_shipping' => $this->getFormShippingMode(),
			'form_shipping_selected' => $this->getFormShipping(),
			'form_shipping_custom_fields' => $this->isEnabledFormShippingCustomFields(),
			'form_shipping_show_invalid' => $this->showInvalidFormShippingMethods(),
			'form_payment' => $this->getFormPaymentMode(),
			'form_payment_selected' => $this->getFormPayment(),
			'form_target_ym_counter' => $this->getTargetYmCounter(),
			'form_target_ym_open_form' => $this->getTargetYmOpenForm(),
			'form_target_ym_send_form' => $this->getTargetYmSendForm(),
			'form_target_ym_send_fail_form' => $this->getTargetYmSendFailForm(),
			'form_target_ga_category_open_form' => $this->getTargetGaCategoryOpenForm(),
			'form_target_ga_category_send_form' => $this->getTargetGaCategorySendForm(),
			'form_target_ga_category_send_fail_form' => $this->getTargetGaCategorySendFailForm(),
			'form_target_ga_action_open_form' => $this->getTargetGaActionOpenForm(),
			'form_target_ga_action_send_form' => $this->getTargetGaActionSendForm(),
			'form_target_ga_action_send_fail_form' => $this->getTargetGaActionSendFailForm(),
		);
	}

	public function getData()
	{
		return $this->data;
	}

	public function isEnabledButton()
	{
		return $this->data->getBool('btn_is_enabled', false);
	}

	public function isUseHook()
	{
		return $this->data->getBool('btn_use_hook', false);
	}

	public function getFormView()
	{
		return $this->data->getFromVariants('form_view', array('modal', 'modal_extend'), 'modal');
	}

	public function getButtonText()
	{
		return $this->data->get('btn_text', 'Купить в 1 клик');
	}

	public function getFormName()
	{
		return $this->data->get('form_name', 'Купить в 1 клик');
	}

	public function getFormText()
	{
		return $this->data->get('form_text', '');
	}

	public function getFormMainColor()
	{
		return $this->data->get('form_main_color', '#f2994a');
	}

	public function getFormButtonTextColor()
	{
		return $this->data->get('form_button_text_color', '#ffffff');
	}

	public function getFormButtonBorderWidth()
	{
		return $this->data->get('form_button_border_width', '');
	}

	public function getFormButtonBorderColor()
	{
		return $this->data->get('form_button_border_color', '');
	}

	public function getButtonColor()
	{
		return $this->data->get('btn_color', '#f2994a');
	}

	public function getButtonTextColor()
	{
		return $this->data->get('btn_text_color', '#ffffff');
	}

	public function getButtonBorderWidth()
	{
		return $this->data->get('btn_border_width', '');
	}

	public function getButtonBorderColor()
	{
		return $this->data->get('btn_border_color', '');
	}

	public function getButtonClass()
	{
		return $this->data->get('btn_class', 'buy1click-button buy1click-button_type_item');
	}

	public function getFormFields()
	{
		$default_value = array(
			array('code' => 'firstname', 'is_required' => true, 'placeholder' => ''),
			array('code' => 'phone', 'is_required' => true, 'placeholder' => '', 'mask' => '+7(###)###-##-##'),
		);

		return $this->data->getArray('form_selected_fields', $default_value);
	}

	public function getFormButtonText()
	{
		return $this->data->get('form_btn_text', 'Оформить заказ');
	}

	public function getOrderMinTotal()
	{
		return $this->data->get('order_min_total', 0);
	}

	public function getHideButtonIfOutOfStock()
	{
		return $this->data->get('hide_button_if_out_of_stock', false);
	}

	public function getIgnoreShippingRateInTotal()
	{
		return $this->data->get('ignore_shipping_rate_in_total', false);
	}

	public function isEnabledFormQuantity()
	{
		return $this->data->getBool('form_quantity', true);
	}

	public function isEnabledFormCoupon()
	{
		return $this->data->getBool('form_coupon', false);
	}

	public function isEnabledFormComment()
	{
		return $this->data->getBool('form_comment', false);
	}

	public function isEnabledFormCaptcha()
	{
		return $this->data->getBool('form_captcha', false);
	}

	public function isEnabledFormShowPhoto()
	{
		return $this->data->getBool('form_show_photo', true);
	}

	public function getFormPhotoWidth()
	{
		return $this->data->get('form_photo_width', 40);
	}

	public function getFormPhotoHeight()
	{
		return $this->data->get('form_photo_height', 0);
	}

	public function isEnabledFormPolicy()
	{
		return $this->data->getBool('form_is_enabled_policy', true);
	}

	public function getFormPolicyText()
	{
		return $this->data->get('form_policy_text', 'Согласен на обработку <a href="#">персональных данных</a>');
	}

	public function isEnabledFormIncludeCartItems()
	{
		return $this->data->getBool('form_include_cart_items', false);
	}

	public function isEnabledFormShowItems()
	{
		return $this->data->getBool('form_show_items', true);
	}

	public function isEnabledFormShowTotal()
	{
		return $this->data->getBool('form_show_total', true);
	}

	public function isEnabledGeoIp()
	{
		if (!$this->env->isEnabledIpPlugin())
		{
			return false;
		}

		return $this->data->getBool('form_geo_ip', true);
	}

	public function getFormShippingMode()
	{
		$shipping_mode_variants = array(
			self::SHIPPING_MODE_DISABLED,
			self::SHIPPING_MODE_SELECTED,
			self::SHIPPING_MODE_ALL,
		);

		return $this->data->getFromVariants(
			'form_shipping',
			$shipping_mode_variants,
			self::SHIPPING_MODE_ALL
		);
	}

	public function getFormShipping()
	{
		return $this->data->getArray('form_shipping_selected', array());
	}

	public function isEnabledFormShippingCustomFields()
	{
		return $this->data->getBool('form_shipping_custom_fields', false);
	}

	public function showInvalidFormShippingMethods()
	{
		return $this->data->getBool('form_shipping_show_invalid', false);
	}

	public function allowCheckoutWithoutShipping()
	{
		return $this->data->getBool('form_shipping_allow_checkout_without_shipping', true);
	}

	public function getFormPaymentMode()
	{
		$payment_mode_variants = array(
			self::PAYMENT_MODE_DISABLED,
			self::PAYMENT_MODE_SELECTED,
			self::PAYMENT_MODE_ALL,
		);

		return $this->data->getFromVariants(
			'form_payment',
			$payment_mode_variants,
			self::PAYMENT_MODE_ALL
		);
	}

	public function getFormPayment()
	{
		return $this->data->getArray('form_payment_selected', array());
	}

	public function getTargetYmCounter()
	{
		return $this->data->get('form_target_ym_counter', '');
	}

	public function getTargetYmOpenForm()
	{
		return $this->data->get('form_target_ym_open_form', '');
	}

	public function getTargetYmSendForm()
	{
		return $this->data->get('form_target_ym_send_form', '');
	}

	public function getTargetYmSendFailForm()
	{
		return $this->data->get('form_target_ym_send_fail_form', '');
	}

	public function getTargetGaCategoryOpenForm()
	{
		return $this->data->get('form_target_ga_category_open_form', '');
	}

	public function getTargetGaCategorySendForm()
	{
		return $this->data->get('form_target_ga_category_send_form', '');
	}

	public function getTargetGaCategorySendFailForm()
	{
		return $this->data->get('form_target_ga_category_send_fail_form', '');
	}

	public function getTargetGaActionOpenForm()
	{
		return $this->data->get('form_target_ga_action_open_form', '');
	}

	public function getTargetGaActionSendForm()
	{
		return $this->data->get('form_target_ga_action_send_form', '');
	}

	public function getTargetGaActionSendFailForm()
	{
		return $this->data->get('form_target_ga_action_send_fail_form', '');
	}
}
