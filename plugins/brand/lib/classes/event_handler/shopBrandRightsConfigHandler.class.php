<?php

class shopBrandRightsConfigHandler
{
	/**
	 * @param waRightConfig $config
	 */
	public function handle($config)
	{
		if ($config instanceof waRightConfig)
		{
			$plugin_user_rights = new shopBrandPluginUserRights();

			$plugin_user_rights->updateRightsConfig($config);
		}
	}
}
