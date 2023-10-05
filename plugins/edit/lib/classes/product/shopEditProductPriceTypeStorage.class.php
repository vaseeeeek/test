<?php

class shopEditProductPriceTypeStorage
{
	public function getAllPriceTypes()
	{
		$price_types = array();

		$price_types[] = array(
			'id' => 'price',
			'title' => 'Цена',
		);

		$price_types[] = array(
			'id' => 'compare_price',
			'title' => 'Зачеркнутая цена',
		);

		$price_types[] = array(
			'id' => 'purchase_price',
			'title' => 'Закупочная цена',
		);

		$price_plugin_helper = new shopEditPricePluginHelper();
		if ($price_plugin_helper->isPluginInstalled())
		{
			$info = $price_plugin_helper->getPluginInfoExtended();
			$plugin_name = $info['name'];

			foreach ($price_plugin_helper->getPrices() as $price)
			{
				$price_field = $price_plugin_helper->getPriceFieldName($price);

				$price_types[] = array(
					'id' => $price_field,
					'title' => "{$price['name']} (из плагина \"{$plugin_name}\")",
				);
			}
		}

		return $price_types;
	}

	public function getSelectedPriceTypes(shopEditPriceTypeSelection $price_field_selection)
	{
		$all_types = $this->getAllPriceTypes();

		if ($price_field_selection->mode == shopEditPriceTypeSelection::MODE_ALL)
		{
			return $all_types;
		}

		$selected_ids = array();
		foreach ($price_field_selection->selected_ids as $selected_id)
		{
			$selected_ids[$selected_id] = $selected_id;
		}

		$selected_types = array();
		foreach ($all_types as $type)
		{
			if (array_key_exists($type['id'], $selected_ids))
			{
				$selected_types[] = $type;
			}
		}

		return $selected_types;
	}
}