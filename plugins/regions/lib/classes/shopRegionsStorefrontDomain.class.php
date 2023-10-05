<?php

class shopRegionsStorefrontDomain
{
	private static $domains = null;
	private static $tree = null;

	public function __construct()
	{
		if (is_null(self::$domains))
		{
			self::$domains = array();

			$domains = wa()->getRouting()->getDomains();

			$tree = array();
			foreach ($domains as $domain)
			{
				$split = explode('/', $domain);
				$tree = $this->subTree($tree, $split);
				self::$domains[$domain] = $domain;
			}

			self::$tree = $tree;
		}
	}

	/**
	 * @param string $storefront
	 * @return string
	 */
	public function search($storefront)
	{
		$storefront_trimmed = trim($storefront, '/*');

		$split = explode('/', $storefront_trimmed);
		$leaf = self::$tree;

		$domain_parts = array();

		foreach ($split as $key)
		{
			if (!isset($leaf[$key]))
			{
				break;
			}

			$leaf = $leaf[$key];
			$domain_parts[] = $key;
		}

		$domain = implode('/', $domain_parts);
		return isset(self::$domains[$domain])
			? $domain
			: null;
	}

	private function subTree($tree, $split)
	{
		if (count($split) === 0)
		{
			return $tree;
		}

		$key = array_shift($split);
		if (!isset($tree[$key]))
		{
			$tree[$key] = array();
		}

		$tree[$key] = $this->subTree($tree[$key], $split);

		return $tree;
	}
}