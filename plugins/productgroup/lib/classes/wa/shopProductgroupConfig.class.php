<?php

class shopProductgroupConfig
{
	/**
	 * @return shopProductgroupStorageFactory
	 */
	public static function getStorageFactory()
	{
		return new shopProductgroupWaStorageFactory();
	}
}