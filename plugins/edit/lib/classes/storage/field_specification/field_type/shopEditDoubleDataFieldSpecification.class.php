<?php

class shopEditDoubleDataFieldSpecification implements shopEditIDataFieldSpecification
{
	private $default_value = 0;

	public function __construct($default_value = null)
	{
		if (is_numeric($default_value))
		{
			$this->default_value = floatval($default_value);
		}
		elseif ($default_value === null)
		{
			$this->default_value = $default_value;
		}
	}

	public function toAccessible($raw_value)
	{
		return is_numeric($raw_value)
			? floatval($raw_value)
			: $this->defaultValue();
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