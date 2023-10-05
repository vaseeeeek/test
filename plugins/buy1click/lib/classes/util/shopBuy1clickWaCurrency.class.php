<?php


class shopBuy1clickWaCurrency extends waCurrency
{
	private static $original_data;
	
	public static function getData()
	{
		return parent::getData();
	}
	
	public static function setData($data)
	{
		if (!isset(self::$original_data))
		{
			self::getData();
			self::$original_data = self::$data;
		}
		
		self::$data = $data;
	}
	
	public static function rollback()
	{
		self::$data = self::$original_data;
	}
}