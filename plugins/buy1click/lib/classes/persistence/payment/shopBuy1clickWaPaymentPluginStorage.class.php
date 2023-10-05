<?php


class shopBuy1clickWaPaymentPluginStorage implements shopBuy1clickPaymentPluginStorage
{
	/**
	 * @param $payment_id
	 * @return shopBuy1clickPaymentPlugin
	 */
	public function getByPaymentId($payment_id)
	{
		try
		{
			$wa_plugin = shopPayment::getPlugin(null, $payment_id);
		
			if (!$wa_plugin)
			{
				return null;
			}
	
			return $this->toPlugin($wa_plugin);
		}
		catch (waException $e)
		{
			return null;
		}
	}
	
	private function toPlugin(waPayment $wa_plugin)
	{
		$allowed_currency = $wa_plugin->allowedCurrency();
		
		$plugin = new shopBuy1clickPaymentPlugin();
		$plugin->setId($wa_plugin->getId());
		$plugin->setIcon($wa_plugin->getProperties('icon'));
		$plugin->setIsAllowAnyCurrency($allowed_currency === true);
		
		if ($plugin->isAllowAnyCurrency())
		{
			$plugin->setAllowedCurrency(array());
		}
		else
		{
			$plugin->setAllowedCurrency((array)$allowed_currency);
		}
		
		return $plugin;
	}
}