<?php

// todo collection
class shopBrandBrandStorage extends shopBrandStorage
{
	protected $feature_model;
	protected $fields_storage;
	protected $sort_enum_options;
	protected $empty_page_response_mode_options;

	public function __construct()
	{
		$this->feature_model = new shopFeatureModel();
		$this->fields_storage = new shopBrandBrandFieldStorage();
		$this->sort_enum_options = new shopBrandProductSortEnumOptions();
        $this->empty_page_response_mode_options = new shopBrandEmptyPageResponseModeEnumOptions();

		parent::__construct();
	}

	/**
	 * @param $brand_id
	 * @return null|shopBrandBrand
	 */
	public function getById($brand_id)
	{
		$brand_raw = $this->model->getById($brand_id);

		if ($brand_raw)
		{
			$fields = $this->fields_storage->getBrandFieldValues($brand_id);

			return new shopBrandBrand($this->prepareStorableForAccessible($brand_raw), $fields);
		}
		else
		{
			try
			{
				$brand_feature = shopBrandHelper::getBrandFeature();
				$type_model = shopFeatureModel::getValuesModel($brand_feature['type']);
			}
			catch (waException $e)
			{
				return null;
			}

			$feature_value = $type_model->getById($brand_id);

			return $feature_value
				? $this->createObjectFromFeatureValue($feature_value)
				: null;
		}
	}

	/**
	 * @param int|array|shopProduct $product
	 * @return null|shopBrandBrand
	 * @throws waException
	 */
	public function getByProduct($product)
	{
		$brand_ids = $this->getProductBrandIds($product);

		if (count($brand_ids) === 0)
		{
			return null;
		}

		foreach ($brand_ids as $brand_id)
		{
			$brand = $this->getById($brand_id);

			if ($brand)
			{
				return $brand;
			}
		}

		return null;
	}

	/**
	 * @param int|array|shopProduct $product
	 * @return shopBrandBrand[]
	 * @throws waException
	 */
	public function getAllByProduct($product)
	{
		$brand_ids = $this->getProductBrandIds($product);

		$brands = array();
		foreach ($brand_ids as $brand_id)
		{
			$brand = $this->getById($brand_id);

			if ($brand)
			{
				$brands[] = $brand;
			}
		}

		return $brands;
	}

	/**
	 * @param $brand_url
	 * @return null|shopBrandBrand
	 */
	public function getByUrl($brand_url)
	{
		$id = $this->model
			->select('id')
			->where('url = :url', array('url' => $brand_url))
			->fetchField();

		if ($id)
		{
			return $this->getById($id);
		}

		try
		{
			$brand_feature = shopBrandHelper::getBrandFeature();
			$type_model = shopFeatureModel::getValuesModel($brand_feature['type']);
		}
		catch (waException $e)
		{
			return null;
		}

		$value_table = $type_model->getTableName();

		$sql = "
SELECT *
FROM {$value_table} val
WHERE val.feature_id = :feature_id AND LOWER(val.`value`) = :brand_url
";

		$params = array(
			'feature_id' => $brand_feature['id'],
			'brand_url' => strtolower($brand_url),
		);
		$feature_value = $type_model->query($sql, $params)->fetchAssoc();

		return $feature_value
			? $this->getById($feature_value['id'])
			: null;
	}

	/**
	 * @param array $brand_assoc
	 * @return int
	 * @throws waException
	 */
	public function store($brand_assoc)
	{
		try
		{
			$brand_feature = shopBrandHelper::getBrandFeature();
			$type_model = shopFeatureModel::getValuesModel($brand_feature['type']);
		}
		catch (waException $e)
		{
			return null;
		}

		if (!array_key_exists('id', $brand_assoc) || !wa_is_int($brand_assoc['id']) || $brand_assoc['id'] < 1)
		{
			$brand_assoc['id'] = null;
			$brand_id = null;
		}
		else
		{
			$brand_id = $brand_assoc['id'];
		}

		$brand_to_insert = $this->prepareAccessibleToStorable($brand_assoc);
		$brand_to_insert['url'] = $this->getUniqueUrl($brand_to_insert['url'], $brand_id);
		$brand_to_insert['name'] = trim($brand_to_insert['name']);


		if (!isset($brand_to_insert['sort']) || !$brand_to_insert['sort'])
		{
			$max_sort = $this->model->select('MAX(sort)')->fetchField();
			$max_sort = $max_sort ? $max_sort : 0;
			$brand_to_insert['sort'] = $max_sort + 1;
		}

		if (!$brand_id)
		{
			$value_row = $type_model->getByField('value', $brand_to_insert['name']);

			if ($value_row)
			{
				$brand_id = $value_row['id'];
			}
			else
			{
				$row = $type_model->addValue(
					$brand_feature['id'],
					$brand_assoc['name']
				);

				$brand_id = $row['id'];
			}

			$brand_to_insert['id'] = $brand_id;
		}

		$insert_result = $this->model->insert($brand_to_insert, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);

		return $brand_to_insert['id'] > 0
			? $brand_to_insert['id']
			: $insert_result;
	}

