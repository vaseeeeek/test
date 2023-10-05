<?php

class shopBrandWaAppConfig extends waAppConfig
{
	public static function renameBrandPlugin(waAppConfig $config, $new_name)
	{
		if (array_key_exists('brand', $config->plugins) && array_key_exists('name', $config->plugins['brand']))
		{
			$config->plugins['brand']['name'] = $new_name;
		}
	}
}