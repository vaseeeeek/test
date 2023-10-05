<?php


class shopBuy1clickWaOrderConverter
{
	public function toWaArrayOrder(shopBuy1clickOrder $order, $apply_discount, $order_without_auth)
	{
		$wa_contact_converter = new shopBuy1clickWaContactInfoConverter();

		$contact_info = $order->getContactInfo();

		$wa_contact = $contact_info->getID() > 0 && $order_without_auth !== 'create_contact'
			? $wa_contact_converter->toExistingWaContact($contact_info)
			: $wa_contact_converter->toNewWaContact($contact_info);

		$order_items = $order->getItems();

		$array_order = array(
			'contact' => $wa_contact,
			'items' => $order_items,
			'total' => $order->getSubTotal(),
			'params' => array(
				shopBuy1clickOrderService::PARAM_IS_BUY1CLICK => '1',
			),
		);
		$selected_rate = $order->getShippingRate();

		$session_shipping = null;
		if (isset($selected_rate))
		{
			$selected_shipping = $order->getShipping();
			$wa_shipping = $selected_shipping->getPlugin()->getWaShipping();

			if (class_exists('shopShipping') && method_exists('shopShipping', 'extendItems'))
			{
				$options = array(
					'currency' => $wa_shipping->allowedCurrency(),
					'weight' => $wa_shipping->allowedWeightUnit(),
				);
				$units = array(
					'weight' => ifset($options, 'weight', true),
					'dimensions' => ifset($options, 'dimensions', true),
				);
				shopShipping::extendItems($order_items, $units);

				$array_order['items'] = $order_items;
			}

			if (class_exists('shopShipping') && method_exists('shopShipping', 'getItemsTotal'))
			{
				$total = shopShipping::getItemsTotal($order_items);
				foreach ($total as $field => $value)
				{
					$array_order['params']['package_' . $field] = $value;
				}
			}

			$array_order['params']['shipping_id'] = $selected_shipping->getID();
			$array_order['params']['shipping_rate_id'] = $selected_rate->getID();
			$array_order['params']['shipping_plugin'] = $selected_shipping->getPlugin()->getId();

			if ($selected_rate->getName())
			{
				$array_order['params']['shipping_name'] = "{$selected_shipping->getName()} ({$selected_rate->getName()})";
			}
			else
			{
				$array_order['params']['shipping_name'] = $selected_shipping->getName();
			}

			$array_order['params']['shipping_est_delivery'] = $selected_rate->getEstDelivery();
			$array_order['shipping'] = shop_currency($selected_rate->getRate(), $selected_rate->getCurrency(), null, false);

			$session_shipping = array(
				'id' => $array_order['params']['shipping_id'],
				'rate_id' => $array_order['params']['shipping_rate_id'],
				'rate' => $array_order['shipping'] ? $array_order['shipping'] : 0,
				'name' => $selected_rate->getName() ? $selected_rate->getName() : '',
				'plugin' => $array_order['params']['shipping_plugin'] ? $array_order['params']['shipping_plugin'] : '',
			);
		}
		else
		{
			$array_order['shipping'] = 0;
		}

		foreach ($order->getShippingCustomFields() as $field_name => $field_value)
		{
			$array_order['params']["shipping_params_{$field_name}"] = $field_value;
		}

		/** @var shopBuy1clickPayment $selected_payment */
		$selected_payment = $order->getPayment();
		$session_payment = null;
		if (isset($selected_payment))
		{
			$array_order['params']['payment_id'] = $selected_payment->getID();
			$array_order['params']['payment_name'] = $selected_payment->getName();

			$payment_plugin = $selected_payment->getPlugin();
			$array_order['params']['payment_plugin'] = $payment_plugin->getId() ?: null;

			$session_payment = $selected_payment->getId();
		}

		$session_data_memento = new shopBuy1clickSessionDataMemento();

		$session_checkout_data = wa()->getStorage()->get('shop/checkout');
		$session_checkout_data['coupon_code'] = $order->getCoupon();

		$session_checkout_data['shipping'] = $session_shipping;
		$session_checkout_data['payment'] = $session_payment;

		$session_data_memento->replace('shop/checkout', $session_checkout_data);

		waRequest::setParam('flexdiscount_force_calculate', true);

		$array_order['discount_description'] = null;
		$array_order['discount'] = shopDiscounts::calculate($array_order, $apply_discount, $array_order['discount_description']);

		$session_data_memento->rollback('shop/checkout');

		if (isset($array_order['params']))
		{
			$array_order['params'] = array_merge($array_order['params'], $this->getWaOrderParams());
		}
		else
		{
			$array_order['params'] = $this->getWaOrderParams();
		}
		
		foreach (array('shipping', 'billing') as $ext)
		{
			$address = $wa_contact->getFirst('address.' . $ext);
			
			if (isset($address['data']))
			{
				$address = $address['data'];
			}
			
			foreach ($address as $k => $v)
			{
				$array_order['params'][$ext . '_address.' . $k] = $v;
			}
		}
		
		$array_order['comment'] = $order->getComment();

		return $array_order;
	}

