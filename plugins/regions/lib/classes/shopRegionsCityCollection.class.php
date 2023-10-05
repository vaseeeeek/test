<?php

class shopRegionsCityCollection
{
	private static $models = array();

	private $select = 't.*';
	private $where = array();
	private $joins = array();

	private $sql_params = array();

	private $sort = null;
	private $order;

	private $limit = null;
	private $offset;

	private $cache = false;
	private $is_result_cached = false;
	private $join_index = array();

	public function enabledOnly()
	{
		return $this->where('t.is_enable = 1');
	}

	public function popularOnly()
	{
		return $this->where('t.is_popular = 1');
	}

	/**
	 * @return shopRegionsCityCollection
	 */
	public function withStorefrontOnly()
	{
		return $this
			->where('t.storefront <> \'\'')
			->where('t.storefront IS NOT NULL');
	}

	/**
	 * @param array $filter
	 * @return shopRegionsCityCollection
	 */
	public function filter($filter)
	{
		$model = $this->model();
		foreach ($filter as $field => $value)
		{
			if (strpos($field, '.') === false)
			{
				$field = 't.' . $field;
			}

			$param_name = str_replace('t.', 'city_', strtolower($field));
			$param_name = preg_replace('/[^a-z_]/', '', $param_name);

			$this->where($model->escape($field) . ' = :' . $param_name, array($param_name => $value));
		}

		return $this;
	}

	/**
	 * @param array $filter_partial
	 * @return shopRegionsCityCollection
	 */
	public function filterPartial($filter_partial)
	{
		$model = $this->model();
		foreach ($filter_partial as $field => $value)
		{
			if (strpos($field, '.') === false)
			{
				$field = 't.' . $field;
			}

			$param_name = str_replace('t.', 'city_', strtolower($field));
			$param_name = preg_replace('/[^a-z_]/', '', $param_name);

			$this->where($model->escape($field) . ' LIKE \'%l:' . $param_name . '%\'', array($param_name => $value));
		}

		return $this;
	}

	/**
	 * @param string $sort
	 * @param string $order
	 * @return shopRegionsCityCollection
	 */
	public function orderBy($sort, $order = 'ASC')
	{
		$sort = strpos($sort, '.') === false
			? 't.' . $sort
			: $sort;

		$order = trim(strtoupper($order));
		if ($order !== 'ASC' && $order !== 'DESC')
		{
			$order = 'ASC';
		}

		if ($this->sort === $sort && $this->order === $order)
		{
			return $this;
		}

		$this->sort = $sort;
		$this->order = $order;

		$this->is_result_cached = false;

		return $this;
	}


	public function count()
	{
		if ($this->is_result_cached && is_array($this->cache))
		{
			return count($this->cache);
		}

		$model = $this->model();
		$sql = 'SELECT COUNT(*)' . PHP_EOL . $this->getSql();

		return (int)$model->query($sql, $this->sql_params)->fetchField();
	}

	/**
	 * @param $table
	 * @param $on
	 * @param null $where
	 * @param null $alias
	 * @return shopRegionsCityCollection
	 */
	public function join($table, $on, $where = null, &$alias = null)
	{
		$alias = $this->_join($table, $on, $where);

		return $this;
	}

	public function leftJoin($table, $on, $where = null, &$alias = null)
	{
		$alias = $this->_join($table, $on, $where, array('type' => 'LEFT'));

		return $this;
	}

	/**
	 * shortcut for joining with wa_region and wa_country
	 *
	 * @param null|string $region_alias
	 * @param null|string $country_alias
	 * @return shopRegionsCityCollection
	 */
	public function joinRegion(&$region_alias = null, &$country_alias = null)
	{
		return $this->_joinRegion($region_alias, $country_alias);
	}

