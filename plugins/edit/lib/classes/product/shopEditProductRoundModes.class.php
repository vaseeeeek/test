<?php

class shopEditProductRoundModes
{
	const POINT_01 = 'POINT_01';
	const POINT_1 = 'POINT_1';
	const POINT_99 = 'POINT_99';
	const INT_1 = 'INT_1';
	const INT_10 = 'INT_10';
	const INT_99 = 'INT_99';
	const INT_100 = 'INT_100';
	const NONE = 'NONE';

	public static function getRoundModeRounding($mode)
	{
		if ($mode == self::POINT_01)
		{
			return 0.01;
		}
		if ($mode == self::POINT_1)
		{
			return 0.1;
		}
		if ($mode == self::POINT_99)
		{
			return 0.99;
		}
		if ($mode == self::INT_1)
		{
			return 1;
		}
		if ($mode == self::INT_10)
		{
			return 10;
		}
		if ($mode == self::INT_99)
		{
			return 99;
		}
		if ($mode == self::INT_100)
		{
			return 100;
		}

		throw new waException("Неизвестный метод округления [{$mode}]");
	}
}