<?php


class shopBuy1clickWaTempCartStorage implements shopBuy1clickTempCartStorage
{
	private $model;
	
	public function __construct(shopBuy1clickTempCartModel $model)
	{
		$this->model = $model;
	}
	
	public function update(shopBuy1clickTempCart $temp_cart)
	{
		$now = new DateTime('now', new DateTimeZone('GMT+0'));
		
		$this->model->replace(array(
			'code' => $temp_cart->getCode(),
			'last_update' => $now->format('Y-m-d H:i:s'),
		));
	}
	
	public function garbageCollection()
	{
		$now = new DateTime('now', new DateTimeZone('GMT+0'));
		$now->modify('- 15 minutes');
		$rows = $this->model->getBefore($now->format('Y-m-d H:i:s'));
		
		foreach ($rows as $row)
		{
			$cart = new shopBuy1clickWaTempCart($row['code']);
			$cart->clear();
			$this->model->deleteByField('code', $row['code']);
		}
	}
}