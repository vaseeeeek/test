<?php

class shopDpPointsModel extends waModel
{
	protected $table = 'shop_dp_points';
	protected $id = 'hash';

	public function insert($data, $type = 0)
	{
		$data['hash'] = md5(sprintf('%s_%s_%s_%s_%s_%s_%s', $data['shipping_id'], $data['country_code'], $data['region_code'], $data['city_name'], ifset($data, 'address', null), ifset($data, 'fixed_service', null), ifset($data, 'storefront_id', null)));
		// $data['hash'] = md5($data['shipping_id'] . '_' . $data['code']);

		$last_insert_id = parent::insert($data, $type);

		return $data['hash'];
	}

	private function fillCoords($hash, &$point)
	{
		$map_adapter = shopDpPluginHelper::getMapAdapter();
		if(!$map_adapter instanceof shopDpMap) {
			return;
		}

		$coords = $map_adapter->getCoords(array(
			'country_name' => $point['country_name'],
			'country_code' => $point['country_code'],
			'region_name' => $point['region_name'],
			'region_code' => $point['region_code'],
			'city_name' => $point['city_name'],
			'address' => $point['address']
		));

		if($coords !== null) {
			$point['coord_x'] = $coords[0];
			$point['coord_y'] = $coords[1];

			$this->updateById($hash, array(
				'coord_x' => $point['coord_x'],
				'coord_y' => $point['coord_y']
			));
		}
	}

	private function fillWorktimeString($hash, &$point)
	{
		$point['worktime_string'] = shopDpPluginHelper::worktimeString($point['worktime']);

		$this->updateById($hash, array(
			'worktime_string' => $point['worktime_string']
		));
	}

	public function getWorktimeModel()
	{
		if(!isset($this->worktime_model))
			$this->worktime_model = new shopDpPointsWorktimeModel();

		return $this->worktime_model;
	}

	public function getAllCustomPoints()
	{
		$points = $this->getCustomPoints(null, array(), null, array(
			'storefront_id',
			'shipping_id'
		), false);

		return $points;
	}

	public function getCustomPoints($shipping_id, $params = array(), $storefront_id = null, $group_by = 'storefront_id', $identify_key = 'hash')
	{
		$default_params = array(
			'custom' => 1
		);

		$params = array_merge($default_params, $params);

		if($storefront_id !== null) {
			$params['storefront_id'] = $storefront_id;
		}

		$points = $this->getPoints($shipping_id, null, $params, $group_by, $identify_key);

		return $points;
	}

	public function getPoints($shipping_id, $service, $params = array(), $group_by = null, $identify_key = 'hash')
	{
		$key = null;

		if ($shipping_id !== null) {
			$params['shipping_id'] = $shipping_id;
		}

		if ($service !== null) {
			$params['service'] = $service;
		}

		if (!array_key_exists('custom', $params)) {
			$params['custom'] = 0;
		}

		$query_builder = $this->select('*');

		$index = 1;
		foreach ($params as $name => $value) {
			$f = $this->escapeField($name);

			if ($name === 'city_name') {
				$e = $this->escape($value, 'like');
				$query_builder->where("{$f} LIKE '%{$e}%'");
			} else {
				$p = "p{$index}";

				if (is_array($value) && count($value) > 0) {
					$query_builder->where("{$f} IN (:{$p})", [$p => $value]);
				} elseif (!is_array($value)) {
					$query_builder->where("{$f} = :{$p}", [$p => $value]);
				}
			}

			$index++;
		}

        $group_by_key = $identify_key !== false ? $identify_key : null;
        $initial_points = $query_builder->fetchAll($group_by_key);
		$points = array();

		if(is_array($group_by)) {
			$_group_by = $group_by;
			list($group_by, $key) = $_group_by;
		}

		foreach($initial_points as $point_key => &$point) {
			$hash = $point['hash'];

			if(empty($point['coord_x']) || empty($point['coord_y'])) {
				$this->fillCoords($hash, $point);
			}

			$point['worktime'] = $this->getWorktimeModel()->getByPoint($hash);
			if(empty($point['worktime_string'])) {
				$this->fillWorktimeString($hash, $point);
			}
			$point['worktime_html'] = str_replace("\n", "<br/>", $point['worktime_string']);

			if($group_by !== null && array_key_exists($group_by, $point)) {
				$point_group_key = $point[$group_by];

				$point_group_key_2 = null;
				if($key !== null && array_key_exists($key, $point)) {
					$point_group_key_2 = $point[$key];
				}

				if($point_group_key_2 !== null) {
					$push_to = &$points[$point_group_key][$point_group_key_2];
				} else {
					$push_to = &$points[$point_group_key];
				}

				if($identify_key !== false && array_key_exists($identify_key, $point)) {
					$identify_value = $point[$identify_key];

					$push_to[$identify_value] = $point;
				} else {
					$push_to[] = $point;
				}
			}
		}

		return $group_by !== null ? $points : $initial_points;
	}

	public function deleteCustomPoints($shipping_id, $storefront_id = null)
	{
		$params = array(
			'custom' => 1
		);

		if($storefront_id !== null) {
			$params['storefront_id'] = $storefront_id;
		}

		$this->deletePoints($shipping_id, null, null, $params);
	}

	public function deletePoints($shipping_id, $service, $hash = null, $params = array())
	{
		if($shipping_id !== null) {
			$params['shipping_id'] = $shipping_id;
		}

		if($service !== null) {
			$params['service'] = $service;
		}

		if(!array_key_exists('custom', $params)) {
			$params['custom'] = 0;
		}

		if($hash !== null) {
			$params['search_hash'] = $hash;
		}

		$points = $this->getByField($params, true);

		foreach($points as $point) {
			$this->getWorktimeModel()->deleteByPoint($point['hash']);
			$this->deleteByField('hash', $point['hash']);
		}
	}

	public function savePoint($point, $params = array(), $aliases = array())
	{
		foreach($aliases as $from => $to) {
			if(array_key_exists($from, $point)) {
				$point[$to] = $point[$from];
				unset($point[$from]);
			}
		}

		$point = array_merge($point, $params);

		if(!array_key_exists('code', $point)) {
			$point['code'] = uniqid();
		}

		$hash = $this->insert($point, 1);

		if(!empty($point['worktime'])) {
			foreach($point['worktime'] as $day => $data) {
				if(is_string($data)) {
					$_data = $data;

					$data = array(
						'day' => $day,
						'period' => $_data
					);
				}

				$data['point_hash'] = $hash;
				$this->getWorktimeModel()->insert($data, 1);
			}
		}

		return $hash;
	}

	public function savePoints($points, $params = array(), $aliases = array())
	{
		foreach($points as $key => $point) {
			if(!array_key_exists('code', $point)) {
				$point['code'] = $key;
			}

			$this->savePoint($point, $params, $aliases);
		}
	}
}
