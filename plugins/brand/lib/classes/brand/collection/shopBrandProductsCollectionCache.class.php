<?php

class shopBrandProductsCollectionCache
{
	const DEFAULT_CACHE_CHUNK_SIZE = 100;

	private $storefront;
	private $brand_id;
	private $currency;
	private $ttl;

	public function __construct($storefront, $brand_id, $currency, $ttl_seconds = 300)
	{
		$this->storefront = $storefront;
		$this->brand_id = $brand_id;
		$this->currency = $currency;
		$this->ttl = $ttl_seconds;
	}

	//public static function getCachePath()
	//{
	//	return wa()->getCachePath('cache/shop_breadcrumbs_plugin/', 'shop');
	//}

	public function get()
	{
		$cache = $this->getCache();

		if (!$cache->isCached())
		{
			return null;
		}

		$data = $cache->get();

		if (
			!is_array($data)
			|| !array_key_exists($this->brand_id, $data)
			|| !is_array($data[$this->brand_id])
		)
		{
			return null;
		}

		$entity_value = $data[$this->brand_id];
		$ttl_field = $this->extensionStoreTimeField();

		if (is_array($entity_value))
		{
			if (isset($entity_value[$ttl_field]) && (time() - $entity_value[$ttl_field] > $this->ttl))
			{
				return null;
			}

			unset($entity_value[$ttl_field]);
		}

		return $entity_value;
	}

	public function set($entity_value)
	{
		$cache = $this->getCache();

		$data = $cache->isCached()
			? $cache->get()
			: array();

		if (is_array($entity_value))
		{
			$entity_value[$this->extensionStoreTimeField()] = time();
		}

		$data[$this->brand_id] = $entity_value;

		$cache->set($data);
	}

	/**
	 * @return waSerializeCache
	 */
	private function getCache()
	{
		$chunk_size = $this->getGroupChunkSize($this->brand_id);

		$brand_range_key = (int)($this->brand_id / $chunk_size);

		$cache_path = 'plugins/brand/brand_products_collection/' . md5("{$this->storefront}:{$this->currency}:{$brand_range_key}");

		return new waSerializeCache($cache_path, $this->ttl, 'shop');
	}

	private function getGroupChunkSize($cache_group)
	{
		return self::DEFAULT_CACHE_CHUNK_SIZE;
	}

	private function extensionStoreTimeField()
	{
		return '_store_timestamp';
	}
}
