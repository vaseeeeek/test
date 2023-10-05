<?php


class shopRegionsCityModel extends waModel
{
	const NO_GROUPING = 'null';
	const CUSTOM_SORT_COLUMN_NAME = 'sort';

	const DEFAULT_SORT = 'name';
	const DEFAULT_ORDER = 'asc';

	const WINDOW_SORT_BY_NAME = 'name';
	const WINDOW_SORT_CUSTOM = 'custom';

	protected $table = 'shop_regions_city';

	public function enableById($ids)
	{
		return $this->updateFieldById($ids, 'is_enable', true);
	}

	public function disableById($ids)
	{
		return $this->updateFieldById($ids, 'is_enable', false);
	}

	public function makePopularById($ids)
	{
		return $this->updateFieldById($ids, 'is_popular', true);
	}

	public function makeUnpopularById($ids)
	{
		return $this->updateFieldById($ids, 'is_popular', false);
	}

	public function cloneById($ids)
	{
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		/** @var shopRegionsCity[] $cities */
		$cities = array();
		foreach ($ids as $id)
		{
			$city = shopRegionsCity::load($id);
			if (!$city)
			{
				return false;
			}

			$cities[] = $city;
		}

		$names_next_index = array();
		$sort_next = (int) $this->select('MAX(`sort`)')->fetchField() + 1;
		foreach ($cities as $city)
		{
			$clean_name = $city->getName();
			if (preg_match('/(^.+?)\s+\((\d+)\)$/', $clean_name, $matches))
			{
				$clean_name = $matches[1];
			}

			if (!isset($names_next_index[$clean_name]))
			{
				$names_next_index[$clean_name] = $this->findMaxCopyNameIndex($clean_name) + 1;
			}

			$clone_name = $clean_name . ' (' . $names_next_index[$clean_name]++ . ')';
			$row = $city->toArray(false);
			unset($row['id']);

			$city_clone = shopRegionsCity::build($row);
			$city_clone->setParams($city->getParams());

			$city_clone->setName($clone_name);
			$city_clone->setSort($sort_next++);
			$city_clone->setIsEnable(false);
			$city_clone->setIsDefaultForStorefront(false);

			if (!$city_clone->save())
			{
				return false;
			}

			$city_clone->saveStorefrontSpecificSettings($city->loadStorefrontSpecificSettings());
		}

		return true;
	}

	/**
	 * @param int[] $order
	 * @param int $offset
	 * @return waDbResultUpdate
	 */
	public function updateCustomOrder($order, $offset)
	{
		$update = '
UPDATE `%1$s` `t1`, (
	SELECT `%2$s`, @rn:=@rn+1 AS `row_index`
	FROM (
		SELECT `%2$s`
		FROM `%1$s`
		ORDER BY FIND_IN_SET(`%2$s`, \'%3$s\') DESC
		LIMIT 0, %4$d
	) `t1`, (SELECT @rn:=0) `t2`
) `t2`
SET `t1`.`sort` = `t2`.`row_index` + %5$d
WHERE `t1`.`%2$s` = `t2`.`%2$s`
';

		$limit = count($order);

		return $this->query(sprintf(
			$update,
			$this->getTableName(),
			$this->getTableId(),
			implode(',', array_reverse($order)),
			$limit,
			$offset
		));
	}

	public function countDistinct($fields)
	{
		$fields_esc = $this->escapeFields($fields);
		$select = 'COUNT(DISTINCT ' . implode(', ', $fields_esc) . ')';

		return (int) $this
			->select($select)
			->fetchField();
	}

	public function getDistinct($fields)
	{
		$fields_esc = $this->escapeFields($fields);
		$select = 'DISTINCT ' . implode(', ', $fields_esc);

		return $this
			->select($select)
			->order(array_shift($fields_esc))
			->fetchAll();
	}

	private function updateFieldById($ids, $field, $value)
	{
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		$updates = array(
			$field => $value,
		);

		return $this->updateByField($this->getTableId(), $ids, $updates);
	}

	private function findMaxCopyNameIndex($clean_name)
	{
		$rows = $this
			->select($this->escapeField('name'))
			->where($this->escapeField('name') . ' REGEXP \'' . $this->escape($clean_name) . ' [(][0-9]+[)]\'')
			->fetchAll();

		$max_index = 0;
		foreach ($rows as $row)
		{
			if (preg_match('/ \((\d+)\)$/', $row['name'], $matches))
			{
				$max_index = max($max_index, (int)$matches[1]);
			}
		}

		return $max_index;
	}

	private function escapeFields($fields)
	{
		if (!is_array($fields))
		{
			$fields = array($fields);
		}

		foreach ($fields as $i => $field)
		{
			$fields[$i] = $this->escapeField($this->escape($field));
		}

		return $fields;
	}

	public static function getSortColumns()
	{
		return array(
			'name' => 'название',
			shopRegionsCityModel::CUSTOM_SORT_COLUMN_NAME => 'вручную',
			'storefront' => 'витрина',
			'region_name' => 'область',
			'country_name' => 'страна',
			'is_popular' => 'популярен',
			'is_enable' => 'виден',
			'create_datetime' => 'добавлен',
			'update_datetime' => 'изменен',
		);
	}

	public static function getSortColumnByWindowSort($window_sort)
	{
		switch ($window_sort)
		{
			case self::WINDOW_SORT_BY_NAME:
				return 'name';
			case self::WINDOW_SORT_CUSTOM:
				return 'sort';
			default:
				return 'name';
		}
	}
}