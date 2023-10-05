<?php


class shopBuy1clickOrderService
{
	const PARAM_IS_BUY1CLICK = 'buy1click_is_buy1click';

	private $env;
	
	public function __construct(shopBuy1clickEnv $env)
	{
		$this->env = $env;
	}

	/**
	 * @param shopBuy1clickForm $form
	 * @return shopBuy1clickOrder
	 */
	public function buildByForm(shopBuy1clickForm $form)
	{
		$order = new shopBuy1clickOrder();
		$items = $form->getCart()->getItems();
		
		foreach ($items as $i => $item)
		{
			unset($items[$i]['id']);
		}
		
		$order->setContactInfo($form->getContactInfo());
		$order->setItems($items);
		$order->setSubtotal($form->getCart()->getTotal());
		
		$shipping = $form->getShipping();
		/** @var shopBuy1clickShipping|null $selected_shipping */
		$selected_shipping = ifset($shipping[$form->getSession()->getSelectedShippingId()]);
		$order->setShipping($selected_shipping);

		if ($selected_shipping && $form->getSettings()->isEnabledFormShippingCustomFields())
		{
			$this->setOrderShippingCustomFields($order, $form, $selected_shipping);
		}
		
		$rates = array();
		if ($order->getShipping())
		{
			$rates = $selected_shipping->getRates();
		}
		
		/** @var shopBuy1clickShippingRate $selected_rate */
		$selected_rate = ifset($rates[$form->getSession()->getSelectedShippingRateId()]);
		$order->setShippingRate($selected_rate);
		
		$payments = $form->getPayments();
		/** @var shopBuy1clickPayment $selected_payment */
		$selected_payment = ifset($payments[$form->getSession()->getSelectedPaymentId()]);
		$order->setPayment($selected_payment);
		$order->setCoupon($form->getSession()->getCoupon());
		$order->setComment($form->getSession()->getComment());

		return $order;
	}

	public function loadTotalWithoutShipping(shopBuy1clickOrder $order)
	{
		$this->loadTotal($order, false);
	}

	public function loadTotalWithShipping(shopBuy1clickOrder $order)
	{
		$this->loadTotal($order, true);
	}

	public function checkout(shopBuy1clickOrder $order)
	{
		$order_without_auth = $this->env->getCheckoutConfig()->getOrderWithoutAuth();
		$wa_order_converter = new shopBuy1clickWaOrderConverter();
		$array_order = $wa_order_converter->toWaArrayOrder($order, true, $order_without_auth);
		$workflow = new shopWorkflow();

		if ($order_id = $workflow->getActionById('create')->run($array_order))
		{
			$order->setID($order_id);
		}
	}

	private function loadTotal(shopBuy1clickOrder $order, $with_shipping_cost)
	{
		if ($this->env->isEnabledFlexdiscountPlugin() && $this->env->isAvailableFlexdiscountSetShopProducts())
		{
			$flex_discount_data = new shopFlexdiscountData();
			$ids = array();

			foreach ($order->getItems() as $item)
			{
				$ids[] = $item['product_id'];
			}

			$products_collection = new shopProductsCollection($ids);
			$products = $products_collection->getProducts('*', 0, count($ids));

			$flex_discount_data->setShopProducts($products);
		}

		$wa_order_converter = new shopBuy1clickWaOrderConverter();
		$order_without_auth = $this->env->getCheckoutConfig()->getOrderWithoutAuth();
		$array_order = $wa_order_converter->toWaArrayOrder($order, false, $order_without_auth);

		$order->setShippingTotal($with_shipping_cost ? $array_order['shipping'] : 0);
		$order->setDiscount($array_order['discount']);
		$order->setDiscountDescription($array_order['discount_description']);

		$fee_params = $this->getIncreasePluginFee($order->getPayment(), $order->getSubtotal() + $order->getShippingTotal());
		$order->setIncreasePluginFee($fee_params['amount'], $fee_params['name']);
	}

	private function setOrderShippingCustomFields(shopBuy1clickOrder $order, shopBuy1clickForm $form, shopBuy1clickShipping $selected_shipping)
	{
		$shipping_params = $form->getSession()->getShippingParams();
		$shipping_custom_fields = array();

		foreach ($selected_shipping->getCustomFields() as $field_name => $_)
		{
			$field_form_key = "shipping_{$selected_shipping->getId()}[{$field_name}]";
			if (array_key_exists($field_form_key, $shipping_params))
			{
				$shipping_custom_fields[$field_name] = $shipping_params[$field_form_key];
			}
		}

		$order->setShippingCustomFields($shipping_custom_fields);
	}

	/**
	 * @param shopBuy1clickPayment|null $payment
	 * @param $total
	 * @return array
	 */
	private function getIncreasePluginFee($payment, $total)
	{
		if (!shopBuy1clickPlugin::getContext()->getEnv()->isIncreasePluginEnabled() || !$payment || !$payment->getId())
		{
			return array(
				'amount' => 0,
				'name' => '',
			);
		}

		$params = array(
			'payment_id' => $payment->getId(),
		);
		$commission = shopIncreasePlugin::getCommission($params, $total);

		if (!is_array($commission))
		{
			return array(
				'amount' => 0,
				'name' => '',
			);
		}

		return array(
			'amount' => $commission['value'],
			'name' => $commission['name'],
		);
	}
}
