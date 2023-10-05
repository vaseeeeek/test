<?php

class shopEditDataFieldSpecificationFactory
{
	public function integer($default_value = 0)
	{
		return new shopEditIntegerDataFieldSpecification($default_value);
	}

	public function string($default_value = '')
	{
		return new shopEditStringDataFieldSpecification($default_value);
	}

	public function double($default_value = 0)
	{
		return new shopEditDoubleDataFieldSpecification($default_value);
	}

	public function boolean($default_value = false)
	{
		return new shopEditBooleanDataFieldSpecification($default_value);
	}

	public function jsonArray(shopEditIDataFieldSpecification $array_element_specification, $default_value = array())
	{
		return new shopEditJsonArrayDataFieldSpecification($array_element_specification, $default_value);
	}

	public function enum(shopEditEnumOptions $enum_options, $default_value)
	{
		return new shopEditEnumDataFieldSpecification($enum_options, $default_value);
	}

	public function datetime($default_timestamp = null)
	{
		return new shopEditDatetimeFieldSpecification($default_timestamp);
	}

	public function splitArray(shopEditIDataFieldSpecification $array_element_specification, $delimiter, $default_value = array())
	{
		return new shopEditSplitArrayDataFieldSpecification($array_element_specification, $delimiter, $default_value);
	}
}