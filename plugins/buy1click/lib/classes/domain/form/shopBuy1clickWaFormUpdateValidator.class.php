<?php


class shopBuy1clickWaFormUpdateValidator implements shopBuy1clickFormValidator
{
	public function getErrors(shopBuy1clickForm $form)
	{
		$errors = array();

		$order = $form->getOrder();

		$total_without_shipping = $order->getSubtotal() - $order->getDiscount();
		$settings = $form->getSettings();
		
		/** @var shopConfig $config */
		$shop_system = wa(shopBuy1clickPlugin::SHOP_ID);
		$config = $shop_system->getConfig();
		$primary_currency = $config->getCurrency(true);
		$shop_currency = $config->getCurrency(false);
		$min_total = shop_currency($settings->getOrderMinTotal(), $primary_currency, $shop_currency, false);
		
		if ($total_without_shipping < $min_total)
		{
			$errors['min_order'] = true;
		}
		
		if (!$shop_system->getSetting('ignore_stock_count'))
		{
			$check_count = true;
			
			if ($shop_system->getSetting('limit_main_stock') && waRequest::param('stock_id'))
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
}
