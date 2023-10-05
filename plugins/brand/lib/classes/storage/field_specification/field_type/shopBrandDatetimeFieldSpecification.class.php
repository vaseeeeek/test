<?php

class shopBrandDatetimeFieldSpecification implements shopBrandIDataFieldSpecification
{
	private $default_value = null;

	/**
	 * @param null $default_value если null, то текущее время
	 */
	public function __construct($default_value = null)
	{
		$this->default_value = $default_value;
	}

	public function toAccessible($raw_value)
	{
		return $raw_value;
	}

	public function toStorable($value)
	{
		$timestamp = wa_is_int($value)
			? $value
			: strtotime($value);

		return date('Y-m-d H:i:s', $timestamp);
	}

	public function defaultValue()
	{
		return $this->default_value;
	}
}