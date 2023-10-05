<?php

class shopBrandBrandsCollection implements shopBrandIBrandsCollection
{
	//private $settings;

	private $brand_feature;
	private $type_model;
	private $feature_model;

	private $brand_storage;
	private $brand_field_storage;

	private $sort;
	private $order;
	private $with_products_only;
	private $with_images_only;
	private $storefront;
    /**
     * @var shopBrandSettings|null
     */
    private $settings;

    /**
	 * shopBrandBrandsCollection constructor.
	 * @param null $storefront
	 * @throws waException
	 */
	public function __construct($storefront)
	{
		$settings_storage = new shopBrandSettingsStorage();
		$this->settings = $settings_storage->getSettings();

		$this->feature_model = new shopFeatureModel();
		$this->brand_feature = shopBrandHelper::getBrandFeature();
		$this->type_model = shopFeatureModel::getValuesModel($this->brand_feature['type']);
		$this->brand_storage = new shopBrandBrandStorage();
		$this->brand_field_storage = new shopBrandBrandFieldStorage();

		$this->storefront = $storefront;
		$this->with_products_only = false;
		$this->with_images_only = false;
	}

	/**
	 * @return shopBrandBrand[]
	 */
    public function getBrands()
    {
        $key_parts = array(
            md5($this->storefront),
            $this->with_images_only ? '1' : '0',
            $this->with_products_only ? '1' : '0',
        );
        $cache_key = implode('_', $key_parts);
        $cache = wa('shop')->getCache();
        $all_brands = null;
        if ($cache) {
            $all_brands = $cache->get($cache_key, 'all_brands');
        }
        if ($all_brands === null) {
            $all_brands = $this->getAllBrands();
            $this->sortBrands($all_brands);
            if ($cache) {
                $cache->set($cache_key, $all_brands, $this->settings->cache_lifetime, 'all_brands');
            }
        }
        return $all_brands;
    }

	/**
	 * @param bool $with_images_only
	 * @return shopBrandBrandsCollection
	 */
	public function withImagesOnly($with_images_only = true)
	{
		$this->with_images_only = !!$with_images_only;

		return $this;
	}

	/**
	 * @param string|array $sort
	 * @param string $order
	 * @return shopBrandBrandsCollection
	 */
	public function sort($sort, $order = 'ASC')
	{
		$this->sort = is_array($sort) ? $sort : array($sort);
		$this->order = strtoupper($order) == 'ASC' ? 'ASC' : 'DESC';

		return $this;
	}

	/**
	 * @param bool $with_products_only
	 * @return shopBrandBrandsCollection
	 */
	public function withProductsOnly($with_products_only = true)
	{
		$this->with_products_only = !!$with_products_only;

		return $this;
	}

	public function sortBrands(&$all_brands)
	{
		usort($all_brands, array($this, 'compareBrandsBySort'));
	}

	/**
	 * @return array|null
	 */
	public function getBrandValueIds()
	{
		$cache = new waSerializeCache('plugins/brand/brand_feature_ids_' . md5($this->storefront), 300, 'shop');

		if ($cache->isCached())
		{
			return $cache->get();
		}

		$value_ids = array();
		$brand_feature_id = $this->brand_feature['id'];


        $collection = new shopProductsCollection('all');
        if(wa()->getEnv() === 'cli') {
            $options = [
                'frontend' => true,
                'storefront_context' => $this->storefront,
            ];
            $collection->setOptions($options);
        }
        $feature_value_ids = $collection->getFeatureValueIds(false);


		if (!array_key_exists($brand_feature_id, $feature_value_ids))
		{
			return array();
		}

		$_value_ids = $feature_value_ids[$brand_feature_id];
		foreach ($_value_ids as $value_id)
		{
			$value_ids[$value_id] = $value_id;
		}

		$cache->set($value_ids);

		return $value_ids;
	}

	/**
	 * @return shopBrandBrand[]
	 */
	private function getAllBrands()
	{
		$value_ids = array();
		if ($this->with_products_only)
		{
			$value_ids = $this->getBrandValueIds();
		}

		$brands = array();
		foreach ($this->brand_storage->getAll() as $brand)
		{
			if (!$brand->is_shown)
			{
				continue;
			}

			if ($this->with_images_only && !$brand->hasImage())
			{
				continue;
			}

			if ($this->with_products_only)
			{
				if (!array_key_exists($brand->id, $value_ids))
				{
					continue;
				}

				// todo закешировать наличие товаров
				//$products_collection = new shopBrandProductsCollection('', array('brand_id' => $brand->id));
				//if ($products_collection->count() == 0)
				//{
				//	continue;
				//}
			}

			$brands[] = $brand;
		}

		return $brands;
	}

	private function compareBrandsBySort(shopBrandBrand $b1, shopBrandBrand $b2)
	{
		$key_1 = $b1->sort;
		$key_2 = $b2->sort;

		if (is_array($this->sort))
		{
			$p1 = ifset($this->sort[0]);
			$p2 = ifset($this->sort[1]);

			if ($p1 == 'field' && $p2)
			{
				$key_1 = $b1->field[$p2];
				$key_2 = $b2->field[$p2];
			}
			elseif ($p1 == 'name')
			{
				$key_1 = mb_strtolower($b1[$p1]);
				$key_2 = mb_strtolower($b2[$p1]);
			}
		}

		if ($key_1 == $key_2)
		{
			return 0;
		}

		return ($key_1 < $key_2 ? -1 : 1) * ($this->order == 'ASC' ? 1 : -1);
	}

	private function addFieldValues(shopBrandBrand $brand)
	{
		$brand_fields = $this->brand_field_storage->getBrandFieldValues($brand->id);
		$brand->field = $brand_fields;
		$brand->fields = $brand_fields;
	}
}