	/**
	 * shortcut for joining with wa_region and wa_country
	 *
	 * @param null|string $region_alias
	 * @param null|string $country_alias
	 * @return shopRegionsCityCollection
	 */
	public function leftJoinRegion(&$region_alias = null, &$country_alias = null)
	{
		return $this->_joinRegion($region_alias, $country_alias, array('type' => 'LEFT'));
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return shopRegionsCityCollection
	 */
	public function limit($limit, $offset = 0)
	{
		if ($this->limit === $limit && $this->offset === $offset)
		{
			return $this;
		}

		$this->limit = $limit;
		$this->offset = $offset;

		$this->is_result_cached = false;

		return $this;
	}

	/**
	 * @param string $select
	 * @return shopRegionsCityCollection
	 */
	public function select($select = 't.*')
	{
		$this->select = $select;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getCities()
	{
		if ($this->is_result_cached && $this->cache !== false)
		{
			return $this->cache;
		}

		$city_model = $this->model();
		$sql = $this->getSql();

		$sql = 'SELECT ' . $this->select . PHP_EOL . $sql;

		$this->cache = $city_model->query($sql, $this->sql_params)->fetchAll('id');
		$this->is_result_cached = true;

		$this->tryTranslateCountryName();

		return $this->cache;
	}

	/**
	 * @return array|null
	 */
	public function getFirst()
	{
		$city_model = $this->model();
		$sql = $this->getSql(array(), 1, 0);

		$sql = 'SELECT ' . $this->select . PHP_EOL . $sql;

		return $city_model->query($sql, $this->sql_params)->fetchAssoc();
	}

	/**
	 * @param string $storefront
	 * @return array|null
	 */
	public function getDefaultForStorefront($storefront)
	{
		$city_model = $this->model();
		$where = array(
			'storefront = :storefront',
			'is_default_for_storefront = 1'
		);
		$this->sql_params['storefront'] = $storefront;

		$sql = $this->getSql($where, 1, 0);

		$sql = 'SELECT t.*' . PHP_EOL . $sql;

		return $city_model->query($sql, $this->sql_params)->fetchAssoc();
	}

	/**
	 * @param string $field
	 * @return shopRegionsCityGroup[]
	 */
	public function getGroupedBy($field)
	{
		$cities = $this->getCities();

		if (!$field)
		{
			return array(new shopRegionsCityGroup('Все', array('is_only' => true), $cities));
		}

		/** @var shopRegionsCityGroup[] $groups */
		$groups = array();

		foreach ($cities as $city_id => $city)
		{
			$grouping_value = $city[$field];
			if (!isset($groups[$grouping_value]))
			{
				$group_params = array(
					'country_iso3' => $city['country_iso3'],
					'region_code' => $city['region_code'],
					'full_region_code' => $city['country_iso3'] . $city['region_code'],
					'region_name' => strlen(trim(ifset($city['region_name'], ''))) ? $city['region_name'] : ifset($city['country_name'], ''),
				);
				$groups[$grouping_value] = new shopRegionsCityGroup($grouping_value, $group_params);
			}

			$groups[$grouping_value]->addCity($city);
		}

		if (count($groups) == 1)
		{
			$group = reset($groups);
			$group->addAttribute('is_only', true);

			unset($group);
		}

		uasort($groups, array($this, '_sortGroupsByName'));

		return $groups;
	}

	/**
	 * @param shopRegionsCityGroup $g1
	 * @param shopRegionsCityGroup $g2
	 * @return int
	 */
	private function _sortGroupsByName($g1, $g2)
	{
		return strnatcasecmp($g1->getName(), $g2->getName());
	}

	public function getSql($where = array(), $limit = null, $offset = null)
	{
		$city_model = $this->model();

		$sql = 'FROM `' . $city_model->getTableName() . '` t
';
		foreach ($this->joins as $options)
		{
			$sql .= ifset($options['type'], '') . ' JOIN `' . $options['table'] . '` `' . $options['alias']
				. '` ON ' . $options['on'] . PHP_EOL;
		}

		$where = array_merge($this->where, $where);
		if (count($where))
		{
			$sql .= 'WHERE ' . implode(' AND ', $where) . PHP_EOL;
		}

		if (strlen($this->sort))
		{
			$sql .= 'ORDER BY ' . $city_model->escape($this->sort) . ' ' . $this->order . PHP_EOL;
		}

		$limit = $limit === null ? $this->limit : $limit;
		$offset = $offset === null ? $this->offset : $offset;
		if ($limit)
		{
			$sql .= 'LIMIT ' . $offset . ', ' . $limit . PHP_EOL;
		}

		return $sql;
	}

	/**
	 * @param string $where
	 * @param null|array $params
	 * @return shopRegionsCityCollection
	 */
	public function where($where, $params = null)
	{
		$this->where[] = $where;
		$this->is_result_cached = false;

		if (is_array($params))
		{
			$this->sql_params = array_merge($this->sql_params, $params);
		}

		return $this;
	}

	public function getGroupByColumnAndLetterAssoc($count_columns)
	{
		$cities_by_columns = $this->getGroupByColumnAssoc($count_columns);

		foreach ($cities_by_columns as $i => $_cities)
		{
			$cities = array();

			foreach ($_cities as $_city)
			{
				$letter = mb_substr($_city['name'], 0, 1);
				if (!isset($cities[$letter]))
				{
					$cities[$letter] = array();
				}

				$cities[$letter][] = $_city;
			}

			$cities_by_columns[$i] = $cities;
		}

		return $cities_by_columns;
	}



	public function getGroupByColumnAssoc($count_columns)
	{
		$cities_rows = $this->getCities();
		$count_regions = count($cities_rows);
		$count_in_column = ceil($count_regions / $count_columns);
		$cities_raw = array(0 => array());
		$column_index = 0;
		foreach ($cities_rows as $_city)
		{
			if ($count_in_column == 0)
			{
				$column_index++;
				$cities_raw[$column_index] = array();
				$count_columns--;
				$count_in_column = ceil($count_regions / $count_columns);
			}

			$cities_raw[$column_index][] = $_city;
			$count_regions--;
			$count_in_column--;
		}

		return $cities_raw;
	}

	/**
	 * @param $name
	 * @return waModel
	 */
	protected function model($name = null)
	{
		if (!array_key_exists($name, self::$models))
		{
			switch ($name)
			{
				case 'wa_region':
					$model = new waRegionModel();
					break;
				case 'wa_country':
					$model = waCountryModel::getInstance();
					break;
				case 'site_domain':
					wa('site');
					$model = new siteDomainModel();
					break;
				case 'regions_city':
				default:
					$model = new shopRegionsCityModel();
			}

			self::$models[$name] = $model;
		}

		return self::$models[$name];
	}

	protected function tryTranslateCountryName()
	{
		if (!is_array($this->cache) || wa()->getLocale() == 'en_US')
		{
			return;
		}

		$first = reset($this->cache);
		if (!is_array($first) || !array_key_exists('country_name', $first))
		{
			return;
		}

		foreach ($this->cache as $city_id => $city_row)
		{
			$this->cache[$city_id]['country_name'] = _ws($city_row['country_name']);
		}
	}

	/**
	 * @param $table
	 * @return string
	 */
	private function getJoinAlias($table)
	{
		$t = explode('_', $table);
		$alias = '';
		foreach ($t as $tp)
		{
			if ($tp == 'shop')
			{
				continue;
			}
			$alias .= substr($tp, 0, 1);
		}

		if (!$alias)
		{
			$alias = $table;
		}

		if ($alias === 't')
		{
			$alias = 'tt';
		}

		if (!isset($this->join_index[$alias]))
		{
			$this->join_index[$alias] = 1;
		}
		else
		{
			$this->join_index[$alias]++;
		}
		$alias .= $this->join_index[$alias];

		return $alias;
	}

	private function _join($table, $on, $where, $params = array())
	{
		$alias = $this->getJoinAlias($table);

		$join = array(
			'table' => $table,
			'alias' => $alias,
			'on' => str_replace(':table', $alias, $on),
		);

		$join = array_merge($join, $params);

		$this->joins[] = $join;

		if ($where !== null)
		{
			$this->where(str_replace(':table', $alias, $where));
		}

		return $alias;
	}

	private function _joinRegion(&$region_alias = null, &$country_alias = null, $join_params = array())
	{
		$region_model = $this->model('wa_region');
		$country_model = $this->model('wa_country');

		$region_alias = $this->_join($region_model->getTableName(), ':table.country_iso3 = t.country_iso3 AND :table.code = t.region_code', null, $join_params);
		$country_alias = $this->_join($country_model->getTableName(), ':table.iso3letter = t.country_iso3', null, $join_params);

		$this->select .= ', ' . $region_alias . '.name region_name, ' . $country_alias . '.name country_name';

		return $this;
	}
}