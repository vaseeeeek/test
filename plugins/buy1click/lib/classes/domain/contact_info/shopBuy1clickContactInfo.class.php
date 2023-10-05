<?php


class shopBuy1clickContactInfo
{
	private $id;
	private $name;
	private $first_name;
	private $last_name;
	private $middle_name;
	private $email;
	private $phone;
	private $custom_fields = array();
	/** @var shopBuy1clickContactInfoShippingAddress */
	private $shipping_address;

	public function getID()
	{
		return $this->id;
	}

	public function setID($id)
	{
		$this->id = $id;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}

	public function getFirstName()
	{
		return $this->first_name;
	}

	public function setFirstName($first_name)
	{
		$this->first_name = $first_name;
	}

	public function getLastName()
	{
		return $this->last_name;
	}

	public function setLastName($last_name)
	{
		$this->last_name = $last_name;
	}

	public function getMiddleName()
	{
		return $this->middle_name;
	}

	public function setMiddleName($middle_name)
	{
		$this->middle_name = $middle_name;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function getPhone()
	{
		return $this->phone;
	}

	public function setPhone($phone)
	{
		$this->phone = $phone;
	}
	
	public function getCustomFields()
	{
		return $this->custom_fields;
	}
	
	public function setCustomFields($custom_fields)
	{
		$this->custom_fields = $custom_fields;
	}
	
	public function getShippingAddress()
	{
		return $this->shipping_address;
	}
	
	public function setShippingAddress($shipping_address)
	{
		$this->shipping_address = $shipping_address;
	}
	
	public function toArray()
	{
		$array = array(
			'name' => $this->getName(),
			'firstname' => $this->getFirstName(),
			'lastname' => $this->getLastName(),
			'middlename' => $this->getMiddleName(),
			'phone' => $this->getPhone(),
			'email' => $this->getEmail(),
		);
		
		foreach ($this->getCustomFields() as $code => $value)
		{
			$array[$code] = $value;
		}
		
		$array['shipping_address'] = $this->getShippingAddress()->toArray();
		
		return $array;
	}
}