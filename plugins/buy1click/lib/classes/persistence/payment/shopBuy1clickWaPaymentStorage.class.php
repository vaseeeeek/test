<?php


class shopBuy1clickWaPaymentStorage implements shopBuy1clickPaymentStorage
{
	private $plugin_model;
	
	public function __construct(shopPluginModel $plugin_model)
	{
		$this->plugin_model = $plugin_model;
	}
	
	/**
	 * @return shopBuy1clickPayment[]
	 */
	public function getAll()
	{
		$plugins = $this->plugin_model->listPlugins(shopPluginModel::TYPE_PAYMENT);
		$payments = array();

		foreach ($plugins as $id => $plugin)
		{
			$payments[$id] = $this->toPayment($plugin);
		}

		return $payments;
	}

	private function toPayment($arr_payment)
	{
		$payment = new shopBuy1clickPayment();
		$payment->setID($arr_payment['id']);
		$payment->setLogo($arr_payment['logo']);
		$payment->setName($arr_payment['name']);
		$payment->setDescription($arr_payment['description']);
		$payment->setSort($arr_payment['sort']);
		$payment->setAvailable($arr_payment['available']);
		$payment->setStatus($arr_payment['status']);

		return $payment;
	}
}