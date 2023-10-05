<?php

class shopDpLog
{
	const LOG_FILE = 'dp.log';

	public static function log($error, $file = null)
	{
		if($file === null) {
			$file = self::LOG_FILE;
		}

		if(is_string($error)) {
			waLog::log($error, $file);
		} else {
			waLog::dump($error, $file);
		}
	}

	public static function details($error, $file = null)
	{
		if(waRequest::cookie('dp_details')) {
			self::log($error, $file);
		}
	}
}