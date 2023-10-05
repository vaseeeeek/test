<?php


class shopBuy1clickTempCartModel extends waModel
{
	protected $table = 'shop_buy1click_temp_cart';
	
	public function getBefore($datetime)
	{
		return $this->query("select * from `{$this->table}` where `last_update` < ?", array($datetime))->fetchAll();
	}
}