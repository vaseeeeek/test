<?php

class shopDpPointsWorktimeModel extends waModel
{
	protected $table = 'shop_dp_points_worktime';

	public function getByPoint($point_hash)
	{
		return $this->getByField('point_hash', $point_hash, 'day');
	}

	public function deleteByPoint($point_hash)
	{
		return $this->deleteByField('point_hash', $point_hash);
	}
}