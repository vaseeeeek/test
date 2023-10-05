<?php


class shopBrandWaRounding
{
	public function roundPrice($price)
	{
		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();

		$currencies = $config->getCurrencies();
		$default_currency = $config->getCurrency(true);
		$frontend_currency = $config->getCurrency(false);

		if ($price > 0)
		{
			$frontend_price = shop_currency($price, $default_currency, $frontend_currency, false);

			if (!empty($currencies[$frontend_currency]['rounding']) && $default_currency != $frontend_currency)
			{
				$frontend_price = shopRounding::roundCurrency($frontend_price, $frontend_currency);
				$price = shop_currency($frontend_price, $frontend_currency, $default_currency, false);
			}
		}

		return $price;
	}
}