<?php

class shopBrandDataFieldSpecificationFactory
{
	public function integer($default_value = 0)
	{
		return new shopBrandIntegerDataFieldSpecification($default_value);
	}

	public function string($default_value = '')
	{
		return new shopBrandStringDataFieldSpecification($default_value);
	}

	public function double($default_value = 0)
	{
		return new shopBrandDoubleDataFieldSpecification($default_value);
	}

	public function boolean($default_value = false)
	{
		return new shopBrandBooleanDataFieldSpecification($default_value);
	}

	public function jsonArray(shopBrandIDataFieldSpecification $array_element_specification, $default_value = array())
	{
		return new shopBrandJsonArrayDataFieldSpecification($array_element_specification, $default_value);
	}

	public function enum(shopBrandEnumOptions $enum_options, $default_value)
	{
		return new shopBrandEnumDataFieldSpecification($enum_options, $default_value);
	}

	public function datetime($default_timestamp = null)
	{
		return new shopBrandDatetimeFieldSpecification($default_timestamp);
	}
}