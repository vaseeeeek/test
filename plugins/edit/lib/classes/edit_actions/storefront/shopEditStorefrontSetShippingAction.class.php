<?php

class shopEditStorefrontSetShippingAction extends shopEditLoggedAction
{
	private $storefront_selection;
	private $shipping_selection;

	public function __construct(shopEditStorefrontSelection $storefront_selection, shopEditShippingSelection $shipping_selection)
	{
		parent::__construct();

		$this->storefront_selection = $storefront_selection;
		$this->shipping_selection = $shipping_selection;
	}

	protected function execute()
	{
		$storage = new shopEditStorefrontStorage();

		$selected_shipping_for_route = $this->shipping_selection->getShippingForRoute();

		/** @var shopEditStorefront[] $storefronts */
		$storefronts = array();
		if ($this->storefront_selection->mode == shopEditStorefrontSelection::MODE_ALL)
		{
			$storefronts = $storage->getAllShopStorefronts();
		}
		elseif ($this->storefront_selection->mode == shopEditStorefrontSelection::MODE_SELECTED)
		{
			$storefronts = $storage->getShopStorefronts($this->storefront_selection->storefronts);
		}

		$affected_storefronts = array();
		foreach ($storefronts as $storefront)
		{
			if ($this->areEquals($storefront->shipping_id, $selected_shipping_for_route))
			{
				continue;
			}

			$storefront->shipping_id = $selected_shipping_for_route;

			$affected_storefronts[$storefront->name] = $storefront->name;
		}
		unset($storefront);

		$storage->updateShopStorefronts($storefronts);

		return array(
			'storefront_selection' => $this->storefront_selection,
			'shipping_selection' => $this->shipping_selection,
			'affected_storefronts' => array_values($affected_storefronts),
			'affected_storefronts_count' => count($affected_storefronts),
		);
	}

	protected function getAction()
	{
		return $this->action_options->STOREFRONT_SET_SHIPPING;
	}

	private function areEquals($arr1, $arr2)
	{
		if ($arr1 === $arr2)
		{
			return true;
		}

		if (!is_array($arr1) || !is_array($arr2))
		{
			return false;
		}

		if (count($arr1) != count($arr2))
		{
			return false;
		}

		return count(array_diff($arr1, $arr2)) == 0;
	}
}