<?php

class shopBrandProductsCollection extends shopProductsCollection
{
	private $product_sort_options;
	private $brand_id;

	private $is_clean = true;

	private $cache;

	public function __construct($hash = '', array $options = array())
	{
		$feature = shopBrandHelper::getBrandFeature();

		if ($feature && is_array($options) && array_key_exists('brand_id', $options))
		{
			$this->brand_id = $options['brand_id'];

			unset($options['brand_id']);
		}

		parent::__construct($hash, $options);

		$this->product_sort_options = new shopBrandProductSortEnumOptions();

		if ($this->brand_id > 0)
		{
			$code = $feature['code'];
			parent::filters(array(
				$code => $this->brand_id,
			));
		}

		$ttl_seconds = 24 * 60 * 60 * (rand(-20, 20) * 0.01);
		$this->cache = new shopBrandProductsCollectionCache($this->getStorefront(), $this->brand_id, $this->getCurrency(), $ttl_seconds);

		if (!$this->brand_id)
		{
			$this->is_clean = false;
		}
	}

	public function filters($data)
	{
		$this->is_clean = false;

		parent::filters($data);
	}

	public function setBrandSortProducts($sort)
	{
		$tmp = is_array($sort)
			? $sort
			: explode(' ', $sort);

		if (!isset($tmp[1]))
		{
			$tmp[1] = 'DESC';
		}
		if ($tmp[0] == 'count')
		{
			$this->fields[] = 'IF(p.count IS NULL, 1, 0) count_null';
			$this->order_by = 'count_null ' . $tmp[1] . ', p.count ' . $tmp[1];
		}
		else
		{
			$this->order_by = 'p.' . $sort;
		}
	}

	public function getSortBySortOption($product_sort_enum_option)
	{
		$sorts = $this->sortsBySortOptions();

		return array_key_exists($product_sort_enum_option, $sorts)
			? $sorts[$product_sort_enum_option]
			: '';
	}

	public function getPriceRange()
	{
		if ($this->is_clean)
		{
			$cached_data = $this->cache->get();
			if (!is_array($cached_data))
			{
				$cached_data = array();
			}

			if (array_key_exists('price_range', $cached_data))
			{
				return $cached_data['price_range'];
			}
		}

		$skus_table_alias = null;

		foreach ($this->joins as $join)
		{
			if ($join['table'] == 'shop_product_skus')
			{
				$skus_table_alias = $join['alias'];

				break;
			}
		}

		if ($skus_table_alias === null)
		{
			$skus_table_alias = $this->addJoin('shop_product_skus', ':table.product_id = p.id', ':table.price <> 0');
		}
		else
		{
			$this->addWhere("`{$skus_table_alias}`.price <> 0");
		}

		$alias_currency = $this->addJoin('shop_currency', ':table.code = p.currency');
		$prev_group_by = $this->group_by;
		$this->groupBy("p.id");
		$sql = $this->getSQL();
		$sql = "SELECT MIN(`{$skus_table_alias}`.price * `{$alias_currency}`.rate) min, MAX(`{$skus_table_alias}`.price * `{$alias_currency}`.rate) max " . $sql;
		$data = $this->getModel()->query($sql)->fetch();
		$this->group_by = $prev_group_by;
		array_pop($this->joins);

		$price_range = array(
			'min' => (double)(isset($data['min']) ? $data['min'] : 0),
			'max' => (double)(isset($data['max']) ? $data['max'] : 0),
		);

		if ($this->is_clean)
		{
			$cached_data['price_range'] = $price_range;
			$this->cache->set($cached_data);
		}

		return $price_range;
	}

	public function count()
	{
		if ($this->is_clean)
		{
			$cached_data = $this->cache->get();
			if (!is_array($cached_data))
			{
				$cached_data = array();
			}

			if (array_key_exists('count', $cached_data))
			{
				return $cached_data['count'];
			}
		}

		$count = parent::count();

		if ($this->is_clean)
		{
			$cached_data['count'] = $count;
			$this->cache->set($cached_data);
		}

		return $count;
	}

	protected function sortsBySortOptions()
	{
		return array(
			$this->product_sort_options->MANUAL => '',
			$this->product_sort_options->PRICE_DESC => 'price DESC',
			$this->product_sort_options->PRICE_ASC => 'price ASC',
			$this->product_sort_options->NAME => 'name ASC',
			$this->product_sort_options->RATING_DESC => 'rating DESC',
			$this->product_sort_options->RATING_ASC => 'rating ASC',
			$this->product_sort_options->TOTAL_SALES_DESC => 'total_sales DESC',
			$this->product_sort_options->TOTAL_SALES_ASC => 'total_sales ASC',
			$this->product_sort_options->COUNT => 'count DESC',
			$this->product_sort_options->CREATE_DATETIME => 'create_datetime DESC',
			$this->product_sort_options->STOCK_WORTH => 'stock_worth DESC',
		);
	}

	private function getStorefront()
	{
		return shopBrandStorefront::getCurrent();
	}

	private function getCurrency()
	{
		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();

		return $config->getCurrency(false);
	}

	public static function patchWaProductsCollection(shopProductsCollection $collection, $brand_id)
	{
		$brand_collection = new shopBrandProductsCollection('', array('brand_id' => $brand_id));

		foreach (get_object_vars($collection) as $var => $_)
		{
			if ($var === 'hash')
			{
				continue;
			}
			elseif ($var === 'count')
			{
				$collection->count = $brand_collection->count();
			}
			else
			{
				$collection->$var = $brand_collection->$var;
			}
		}

		$brand_model = new shopBrandBrandModel();
		$brand_name = $brand_model
			->select('name')
			->where('id = :id', array('id' => $brand_id))
			->fetchField();

		if (is_string($brand_name) && $brand_name !== '')
		{
			$collection->title = "Товары бренда \"{$brand_name}\"";
		}
	}
}