	private function getWaOrderParams()
	{
		$params = array();

		if (wa()->getStorage()->get('shop_order_buybutton'))
		{
			$params['sales_channel'] = 'buy_button:';
		}
		
		if (($ref = waRequest::cookie('referer')))
		{
			$params['referer'] = $ref;
			$ref_parts = @parse_url($ref);
			$params['referer_host'] = $ref_parts['host'];
			// try get search keywords
			if (!empty($ref_parts['query']))
			{
				$search_engines = array(
					'text' => 'yandex\.|rambler\.',
					'q' => 'bing\.com|mail\.|google\.',
					's' => 'nigma\.ru',
					'p' => 'yahoo\.com',
				);
				$q_var = false;
				foreach ($search_engines as $q => $pattern)
				{
					if (preg_match('/(' . $pattern . ')/si', $ref_parts['host']))
					{
						$q_var = $q;
						break;
					}
				}
				// default query var name
				if (!$q_var)
				{
					$q_var = 'q';
				}
				parse_str($ref_parts['query'], $query);
				if (!empty($query[$q_var]))
				{
					$params['keyword'] = $query[$q_var];
				}
			}
		}
		
		if (($utm = waRequest::cookie('utm')))
		{
			$utm = json_decode($utm, true);
			if ($utm && is_array($utm))
			{
				foreach ($utm as $k => $v)
				{
					$params['utm_' . $k] = $v;
				}
			}
		}
		
		if (($landing = waRequest::cookie('landing')) && ($landing = @parse_url($landing)))
		{
			if (!empty($landing['query']))
			{
				@parse_str($landing['query'], $arr);
				if (!empty($arr['gclid'])
					&& !empty($params['referer_host'])
					&& strpos($params['referer_host'], 'google') !== false
				)
				{
					$params['referer_host'] .= ' (cpc)';
					$params['cpc'] = 1;
				}
				elseif (!empty($arr['_openstat'])
					&& !empty($params['referer_host'])
					&& strpos($params['referer_host'], 'yandex') !== false
				)
				{
					$params['referer_host'] .= ' (cpc)';
					$params['openstat'] = $arr['_openstat'];
					$params['cpc'] = 1;
				}
			}
			
			$params['landing'] = $landing['path'];
		}
		
		// A/B tests
		$abtest_variants_model = new shopAbtestVariantsModel();
		foreach (waRequest::cookie() as $k => $v)
		{
			if (substr($k, 0, 5) == 'waabt')
			{
				$variant_id = $v;
				$abtest_id = substr($k, 5);
				if (wa_is_int($abtest_id) && wa_is_int($variant_id))
				{
					$row = $abtest_variants_model->getById($variant_id);
					if ($row && $row['abtest_id'] == $abtest_id)
					{
						$params['abt' . $abtest_id] = $variant_id;
					}
				}
			}
		}
		
		$params['ip'] = waRequest::getIp();
		$params['user_agent'] = waRequest::getUserAgent();
		$routing_url = wa()->getRouting()->getRootUrl();
		$storefront = wa()->getConfig()->getDomain() . ($routing_url ? '/' . $routing_url : '');
		$params['storefront'] = $storefront;
		$params['sales_channel'] = "buy1click:{$storefront}";
		
		return $params;
	}
}
