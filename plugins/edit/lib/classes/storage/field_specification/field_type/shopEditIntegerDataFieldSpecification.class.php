<?php

class shopEditIntegerDataFieldSpecification implements shopEditIDataFieldSpecification
{
	private $default_value = 0;

	public function __construct($default_value = null)
	{
		if (wa_is_int($default_value))
		{
			$this->default_value = intval($default_value);
		}
		elseif ($default_value === null)
		{
			$this->default_value = $default_value;
		}
	}

	public function toAccessible($raw_value)
	{
		return wa_is_int($raw_value)
			? intval($raw_value)
			: $this->defaultValue();
	}

	public function toStorable($value)
	{
		return strval(intval($value));
	}

	public function defaultValue()
	{
		return $this->default_value;
	}
}