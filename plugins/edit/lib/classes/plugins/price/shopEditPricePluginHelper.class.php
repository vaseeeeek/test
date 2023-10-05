<?php

class shopEditPricePluginHelper extends shopEditAbstractPluginHelper
{
	public function isPluginInstalled()
	{
		$info = $this->getPluginInfoRaw();

		return is_array($info) && $info !== array();
	}

	public function isPluginEnabled()
	{
		try
		{
			return $status = $this->getPluginInstance()->getSettings('status') == '1';
		}
		catch (waException $e)
		{
			return false;
		}
	}

	public function getPluginId()
	{
		return 'price';
	}

	public function getPrices()
	{
		if (!$this->isPluginInstalled())
		{
			return array();
		}

		$product_skus_model = new shopProductSkusModel();

		$product_skus_table_meta = $product_skus_model->getMetadata();

		$price_model = new shopPricePluginModel();
		$prices_query = $price_model
			->select('*')
			->order('sort ASC')
			->query();

		$prices = array();
		foreach ($prices_query as $price)
		{
			$field_name = $this->getPriceFieldName($price);
			if (array_key_exists($field_name, $product_skus_table_meta))
			{
				$prices[] = $price;
			}
		}

		return $prices;
	}

	public function getPriceFieldName($price)
	{
		return "price_plugin_{$price['id']}";
	}
}