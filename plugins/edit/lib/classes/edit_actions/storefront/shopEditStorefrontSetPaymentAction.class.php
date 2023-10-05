<?php

class shopEditStorefrontSetPaymentAction extends shopEditLoggedAction
{
	private $storefront_selection;
	private $payment_selection;

	public function __construct(shopEditStorefrontSelection $storefront_selection, shopEditPaymentSelection $payment_selection)
	{
		parent::__construct();

		$this->storefront_selection = $storefront_selection;
		$this->payment_selection = $payment_selection;
	}

	protected function execute()
	{
		$storage = new shopEditStorefrontStorage();

		$selected_payment_for_route = $this->payment_selection->getPaymentForRoute();

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
			if ($this->areEquals($storefront->payment_id, $selected_payment_for_route))
			{
				continue;
			}

			$storefront->payment_id = $selected_payment_for_route;

			$affected_storefronts[$storefront->name] = $storefront->name;
		}
		unset($storefront);

		$storage->updateShopStorefronts($storefronts);

		return array(
			'storefront_selection' => $this->storefront_selection,
			'payment_selection' => $this->payment_selection,
			'affected_storefronts' => array_values($affected_storefronts),
			'affected_storefronts_count' => count($affected_storefronts),
		);
	}

	protected function getAction()
	{
		return $this->action_options->STOREFRONT_SET_PAYMENT;
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