<?php

class shopDpLocationStorage
{
	protected $env;

	protected function getEnv()
	{
		if(!isset($this->env)) {
			$this->env = new shopDpEnv();
		}

		return $this->env;
	}

	public function save(shopDpLocation $location)
	{
		$contact = $this->getEnv()->getContact();

		$address = $contact->get('address.shipping');

		$address[0]['data']['country'] = $location->getCountry();
		$address[0]['data']['region'] = $location->getRegion();
		$address[0]['data']['city'] = $location->getCity();

		$zip = $location->getZip();
		if ($zip) {
			$address[0]['data']['zip'] = $zip;
		}

		$contact->set('address.shipping', $address);

		$this->getEnv()->saveContact($contact);

		wa()->getResponse()->setCookie('shop_region_remember_address', true);
	}
}