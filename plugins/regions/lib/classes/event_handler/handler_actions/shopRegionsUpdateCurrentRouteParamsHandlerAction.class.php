<?php

class shopRegionsUpdateCurrentRouteParamsHandlerAction implements shopRegionsIHandlerAction
{
	private $plugin_model;

	public function __construct()
	{
		$this->plugin_model = new shopPluginModel();
	}

	public function execute($plugin_routes)
	{
		$regions_routing = new shopRegionsRouting();
		$current_city = $regions_routing->getCurrentCity();

		$storefront_settings = $current_city ? $current_city->getStorefrontSettings() : false;

		if ($storefront_settings !== false)
		{
			foreach ($storefront_settings as $key => $value)
			{
				if ($key === 'payment_id' && is_array($value) && count($value))
				{
					$value = $this->filterDisabledPaymentMethods($value);
				}
				elseif ($key === 'shipping_id' && is_array($value) && count($value))
				{
					$value = $this->filterDisabledShippingMethods($value);
				}

				waRequest::setParam($key, $value);
			}
		}
		else
		{
			foreach (array('payment_id', 'shipping_id') as $key)
			{
				$value = waRequest::param($key);
				if ($key === 'payment_id' && is_array($value) && count($value))
				{
					waRequest::setParam($key, $this->filterDisabledPaymentMethods($value));
				}
				elseif ($key === 'shipping_id' && is_array($value) && count($value))
				{
					waRequest::setParam($key, $this->filterDisabledShippingMethods($value));
				}
			}
		}

		$storage = wa()->getStorage();
		$cart = $storage->get('shop/cart');
		$stored_currency = $storage->get('shop/currency');
		$storefront_currency = waRequest::param('currency');
		if (is_array($cart) && (!$stored_currency || $stored_currency != $storefront_currency))
		{
			unset($cart['total']);
			$storage->set('shop/cart', $cart);
			//$storage->set('shop/currency', $storefront_currency); // todo это сбрасывает выбранную юзером валюту
		}
	}

	private function filterDisabledPaymentMethods($ids)
	{
		$filtered_ids = $this->plugin_model
			->select('id')
			->where('type = :type', array('type' => shopPluginModel::TYPE_PAYMENT))
			->where('id IN (s:ids)', array('ids' => $ids))
			->where('status = 1')
			->fetchAll('id');

		return array_keys($filtered_ids);
	}

	private function filterDisabledShippingMethods($ids)
	{
		$filtered_ids = $this->plugin_model
			->select('id')
			->where('type = :type', array('type' => shopPluginModel::TYPE_SHIPPING))
			->where('id IN (s:ids)', array('ids' => $ids))
			->where('status = 1')
			->fetchAll('id');

		return array_keys($filtered_ids);
	}
}