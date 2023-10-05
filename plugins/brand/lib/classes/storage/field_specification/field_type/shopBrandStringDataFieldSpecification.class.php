<?php

class shopBrandStringDataFieldSpecification implements shopBrandIDataFieldSpecification
{
	private $default_value = '';

	public function __construct($default_value = null)
	{
		if (is_string($default_value))
		{
			$this->default_value = strval($default_value);
		}
		elseif ($default_value === null)
		{
			$this->default_value = $default_value;
		}
	}

	public function toAccessible($raw_value)
	{
		return strval($raw_value);
	}

	public function toStorable($value)
	{
		return strval($value);
	}

	public function defaultValue()
	{
		return $this->default_value;
	}
}