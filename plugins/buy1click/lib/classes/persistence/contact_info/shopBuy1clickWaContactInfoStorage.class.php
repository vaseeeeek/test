<?php


class shopBuy1clickWaContactInfoStorage implements shopBuy1clickContactInfoStorage
{
	private $customer_form_config;
	
	public function __construct(shopBuy1clickWaCustomerForm $customer_form_config)
	{
		$this->customer_form_config = $customer_form_config;
	}
	
	/**
	 * @return shopBuy1clickContactInfo
	 */
	public function getCurrent()
	{
		$user = $this->getCurrentContact();

		$contact_info = new shopBuy1clickContactInfo();
		$shipping_address = new shopBuy1clickContactInfoShippingAddress();

		if ($user->exists())
		{
			$contact_info->setID($user->getId());
		}
		
		$fields = $this->customer_form_config->getFields();
		$address = $user->getFirst('address.shipping');

		foreach ($fields as $field)
		{
			if ($field['code'] == 'name')
			{
				$contact_info->setName($user->get($field['code'], 'default'));
			}
			elseif ($field['code'] == 'firstname')
			{
				$contact_info->setFirstName($user->get($field['code'], 'default'));
			}
			elseif ($field['code'] == 'lastname')
			{
				$contact_info->setLastName($user->get($field['code'], 'default'));
			}
			elseif ($field['code'] == 'middlename')
			{
				$contact_info->setMiddleName($user->get($field['code'], 'default'));
			}
			elseif ($field['code'] == 'email')
			{
				$contact_info->setEmail($user->get($field['code'], 'default'));
			}
			elseif ($field['code'] == 'phone')
			{
				$contact_info->setPhone($user->get($field['code'], 'default'));
			}
			elseif (strpos($field['code'], 'address_') === 0)
			{
				$code = preg_replace('/^address_/', '', $field['code']);
				
				if ($code == 'country')
				{
					$shipping_address->setCountry(ifset($address['data'][$code]));
				}
				elseif ($code == 'region')
				{
					$shipping_address->setRegion(ifset($address['data'][$code]));
				}
				elseif ($code == 'city')
				{
					$shipping_address->setCity(ifset($address['data'][$code]));
				}
				elseif ($code == 'street')
				{
					$shipping_address->setStreet(ifset($address['data'][$code]));
				}
				elseif ($code == 'zip')
				{
					$shipping_address->setZip(ifset($address['data'][$code]));
				}
				else
				{
					$custom_fields = $shipping_address->getCustomFields();
					$custom_fields[$code] = ifset($address['data'][$code]);
					$shipping_address->setCustomFields($custom_fields);
				}
			}
			else
			{
				$custom_fields = $contact_info->getCustomFields();
				$custom_fields[$field['code']] = $user->get($field['code'], 'default');
				$contact_info->setCustomFields($custom_fields);
			}
		}
		
		$country_field = $this->customer_form_config->getCountryField();
		
		if ($country_field['is_hidden'])
		{
			$shipping_address->setCountry($country_field['value']);
		}
		
		$region_field = $this->customer_form_config->getRegionField();
		
		if ($region_field['is_hidden'])
		{
			$shipping_address->setRegion($region_field['value']);
		}
		
		$contact_info->setShippingAddress($shipping_address);

		return $contact_info;
	}

	public function store(shopBuy1clickContactInfo $contact_info)
	{
		$user = $this->getCurrentContact();
		
		if ($contact_info->getName())
		{
			$user->set('name', $contact_info->getName());
		}
		
		if ($contact_info->getFirstName())
		{
			$user->set('firstname', $contact_info->getFirstName());
		}
		
		if ($contact_info->getLastName())
		{
			$user->set('lastname', $contact_info->getLastName());
		}
		
		if ($contact_info->getMiddleName())
		{
			$user->set('middlename', $contact_info->getMiddleName());
		}
		
		$user->set('email', $contact_info->getEmail());
		$user->set('phone', $contact_info->getPhone());
		
		foreach ($contact_info->getCustomFields() as $code => $value)
		{
			$user->set($code, $value);
		}
		
		$address = array(
			'country' => $contact_info->getShippingAddress()->getCountry(),
			'region' => $contact_info->getShippingAddress()->getRegion(),
			'city' => $contact_info->getShippingAddress()->getCity(),
			'street' => $contact_info->getShippingAddress()->getStreet(),
			'zip' => $contact_info->getShippingAddress()->getZip(),
		);
		
		foreach ($contact_info->getShippingAddress()->getCustomFields() as $code => $value)
		{
			$address[$code] = $value;
		}
		
		$user->set('address.shipping', $address);

		if ($user->exists())
		{
			$user->save();
		}
		else
		{
			$data = wa()->getStorage()->get('shop/checkout');
			$data['contact'] = $user;
			wa()->getStorage()->set('shop/checkout', $data);
		}
	}

	private function getCurrentContact()
	{
		if (wa()->getUser()->isAuth())
		{
			$contact = wa()->getUser();
		}
		else
		{
			$data = wa()->getStorage()->get('shop/checkout');

			if ($data)
			{
				$contact = ifset($data['contact']);
			}
		}

		if (isset($contact))
		{
			if (!$contact->get('address.shipping') && $addresses = $contact->get('address'))
			{
				$contact->set('address.shipping', $addresses[0]);
			}
		}

		return isset($contact) ? $contact : new waContact();
	}
}