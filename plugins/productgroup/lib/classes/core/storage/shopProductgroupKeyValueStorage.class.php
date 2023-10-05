<?php

abstract class shopProductgroupKeyValueStorage
{
	/**
	 * @param array $settings_raw
	 * @param $field
	 * @return mixed
	 * @throws shopProductgroupIncompleteStorageSpecification
	 */
	protected function getValueFromRaw(array $settings_raw, $field)
	{
		$field_specification = $this->tryGetFieldSpecification($field);

		$type = $field_specification->stored_type;
		if (!array_key_exists($field, $settings_raw))
		{
			return $field_specification->default_value;
		}

		$value_raw = $settings_raw[$field];
		if ($type === shopProductgroupStoredValueType::BOOL)
		{
			return $value_raw === '1';
		}
		elseif ($type === shopProductgroupStoredValueType::INT)
		{
			return intval($value_raw);
		}
		elseif ($type === shopProductgroupStoredValueType::DOUBLE)
		{
			return doubleval($value_raw);
		}
		elseif ($type === shopProductgroupStoredValueType::STRING)
		{
			return strval($value_raw);
		}
		elseif ($type === shopProductgroupStoredValueType::STRING_TRIM)
		{
			return trim(strval($value_raw));
		}
		elseif ($type === shopProductgroupStoredValueType::ASSOC)
		{
			$decoded_value = json_decode($value_raw, true);
			if (!is_array($decoded_value))
			{
				$decoded_value = [];
			}

			return $decoded_value;
		}
		elseif ($type === shopProductgroupStoredValueType::ASSOC_ARRAY)
		{
			$decoded_value = json_decode($value_raw, true);
			if (!is_array($decoded_value))
			{
				$decoded_value = [];
			}

			return $decoded_value;
		}
		else
		{
			throw new shopProductgroupIncompleteStorageSpecification("Неизвестный тип поля [{$field}]: [{$type}]");
		}
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 * @return string
	 * @throws shopProductgroupIncompleteStorageSpecification
	 */
	protected function valueToRaw($field, $value)
	{
		$field_specification = $this->tryGetFieldSpecification($field);

		$type = $field_specification->stored_type;

		if ($type === shopProductgroupStoredValueType::BOOL)
		{
			return $value ? '1' : '0';
		}
		elseif ($type === shopProductgroupStoredValueType::INT)
		{
			return strval(intval($value));
		}
		elseif ($type === shopProductgroupStoredValueType::DOUBLE)
		{
			return strval(doubleval($value));
		}
		elseif ($type === shopProductgroupStoredValueType::STRING)
		{
			return strval($value);
		}
		elseif ($type === shopProductgroupStoredValueType::STRING_TRIM)
		{
			return trim(strval($value));
		}
		elseif ($type === shopProductgroupStoredValueType::ASSOC)
		{
			$encoded_value = json_encode($value);
			if (!is_string($encoded_value))
			{
				$encoded_value = '{}';
			}

			return $encoded_value;
		}
		elseif ($type === shopProductgroupStoredValueType::ASSOC_ARRAY)
		{
			$encoded_value = null;
			if (is_array($value))
			{
				$encoded_value = json_encode(array_values($value));
			}

			if (!is_string($encoded_value))
			{
				$encoded_value = '[]';
			}

			return $encoded_value;
		}
		else
		{
			throw new shopProductgroupIncompleteStorageSpecification("Неизвестный тип поля [{$field}]: [{$type}]");
		}
	}

	/**
	 * @param string $field
	 * @return shopProductgroupStoredValueSpecification
	 * @throws shopProductgroupIncompleteStorageSpecification
	 */
	private function tryGetFieldSpecification($field)
	{
		$specification = $this->getFieldSpecifications();

		if (!array_key_exists($field, $specification) || !($specification[$field] instanceof shopProductgroupStoredValueSpecification))
		{
			throw new shopProductgroupIncompleteStorageSpecification("Не указана спецификация поля [{$field}]");
		}

		return $specification[$field];
	}

	/**
	 * @return shopProductgroupStoredValueSpecification[]
	 */
	abstract protected function getFieldSpecifications();
}