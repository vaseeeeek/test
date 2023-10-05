<?php


class shopBuy1clickSettingsUtil
{
	public static function fill($keys, $value, $data)
	{
		return array_merge(array_fill_keys($keys, $value), $data);
	}
	
	public static function merge($child_data, $parent_data)
	{
		foreach ($parent_data as $name => $value)
		{
			if (!isset($child_data[$name]))
			{
				$child_data[$name] = $parent_data[$name];
			}
		}
		
		return $child_data;
	}
	
	public static function diff($child_data, $parent_data)
	{
		foreach ($parent_data as $name => $value)
		{
			if (!isset($child_data[$name]))
			{
				continue;
			}
			
			if ($parent_data[$name] !== null && $parent_data[$name] == $child_data[$name])
			{
				$child_data[$name] = null;
			}
		}
		
		return $child_data;
	}
	
	public static function get($data, $name, $default, $is_allow_null)
	{
		$value = ifset($data[$name]);
		
		if ($value === null)
		{
			return $is_allow_null ? null : $default;
		}
		
		return $value;
	}
	
	public static function getBool($data, $name, $default, $is_allow_null)
	{
		$value = self::get($data, $name, $default, $is_allow_null);
		
		if ($value === null)
		{
			return $is_allow_null ? null : $default;
		}
		
		return !!$value;
	}
	
	public static function getFromVariants($data, $name, $variants, $default, $is_allow_null)
	{
		$value = self::get($data, $name, null, $is_allow_null);
		
		if (!in_array($value, $variants))
		{
			$value = null;
		}
		
		if ($value === null)
		{
			return $is_allow_null ? null : $default;
		}
		
		return $value;
	}
	
	public static function getArray($data, $name, $default, $is_allow_null)
	{
		$value = self::get($data, $name, $default, $is_allow_null);
		
		if (!is_array($value))
		{
			if (is_string($value))
			{
				$value = json_decode($value, true);
			}
			else
			{
				$value = null;
			}
		}
		
		if ($value === null)
		{
			return $is_allow_null ? null : $default;
		}
		
		return $value;
	}
}