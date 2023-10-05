<?php

class shopRegionsRobotsFactory
{
	private static $global_template = null;

	/**
	 * @param $domain
	 * @return shopRegionsRobots
	 */
	public static function robots($domain)
	{
		$robots = new shopRegionsDomainRobots($domain);

		if ($robots->isNull())
		{
			$robots = self::globalTemplate();
		}

		return $robots;
	}

	public static function globalTemplate()
	{
		if (self::$global_template === null)
		{
			self::$global_template = new shopRegionsRobotsGlobalTemplate(null);
		}

		return self::$global_template;
	}
}