	/**
	 * @return shopBrandBrand[]
	 */
	public function getAll()
	{
		try
		{
			$brand_feature = shopBrandHelper::getBrandFeature();
			$type_model = shopFeatureModel::getValuesModel($brand_feature['type']);
		}
		catch (waException $e)
		{
			return array();
		}

		$type_table = $type_model->getTableName();

		$sql = "
SELECT
	val.id AS `id`,
	val.sort AS `sort`,
	val.value AS `value`,
	brand.id AS `brand_id`
FROM `{$type_table}` val
	LEFT JOIN shop_brand_brand brand
		ON brand.id = val.id
WHERE val.feature_id = :feature_id
";

		$params = array(
			'feature_id' => $brand_feature['id'],
		);

		$brand_ids = array();

		foreach ($this->feature_model->query($sql, $params) as $row)
		{
			$brand_ids[] = $row['brand_id']
				? $row['brand_id']
				: $this->storeBrandByFeatureValue($row);
		}

		if (count($brand_ids) === 0)
		{
			return array();
		}

		$brand_ids = array_unique($brand_ids);

		$brands_sql = "
SELECT *
FROM `{$this->model->getTableName()}`
WHERE id IN (:brand_ids)
";

		$params_sql = "
SELECT *
FROM shop_brand_brand_field_value
WHERE brand_id IN (:brand_ids)
";

		$query_params = array(
			'brand_ids' => $brand_ids
		);

		$all_brands_assoc = array();
		foreach ($this->model->query($brands_sql, $query_params) as $brand_row)
		{
			$all_brands_assoc[$brand_row['id']] = $brand_row;
		}

		$brands_fields = array();
		foreach ($this->model->query($params_sql, $query_params) as $value_row)
		{
			$brand_id = $value_row['brand_id'];
			if (!array_key_exists($brand_id, $brand_feature))
			{
				$brands_fields[$brand_id] = array();
			}

			$brands_fields[$brand_id][$value_row['field_id']] = $value_row['value'];
		}

		$all_brands = array();
		foreach ($all_brands_assoc as $brand_assoc)
		{
			$all_brands[] = new shopBrandBrand(
				$brand_assoc,
				ifset($brands_fields, $brand_assoc['id'], null)
			);
		}

		return $all_brands;
	}

	/**
	 * @return shopBrandBrand[]
	 */
	public function getAllWithProducts()
	{
		$all_brands = array();

		try
		{
			$brand_feature = shopBrandHelper::getBrandFeature();
			$brand_feature_id = $brand_feature['id'];
			$type_model = shopFeatureModel::getValuesModel($brand_feature['type']);
		}
		catch (waException $e)
		{
			return array();
		}

		$type_table = $type_model->getTableName();

		$sql = "
SELECT
	val.id AS `id`,
	val.sort AS `sort`,
	val.value AS `value`,
	brand.id AS `brand_id`
FROM `{$type_table}` val
	LEFT JOIN shop_brand_brand brand
		ON brand.id = val.id
WHERE val.feature_id = :feature_id
";

		$params = array(
			'feature_id' => $brand_feature_id,
		);

		$collection = new shopProductsCollection('all');
		$feature_value_ids = $collection->getFeatureValueIds(false);

		if (!array_key_exists($brand_feature_id, $feature_value_ids))
		{
			return array();
		}
		$_value_ids = $feature_value_ids[$brand_feature_id];
		$value_ids = array();
		foreach ($_value_ids as $value_id)
		{
			$value_ids[$value_id] = $value_id;
		}

		foreach ($this->feature_model->query($sql, $params) as $row)
		{
			if (!array_key_exists($row['id'], $value_ids))
			{
				continue;
			}

			$brand = $row['brand_id']
				? $this->getById($row['brand_id'])
				: $this->createObjectFromFeatureValue($row);

			if ($brand->is_shown)
			{
				$all_brands[] = $brand;
			}
		}

		return $all_brands;
	}

