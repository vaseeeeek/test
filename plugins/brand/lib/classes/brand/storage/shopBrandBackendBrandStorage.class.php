<?php

class shopBrandBackendBrandStorage extends shopBrandBrandStorage
{
	/**
	 * @param shopBrandBrandListFilter $filter
	 * @param string $query
	 * @return shopBrandBrand[]
	 */
	public function getAllFiltered(shopBrandBrandListFilter $filter, $query='')
	{
	    $query = trim($query);
		$all_brands = array();

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

		$sort_statement = 'brand.sort ASC';

		$where = array(
			'val.feature_id = :feature_id',
		);
		$joins = array(
			array(
				'type' => 'LEFT',
				'table' => 'shop_brand_brand',
				'alias' => 'brand',
				'on' => 'brand.id = val.id',
			)
		);

		$this->filter($filter, $where, $joins);

		$where_statement = implode(' AND ', $where);
		if($query != '') {
		    $where_statement .= " AND brand.name LIKE '%$query%' ";
        }
		$join_statement = '';
		foreach ($joins as $join)
		{
			$join_statement .= PHP_EOL . "\t" . ifset($join['type'], '') . " JOIN {$join['table']} AS {$join['alias']}";
			$join_statement .= PHP_EOL . "\t\tON {$join['on']}";
		}

		$sql = "
SELECT DISTINCT
	val.id AS `id`,
	val.sort AS `sort`,
	val.value AS `value`,
	brand.id AS `brand_id`
FROM `{$type_table}` AS val {$join_statement}
WHERE {$where_statement}
ORDER BY {$sort_statement}, brand.id IS NULL
";

		$params = array(
			'feature_id' => $brand_feature['id'],
			'MAIN_PAGE_ID' => shopBrandPageStorage::MAIN_PAGE_ID,
		);

		$loaded_brand_ids = array();

		foreach ($this->feature_model->query($sql, $params) as $row)
		{
			$brand = $row['brand_id']
				? $this->getById($row['brand_id'])
				: $this->createObjectFromFeatureValue($row);

			if ($brand)
			{
				$all_brands[] = $brand;
				$loaded_brand_ids[$brand->id] = $brand->id;
			}
		}

		return $all_brands;
	}

	/**
	 * @return shopBrandBrand[]
	 */
	public function getAllDeleted()
	{
		$model = new waModel();
		$brand_feature = null;
		$value_model = null;

		try
		{
			$brand_feature = shopBrandHelper::getBrandFeature();
			$value_model = shopFeatureModel::getValuesModel($brand_feature['type']);
		}
		catch (waException $e)
		{
		}

		if ($brand_feature)
		{
			$value_table = $value_model->getTableName();

			$sql = "
SELECT brand.id
FROM shop_brand_brand AS brand
	LEFT JOIN {$value_table} AS val
		ON val.id = brand.id AND val.feature_id = :feature_id
WHERE val.id IS NULL
ORDER BY brand.sort
";
			$params = [
			    'feature_id' => $brand_feature['id'],
            ];
		}
		else
		{
			$sql = '
SELECT id
FROM shop_brand_brand
ORDER BY `sort`
';
			$params = [];
		}

		$brands = array();

		foreach ($model->query($sql, $params) as $row)
		{
			$brand = $this->getById($row['id']);
			if ($brand)
			{
				$brands[] = $brand;
			}
		}

		return $brands;
	}

	public function deleteById($brand_id)
	{
		return $this->model->deleteByField('id', $brand_id);
	}

	private function filter(shopBrandBrandListFilter $filter, &$where, &$join)
	{
		if (is_bool($filter->is_shown))
		{
			$where[] = ($filter->is_shown ? '' : '!') . '(is_shown = \'1\')';
		}

		if (is_bool($filter->has_image))
		{
			$where[] = ($filter->has_image ? '!' : '') . '(brand.image = \'\' OR brand.image IS NULL)';
		}

		if (is_bool($filter->has_sort))
		{
			$where[] = $filter->has_sort
				? 'brand.enable_client_sorting = 1'
				: 'brand.enable_client_sorting = 0';
		}

		if (is_bool($filter->has_filters))
		{
			$where[] = $filter->has_filters
				? 'brand.filter != \'[]\''
				: 'brand.filter = \'[]\'';
		}

		if (is_bool($filter->has_description))
		{
			$join['bp'] = array(
				'type' => 'LEFT',
				'table' => 'shop_brand_brand_page',
				'alias' => 'bp',
				'on' => 'brand.id = bp.brand_id AND bp.id = :MAIN_PAGE_ID',
			);

			$where[] = ($filter->has_description ? '!' : '') . '(bp.description = \'\' OR bp.id IS NULL)';
		}

		if (is_bool($filter->has_additional_description))
		{
			$join['bp'] = array(
				'type' => 'LEFT',
				'table' => 'shop_brand_brand_page',
				'alias' => 'bp',
				'on' => 'brand.id = bp.brand_id AND bp.id = :MAIN_PAGE_ID',
			);

			$where[] = ($filter->has_additional_description ? '!' : '') . '(bp.additional_description = \'\' OR bp.id IS NULL)';
		}
	}
}
