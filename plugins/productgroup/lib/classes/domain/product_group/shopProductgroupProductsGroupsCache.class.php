<?php

class shopProductgroupProductsGroupsCache
{
	const PRODUCTS_BY_GROUP = 50;

	private static $caches = [];

	/**
	 * @param int[] $product_ids
	 * @return shopProductgroupProductProductsGroup[][]
	 */
	public function getProductsGroups(array $product_ids)
	{
		$key_groups = $this->getKeyGroups($product_ids);

		$products_groups = [];
		foreach ($key_groups as $group_key => $group_product_ids)
		{
			$products_groups_cache = self::getCacheObject($group_key);

			if (!$products_groups_cache->isCached())
			{
				continue;
			}
			$cached_products_groups = $products_groups_cache->get();

			foreach ($group_product_ids as $product_id)
			{
				if (array_key_exists($product_id, $cached_products_groups))
				{
					$products_groups[$product_id] = $cached_products_groups[$product_id];
				}
			}
		}

		return $products_groups;
	}

	/**
	 * @param int[] $product_ids
	 * @param shopProductgroupProductProductsGroup[][] $products_groups
	 * @return bool
	 */
	public function storeProductsGroups(array $product_ids, array $products_groups)
	{
		$key_groups = $this->getKeyGroups($product_ids);

		foreach ($key_groups as $group_key => $group_product_ids)
		{
			$products_groups_cache = self::getCacheObject($group_key);

			$cached_products_groups = $products_groups_cache->isCached()
				? $products_groups_cache->get()
				: [];

			foreach ($group_product_ids as $product_id)
			{
				$cached_products_groups[$product_id] = array_key_exists($product_id, $products_groups)
					? $products_groups[$product_id]
					: [];
			}

			$products_groups_cache->set($cached_products_groups);
		}

		return true;
	}

	public function clearProductsGroups(array $product_ids)
	{
		$key_groups = $this->getKeyGroups($product_ids);

		foreach ($key_groups as $group_key => $group_product_ids)
		{
			$products_groups_cache = self::getCacheObject($group_key);

			$cached_products_groups = $products_groups_cache->isCached()
				? $products_groups_cache->get()
				: [];

			foreach ($group_product_ids as $product_id)
			{
				 unset($cached_products_groups[$product_id]);
			}

			$products_groups_cache->set($cached_products_groups);
		}

		return true;
	}

	public function clearAll()
	{
		$products_groups_cache_path = wa()->getCachePath('cache/productgroup/products_groups/', 'shop');

		return waFiles::delete($products_groups_cache_path);
	}

	private function getKeyGroups(array $product_ids)
	{
		$key_groups = [];
		foreach ($product_ids as $product_id)
		{
			$key_index = floor($product_id / self::PRODUCTS_BY_GROUP - 1e-6);
			$key = "productgroup/products_groups/{$key_index}";

			if (!array_key_exists($key, $key_groups))
			{
				$key_groups[$key] = [];
			}

			$key_groups[$key][$product_id] = $product_id;
		}

		return $key_groups;
	}

	/**
	 * @param string $group_key
	 * @return waSerializeCache
	 */
	private static function getCacheObject($group_key)
	{
		$ttl_seconds = 24 * 60 * 60;
		//$ttl_seconds = intval($ttl_seconds * (1 + rand(-15, 15) * 0.01));

		if (!array_key_exists($group_key, self::$caches))
		{
			self::$caches[$group_key] = new waSerializeCache($group_key, $ttl_seconds, 'shop');
		}

		return self::$caches[$group_key];
	}
}