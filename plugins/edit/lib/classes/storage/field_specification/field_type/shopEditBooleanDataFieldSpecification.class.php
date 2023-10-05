<?php

class shopEditBooleanDataFieldSpecification implements shopEditIDataFieldSpecification
{
	const DB_TRUE = '1';
	const DB_FALSE = '0';

	private $default_value = false;

	public function __construct($default_value = null)
	{
		if (is_bool($default_value))
		{
			$this->default_value = $default_value;
		}
		elseif ($default_value === null)
		{
			$this->default_value = $default_value;
		}
	}

	public function toAccessible($raw_value)
	{
		if ($raw_value === self::DB_TRUE)
		{
			return true;
		}
		elseif ($raw_value === self::DB_FALSE)
		{
			return false;
		}
		else
		{
			return $this->defaultValue();
		}
	}

	public function toStorable($value)
	{
		return $value ? self::DB_TRUE : self::DB_FALSE;
	}

	public function defaultValue()
	{
		return $this->default_value;
	}
}