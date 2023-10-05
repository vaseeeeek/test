<?php


class shopBuy1clickWaShippingStorage implements shopBuy1clickShippingStorage
{
	private $plugin_model;
	
	public function __construct(shopPluginModel $plugin_model)
	{
		$this->plugin_model = $plugin_model;
	}
	
	/**
	 * @return shopBuy1clickShipping[]
	 */
	public function getAll()
	{
		$entries = $this->plugin_model->listPlugins('shipping');
		$shipping = array();

		foreach ($entries as $i => $entry)
		{
			$shipping[$i] = $this->toShipping($entry);
		}

		return $shipping;
	}

	/**
	 * @param $shipping_id
	 * @return shopBuy1clickShipping
	 */
	public function getByID($shipping_id)
	{
		$entry = $this->plugin_model->getPlugin($shipping_id,shopPluginModel::TYPE_SHIPPING);

		if (!$entry)
		{
			return null;
		}

		return $this->toShipping($entry);
	}
	
	private function toShipping($arr_shipping)
	{
		$shipping = new shopBuy1clickShipping();
		$shipping->setID($arr_shipping['id']);
		$shipping->setName($arr_shipping['name']);
		$shipping->setDescription($arr_shipping['description']);
		$shipping->setLogo($arr_shipping['logo']);
		$shipping->setStatus($arr_shipping['status']);
		$shipping->setSort($arr_shipping['sort']);
		$shipping->setAvailable(isset($arr_shipping['available']) ? $arr_shipping['available'] : false);

		return $shipping;
	}
}