<?php

class shopDpShippingModel extends waModel
{
	protected $table = 'shop_dp_shipping';

	public function getPointsModel()
	{
		if(!isset($this->points_model))
			$this->points_model = new shopDpPointsModel();

		return $this->points_model;
	}

	public function getShippings()
	{
		return $this->getAll('id');
	}

	public function getShipping($id, $service, $hash = null)
	{
		$params = array(
			'id' => $id,
			'service' => $service
		);

		if($hash !== null)
			$params['hash'] = $hash;

		return $this->getByField($params);
	}

	public function updateShipping($id, $service, $hash = null)
	{
		$value = array(
			'id' => $id,
			'service' => $service,
			'update_datetime' => date('Y-m-d H:i:s')
		);

		if($hash !== null)
			$value['hash'] = $hash;

		return $this->insert($value, 1);
	}

	public function findPoints($id, $actuality, $params)
	{
		$shipping = $this->getShipping($id);

		if($shipping) {
			if($actuality === 'unlimited') {
				return $this->getPointsModel()->getPoints($id, $params);
			} else {
				$shipping_update_datetime = new DateTime($shipping['update_datetime']);

				if($shipping_update_datetime->getTimestamp() + $actuality > time()) {
					return $this->getPointsModel()->getPoints($id, $params);
				} else
					return false;
			}
		} else
			return false;
	}

	public function savePoints($id, $points = array())
	{
		$this->getPointsModel()->deletePoints($id);

		$this->updateShipping($id);

		foreach($points as &$point) {
			$point['shipping_id'] = $id;
			$this->getPointsModel()->savePoint($point);
		}
	}
}