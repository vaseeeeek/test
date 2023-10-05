<?php


class shopBuy1clickWaShippingPluginStorage implements shopBuy1clickShippingPluginStorage
{
	/**
	 * @param $shipping_id
	 * @return shopBuy1clickShippingPlugin
	 */
	public function getByShippingID($shipping_id)
	{
		$wa_plugin = null;

		try
		{
			$wa_plugin = shopShipping::getPlugin(null, $shipping_id);

			if ($wa_plugin->allowedCurrency() === '')
			{
				return null;
			}
		}
		catch (waException $e)
		{
		}

		if (!$wa_plugin)
		{
			return null;
		}

		return $this->toPlugin($wa_plugin);
	}

	private function toPlugin(waShipping $wa_plugin)
	{
		$plugin = new shopBuy1clickShippingPlugin();
		$plugin->setId($wa_plugin->getId());
		$plugin->setExternal($wa_plugin->getProperties('external'));
		$plugin->setIcon($wa_plugin->getProperties('icon'));
		$plugin->setImg($wa_plugin->getProperties('img'));
		$plugin->setWaShipping($wa_plugin);

		return $plugin;
	}
}
