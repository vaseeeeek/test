<?php

class shopArrivedPluginReport {

	public $model;
	public $product_model;
	public $where;
	private $sku_model;

	public function __construct()
	{
		$this->model = new shopArrivedModel();
		$this->product_model = new shopProductModel();
        $this->sku_model = new shopProductSkusModel();
		$this->where = $this->getQuery();
		$this->model->deleteAllExpiried();
		if(waRequest::get('remove', 0, waRequest::TYPE_INT)>0)
			$this->model->removeById(waRequest::get('remove', 0, waRequest::TYPE_INT));
	}

    public function getItems($limit="")
	{
		$this->model = new shopArrivedModel();
		if(waRequest::get('rating'))
			$items = $this->model->getStats($this->where,$limit);
		else
			$items = $this->model->getAllRequests($this->where,$limit);

		for($i=0;$i<count($items);++$i) {
			$items[$i]['product'] = $this->product_model->getById($items[$i]['product_id']);
			$items[$i]['product']['sku'] = $this->sku_model->getSku($items[$i]['sku_id']);
		}
		return $items;
	}

    public function getHashParams()
	{
		$hash = waRequest::get('hash', "", "string");
		if(empty($hash))
			return false;
		$hash = explode("/",$hash);
		foreach($hash as $hashrow)
		{
			$hashrow = explode("=",$hashrow);
			$row[$hashrow[0]] = $hashrow[1];
		}
		return $row;
	}

    private function getQuery()
    {
        $hash = $this->getHashParams();
		$days = waRequest::get('timeframe');

        if(($days == 'custom') && waRequest::get('from') && waRequest::get('to')) {
            $from = date('Y-m-d 00:00:00', waRequest::get('from'));
            $to = date('Y-m-d 23:59:59', waRequest::get('to'));
            $where = 'created BETWEEN \''.$from. '\' AND \''.$to."'";
        } elseif((int)$days) {
            $where = 'created > (NOW() - interval '.((int)$days).' day)';
        } else {
            $where = '1';
        }
		if((int)$hash['pid']>0) {
            $where .= ' AND product_id = '.(int)$hash["pid"];
		}
		if(waRequest::get('sended') && !waRequest::get('rating')) {
            $where .= ' AND sended = 1';
        } else if(!waRequest::get('rating')) {
            $where .= ' AND sended = 0';
        }
		if(!waRequest::get('rating'))
			$where .= ' AND expired = 0';	

        return $where;
    }
}