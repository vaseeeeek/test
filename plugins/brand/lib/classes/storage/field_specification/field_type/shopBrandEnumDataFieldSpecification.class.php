<?php

class shopBrandEnumDataFieldSpecification implements shopBrandIDataFieldSpecification
{
	private $enum_options;
	private $default_value;

	public function __construct(shopBrandEnumOptions $enum_options, $default_value)
	{
		$this->enum_options = $enum_options;
		$this->default_value = strval($default_value);
	}

	public function toAccessible($raw_value)
	{
		$raw_value = strval($raw_value);

		try
		{
			return $this->enum_options->$raw_value;
		}
		catch (waException $e)
		{
			return $this->defaultValue();
		}
	}

	public function toStorable($value)
	{
		$value = strval($value);

		try
		{
			return $this->enum_options->$value;
		}
		catch (waException $e)
		{
			return $this->defaultValue();
		}
	}

	public function defaultValue()
	{
		return $this->default_value;
	}
}