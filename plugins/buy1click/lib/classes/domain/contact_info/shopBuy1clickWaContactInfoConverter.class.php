<?php


class shopBuy1clickWaContactInfoConverter
{
	public function toExistingWaContact(shopBuy1clickContactInfo $contact_info)
	{
		$contact = new waContact($contact_info->getID());

		$this->setContactData($contact, $contact_info);

		return $contact;
	}

	public function toNewWaContact(shopBuy1clickContactInfo $contact_info)
	{
		$contact = new waContact();

		$this->setContactData($contact, $contact_info);

		return $contact;
	}

	private function setContactData(waContact $contact, shopBuy1clickContactInfo $contact_info)
	{
		if ($contact_info->getName())
		{
			$contact->set('name', $contact_info->getName());
		}

		if ($contact_info->getFirstName())
		{
			$contact->set('firstname', $contact_info->getFirstName());
		}

		if ($contact_info->getLastName())
		{
			$contact->set('lastname', $contact_info->getLastName());
		}

		if ($contact_info->getMiddleName())
		{
			$contact->set('middlename', $contact_info->getMiddleName());
		}

		$contact->set('phone', $contact_info->getPhone());
		$contact->set('email', $contact_info->getEmail());
		$contact->set('address.shipping', $contact_info->getShippingAddress()->toArray());
	}
}
