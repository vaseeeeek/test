<?php

class shopEditJsonArrayDataFieldSpecification implements shopEditIDataFieldSpecification
{
	private $default_value = array();
	private $array_element_specification;

	public function __construct(shopEditIDataFieldSpecification $array_element_specification, $default_value = null)
	{
		$this->array_element_specification = $array_element_specification;

		if (is_array($default_value))
		{
			$this->default_value = array_map(array($this, '_arrayItemToAccessible'), $default_value);
		}
	}

	public function toAccessible($json_value)
	{
		$value = json_decode($json_value, true);

		return is_array($value)
			? array_map(array($this, '_arrayItemToAccessible'), $value)
			: $this->defaultValue();
	}

	public function toStorable($value)
	{
		if (!is_array($value))
		{
			$value = $this->defaultValue();
		}

		$value = array_map(array($this, '_arrayItemToStorable'), $value);

		return json_encode($value);
	}

	public function defaultValue()
	{
		return $this->default_value;
	}

	private function _arrayItemToAccessible($item)
	{
		return $this->array_element_specification->toAccessible($item);
	}

	private function _arrayItemToStorable($item)
	{
		return $this->array_element_specification->toStorable($item);
	}
}