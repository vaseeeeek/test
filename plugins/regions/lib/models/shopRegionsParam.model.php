<?php


class shopRegionsParamModel extends waModel
{
	protected $table = 'shop_regions_param';

	public function deleteAll()
	{
		return $this->query('DELETE FROM `'.$this->table.'`');
	}
}