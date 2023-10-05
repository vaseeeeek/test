<?php


class shopBuy1clickWaFormSendValidator extends shopBuy1clickWaFormUpdateValidator
{
	public function getErrors(shopBuy1clickForm $form)
	{
		$errors = parent::getErrors($form);
		
		if (!$this->checkCaptcha($form))
		{
			$errors['captcha'] = true;
		}

		$settings = $form->getSettings();
		$selected_fields = $settings->getFormFields();
		$contact_info = $form->getContactInfo()->toArray();

		$session = $form->getSession();
		$selected_shipping_id = $session->getSelectedShippingId();
		$shipping = $form->getShipping();
		
		if (array_key_exists($selected_shipping_id, $shipping))
		{
			$selected_shipping = $shipping[$selected_shipping_id];
			/** @var waShipping $wa_plugin */
			$wa_plugin = shopShipping::getPlugin(null, $selected_shipping->getId());
			$fields = $wa_plugin->requestedAddressFields();
			
			if (is_array($fields))
			{
				$fields_ids = array_keys($fields);
				
				foreach ($selected_fields as $field_id => $field)
				{
					if (strpos($field['code'], 'address_') === 0)
					{
						$code = preg_replace('/^address_/', '', $field['code']);
						
						if (in_array($code, $fields_ids))
						{
							if (array_key_exists('required', $fields[$code]) && $fields[$code]['required'])
							{
								$selected_fields[$field_id]['is_required'] = true;
							}
						}
					}
				}
			}
		}
		
		foreach ($selected_fields as $field_id => $field)
		{
			if (strpos($field['code'], 'address_') === 0)
			{
				$code = preg_replace('/^address_/', '', $field['code']);
				$value = $contact_info['shipping_address'][$code];
			}
			else
			{
				$value = $contact_info[$field['code']];
			}
			
			if (isset($field['is_required']) && $field['is_required'] && !$value)
			{
				if (!isset($errors['required_contact_fields']))
				{
					$errors['required_contact_fields'] = array();
				}
				
				$errors['required_contact_fields'][] = $field['code'];
			}
			
			if ($field['code'] == 'email' && !empty($value))
			{
				$validator = new waEmailValidator();
				
				if (!$validator->isValid($value))
				{
					$errors['invalid_email'] = true;
				}
			}
		}

		if (!wa(shopBuy1clickPlugin::SHOP_ID)->getSetting('ignore_stock_count'))
		{
			$check_count = true;
			
			if (wa(shopBuy1clickPlugin::SHOP_ID)->getSetting('limit_main_stock') && waRequest::param('stock_id'))
			{
				$check_count = waRequest::param('stock_id');
			}
			
			$cart_model = new shopCartItemsModel();
			$not_available_items = $cart_model->getNotAvailableProducts($form->getCart()->getCode(), $check_count);
			
			foreach ($not_available_items as $row)
			{
				if ($row['sku_name'])
				{
					$row['name'] .= ' (' . $row['sku_name'] . ')';
				}
				
				if ($row['available'])
				{
					if ($row['count'] > 0)
					{
						$errors['available']
							= sprintf(_w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.'),
							$row['count'], $row['name']);
					}
					else
					{
						$errors['available']
							= sprintf(_w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience. Please remove this product from your shopping cart to proceed.'),
							$row['name']);
					}
				}
				else
				{
					$errors['available']
						= sprintf(_w('Oops! %s is not available for purchase at the moment. Please remove this product from your shopping cart to proceed.'),
						$row['name']);
				}
			}
		}

		if ($form->getConfirmationChannel()->hasUnconfirmedChannels())
		{
			$errors['confirm_channel'] = 'Необходимо подтвердить свои контактные данные.';
		}

		if (
			$settings->getFormShippingMode() !== shopBuy1clickSettings::SHIPPING_MODE_DISABLED
			&& !$settings->allowCheckoutWithoutShipping()
			&& !$form->getSession()->getSelectedShippingId()
		)
		{
			$errors['shipping_required'] = true;
		}

		return $errors;
	}

	private function checkCaptcha(shopBuy1clickForm $form)
	{
		$settings = $form->getSettings();

		if (!$settings->isEnabledFormCaptcha())
		{
			return true;
		}

		$captcha = wa(shopBuy1clickPlugin::SHOP_ID)->getCaptcha();

		return $captcha->isValid();
	}
}