	public function toggleBrandIsShown($brand_id, $toggle)
	{
		$brand = $this->getById($brand_id);
		if (!$brand)
		{
			return false;
		}

		$specification = $this->getFieldSpecification('is_shown');

		return $this->model->updateById($brand_id, array(
			'is_shown' => $specification->toStorable(!!$toggle),
		));
	}

	public function getUniqueUrl($url, $brand_id = null)
	{
		if (!is_string($url) || strlen($url) == 0)
		{
			$url = uniqid();
		}

		$base_url = $random_url = shopBrandHelper::toCanonicalUrl($url);

		$unique_url_count_sql = "
SELECT COUNT(id)
FROM {$this->model->getTableName()}
WHERE url = :url
";
		$sql_params = array(
			'url' => $random_url,
		);

		if ($brand_id > 0)
		{
			$unique_url_count_sql .= ' AND id != :id';
			$sql_params['id'] = $brand_id;
		}

		while ($this->model->query($unique_url_count_sql, $sql_params)->fetchField() > 0)
		{
			$random_url = $base_url . '_' . substr(uniqid(), -5, 5);
			$sql_params['url'] = $random_url;
		}

		return $random_url;
	}

	public function setBrandsSort($brand_ids)
	{
		$sort = 1;
		foreach ($brand_ids as $brand_id)
		{
			$this->model->updateById($brand_id, array(
				'sort' => $sort++,
			));
		}
	}

	/**
	 * @return shopBrandIDataFieldSpecification[]
	 */
	protected function accessSpecification()
	{
		$specification = new shopBrandDataFieldSpecificationFactory();

		return array(
			'id' => $specification->integer(),
			'name' => $specification->string(),
			'url' => $specification->string(),
			'image' => $specification->string(),
			'description_short' => $specification->string(),
			'product_sort' => $specification->enum($this->sort_enum_options, $this->sort_enum_options->MANUAL),
			'filter' => $specification->jsonArray($specification->string()),
			'is_shown' => $specification->boolean(true),
			'enable_client_sorting' => $specification->boolean(true),
			'empty_page_response_mode' => $specification->enum($this->empty_page_response_mode_options, $this->empty_page_response_mode_options->DEFAULT),
			'sort' => $specification->integer(),
		);
	}

	/**
	 * @return waModel
	 */
	protected function dataModel()
	{
		return new shopBrandBrandModel();
	}

	protected function createObjectFromFeatureValue($feature_value_row)
	{
		$attributes = $this->prepareStorableForAccessible(array(
			'id' => $feature_value_row['id'],
			'name' => $feature_value_row['value'],
			'url' => shopBrandHelper::toCanonicalUrl($feature_value_row['value']),
		));

		$brand_id = $this->store($attributes);

		return $brand_id > 0
			? $this->getById($feature_value_row['id'])
			: new shopBrandBrand($attributes);
	}

	/**
	 * @param $feature_value_row
	 * @return int brand id
	 * @throws waException
	 */
	protected function storeBrandByFeatureValue($feature_value_row)
	{
		$attributes = $this->prepareStorableForAccessible(array(
			'id' => $feature_value_row['id'],
			'name' => $feature_value_row['value'],
			'url' => shopBrandHelper::toCanonicalUrl($feature_value_row['value']),
		));

		return $this->store($attributes);
	}

	/**
	 * @param int|array|shopProduct $product
	 * @return int[]
	 * @throws waException
	 */
	protected function getProductBrandIds($product)
	{
		$product_id = null;

		if (wa_is_int($product))
		{
			$product_id = $product;
		}
		elseif (is_array($product) || ($product instanceof shopProduct))
		{
			$product_id = $product['id'];
		}
		else
		{
			return array();
		}

		$feature = shopBrandHelper::getBrandFeature();
		$product_features_model = new shopProductFeaturesModel();
		$rows = $product_features_model
			->select('feature_value_id')
			->where('product_id = :product_id', array('product_id' => $product_id))
			->where('feature_id = :feature_id', array('feature_id' => $feature['id']))
			->fetchAll();

		$ids = array();
		foreach ($rows as $row)
		{
			$ids[$row['feature_value_id']] = $row['feature_value_id'];
		}

		return array_values($ids);
	}
}
