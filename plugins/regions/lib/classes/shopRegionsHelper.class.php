<?php


class shopRegionsHelper
{
	private static $is_regions_installed;

	private static $all_storefronts = null;

	private $m_country;
	private $m_regions;

	public function __construct()
	{
		$this->m_country = new waCountryModel();
		$this->m_regions = new waRegionModel();
	}

	public function getAllCountries()
	{
		return $this->m_country->all();
	}

	public function getRegionsByCountry($country_iso3)
	{
		return $this->m_regions->getByCountryWithFav($country_iso3);
	}

	public function getGroupByIso3Countries()
	{
		$countries = array();
		$_countries = $this->m_country->all();

		foreach ($_countries as $_country)
		{
			$countries[$_country['iso3letter']] = $_country['name'];
		}

		return $countries;
	}

	public function getAllPayments()
	{
		return shopHelper::getPaymentMethods();
	}

	public function getAllShipping()
	{
		return shopHelper::getShippingMethods();
	}

	public function getStocks()
	{
		$stocks = $public_stocks = array();

		if (method_exists('shopHelper', 'getStocks'))
		{
			foreach (shopHelper::getStocks() as $stock_id => $s)
			{
				$stocks[$stock_id] = $s['name'];
				if ($s['public'])
				{
					$public_stocks[$stock_id] = $s['name'];
				}
			}
		}
		else
		{
			$model = new shopStockModel();
			foreach ($model->getAll() as $stock)
			{
				$stocks[$stock['id']] = $stock['name'];
				if ($stock['public'])
				{
					$public_stocks[$stock['id']] = $stock['name'];
				}
			}
		}

		return array($stocks, $public_stocks);
	}

	public function getCookieDomain(shopRegionsCity $city)
	{
		$domains = wa()->getRouting()->getDomains();
		$storefront = new shopRegionsRoute($city->getStorefront());
		$domain = $storefront->getDomain()->getName();
		$min_length = null;
		$min_idx = null;

		foreach ($domains as $idx => $_domain)
		{
			if (strpos($domain, $_domain) !== false
				and (!isset($min_length) or strlen($_domain) < $min_length))
			{
				$min_length = strlen($_domain);
				$min_idx = $idx;
			}
		}

		return isset($idx) ? '.'.$domains[$min_idx] : '.'.$domain;
	}

	public function getAllCurrencies()
	{
		$currency_model = new shopCurrencyModel();
		return $currency_model->getCurrencies();
	}

	public function getAllStorefronts($reload = false)
	{
		if (self::$all_storefronts === null || $reload)
		{
			$routing = wa()->getRouting();

			self::$all_storefronts = array();
			foreach ($routing->getDomains() as $domain)
			{
				foreach ($routing->getByApp('shop', $domain) as $route)
				{
					if ($reload)
					{
						wa('site');
						$domain_model = new siteDomainModel();
						$domain_row = $domain_model->getByName($domain);
					}
					else
					{
						$domain_row = shopRegionsDomain::getDomainRow($domain);
					}

					self::$all_storefronts[$domain . '/' . $route['url']] = array(
						'name' => $domain . '/' . $route['url'],
						'route' => $route['url'],
						'domain' => $domain,
						'title' => ($domain_row['title'] ? $domain_row['title'] : $domain_row['name']) . '/' . $route['url'],
					);
				}
			}
		}

		return self::$all_storefronts;
	}

	public function isRegionsInstalled()
	{
		if (self::$is_regions_installed === null)
		{
			$info = wa('shop')->getConfig()->getPluginInfo('regions');

			$is_regions_installed = true;

			if ($info === array())
			{
				$is_regions_installed = false;
			}

			self::$is_regions_installed = $is_regions_installed;
		}

		return self::$is_regions_installed;
	}
}