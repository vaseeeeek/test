<?php


class shopBuy1clickWaCustomerForm
{
	private $form;
	
	public function __construct(waContactForm $form)
	{
		$this->form = $form;
	}
	
	public function getFields()
	{
		$fields = $this->getWithHidden();
		$result_fields = array();
		
		foreach ($fields as $field)
		{
			if (!$field['is_hidden'])
			{
				$result_fields[] = $field;
			}
		}
		
		return $result_fields;
	}
	
	public function getCountryField()
	{
		$fields = $this->getWithHidden();
		
		foreach ($fields as $field)
		{
			if ($field['code'] == 'address_country')
			{
				return $field;
			}
		}
		
		return null;
	}
	
	public function getRegionField()
	{
		$fields = $this->getWithHidden();
		
		foreach ($fields as $field)
		{
			if ($field['code'] == 'address_region')
			{
				return $field;
			}
		}
		
		return null;
	}
	
	private function getWithHidden()
	{
		$fields = array();
		
		foreach ($this->form->fields as $field)
		{
			if (!($field instanceof waContactStringField || $field instanceof waContactAddressField))
			{
				continue;
			}
			
			if ($field->getId() == 'address')
			{
				/** @var waContactAddressField $address_field */
				$address_field = $field;
				
				foreach ($address_field->getFields() as $address_field)
				{
					$code = 'address_' . $address_field->getId();
					$array_field = array(
						'code' => $code,
						'is_hidden' => $address_field->isHidden(),
						'name' => $address_field->getName(),
					);
					
					if ($address_field->isHidden())
					{
						$array_field['value'] = $address_field->getParameter('value');
					}
					
					$fields[] = $array_field;
				}
			}
			else
			{
				$array_field = array(
					'code' => $field->getId(),
					'is_hidden' => $field->isHidden(),
					'name' => $field->getName(),
				);
				
				if ($field->isHidden())
				{
					$array_field['value'] = $field->getParameter('value');
				}
				
				$fields[] = $array_field;
			}
		}
		
		return $fields;
	}
}