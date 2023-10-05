<?php

class shopComplexPluginCart extends shopCart
{
	public function __construct($code = '')
	{
		parent::__construct();
		$this->model = new shopComplexPluginCartItemsModel();
	}
	
   public function total($discount = true)
	{
		if(!$discount) {
			return (float) $this->model->total($this->code);
		}

		$total = $this->model->total($this->code);
		if($total > 0) {
			$order = array(
				'currency' => wa('shop')->getConfig()->getCurrency(false),
				'total' => $total,
				'items' => $this->items(false)
			);
			
			$discount = shopDiscounts::calculate($order);
			$total = $total - $discount;
		}

		return (float) $total;
	}

	public function items($hierarchy = true)
	{
		return $this->model->getByCode($this->code, true, $hierarchy);
	}
}