<?php

class shopRegionsCurrentContact
{
	private static $auth = null;

	/**
	 * @param shopRegionsCity $region_city
	 */
	public static function updateShipping($region_city)
	{
		if (!$region_city || waRequest::cookie('shop_region_remember_address', false))
		{
			return;
		}

		$contact = self::getCurrentContact();
		if (!($contact instanceof waContact))
		{
			return;
		}

		$address = $contact->get('address:shipping');//warm up the cache
		$add = count($address) === 0;

		$contact_city = $contact->get('address:city', 'default');
		$contact_region = $contact->get('address:region', 'default');
		$contact_country = $contact->get('address:country', 'default');

		if (self::getAuth())
		{
			if (empty($contact_city) && empty($contact_region) && empty($contact_country))
			{
				$city = $region_city->getName();
				$region = $region_city->getRegionCode();
				$country = $region_city->getCountryIso3();
			}
			else
			{
				return;
			}
		}
		else
		{
			$city = $region_city->getName();
			$region = $region_city->getRegionCode();
			$country = $region_city->getCountryIso3();

			if ($contact_city === $city && $contact_region === $region && $contact_country === $country)
			{
				return;
			}
		}

		$contact->set('address:country.shipping', $country, $add);
		$contact->set('address:region.shipping', $country && $region ? $region : '', $add);
		$contact->set('address:city.shipping', $country && $region && $city ? $city : '', $add);

		self::updateCurrentContact($contact);
	}

	public static function getCurrentContact()
	{
		$auth = self::getAuth();

		if ($auth)
		{
			$contact = new waContact($auth['id']);
		}
		else
		{
			$session = wa()->getStorage();
			$checkout = $session->get('shop/checkout');
			if (!$checkout)
			{
				$checkout = array();
			}

			$contact = isset($checkout['contact']) && ($checkout['contact'] instanceof waContact)
				? $checkout['contact']
				: new waContact();
		}

		return $contact;
	}

	/**
	 * @param waContact $contact
	 */
	private static function updateCurrentContact($contact)
	{
		if (!($contact instanceof waContact))
		{
			return;
		}

		$auth = self::getAuth();

		if ($auth)
		{
			$contact->save();
		}

		$session = wa()->getStorage();
		$checkout = $session->get('shop/checkout');
		if (!is_array($checkout))
		{
			$checkout = array();
		}
		$checkout['contact'] = $contact;


		if (!array_key_exists('order', $checkout))
		{
			$checkout['order'] = array();
		}

		if (!array_key_exists('region', $checkout['order']))
		{
			$checkout['order']['region'] = array();
		}

		$country = $contact->get('address:country.shipping', 'default');
		$region = $contact->get('address:region.shipping', 'default');
		$city = $contact->get('address:city.shipping', 'default');

		$shop_version = wa()->getVersion('shop');
		if (version_compare($shop_version, '8.0', '>='))
		{
			$checkout['order']['region']['country'] = $country;
			$checkout['order']['region']['region'] = $region;
			$checkout['order']['region']['city'] = $city;

			$location_id = self::getLocationId($country, $region, $city);
			if ($location_id !== null)
			{
				$checkout['order']['region']['location_id'] = $location_id;
			}
		}

		$session->set('shop/checkout', $checkout);
	}

	private static function getAuth()
	{
		if (self::$auth === null)
		{
			$is_template = waConfig::get('is_template');
			waConfig::set('is_template', false);

			try
			{
				$auth_adapter = wa()->getAuth();

				if ($auth_adapter)
				{
					$login_field_ids = $auth_adapter->getOption('login_field_ids');

					self::$auth = is_array($login_field_ids) && count($login_field_ids) !== 0
						? $auth_adapter->isAuth()
						: false;
				}
				else
				{
					self::$auth = false;
				}
			}
			catch (waException $e)
			{
				self::$auth = false;
			}

			waConfig::set('is_template', $is_template);
		}

		return self::$auth;
	}

	private static function getLocationId($country, $region, $city)
	{
		$route = wa()->getRouting()->getRoute();
		$checkout_config = new shopCheckoutConfig(ifset($route, 'checkout_storefront_id', []));

		// shopCheckoutConfig::ORDER_MODE_TYPE_DEFAULT или shopCheckoutConfig::SHIPPING_MODE_TYPE_DEFAULT
		// @webasyst, зачем ты контанты переименовываешь?
		$mode_default = 'default';

		if (ifempty($checkout_config, 'shipping', 'mode', $mode_default) == $mode_default)
		{
			return null;
		}

		$locations_list = ifempty($checkout_config, 'shipping', 'locations_list', []);

		foreach ($locations_list as $location_id => $location)
		{
			if (
				ifset($location, 'enabled', false)
				&& ifset($location, 'country', '') === $country
				&& ifset($location, 'region', '') === $region
				&& ifset($location, 'city', '') === $city
			)
			{
				return $location_id;
			}
		}

		return null;
	}
}
