<?php

class shopProductgroupGroupSettingsScope
{
	const PRODUCT = 'PRODUCT';
	const CATEGORY = 'CATEGORY';

	public static function getScopes()
	{
		return [
			self::PRODUCT,
			self::CATEGORY,
		];
	}
}