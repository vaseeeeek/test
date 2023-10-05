<?php

class shopEditStorefrontSetDropOutOfStockAction extends shopEditLoggedAction
{
	private $storefront_selection;
	private $drop_out_of_stock;

	public function __construct(shopEditStorefrontSelection $storefront_selection, $drop_out_of_stock)
	{
		parent::__construct();

		$this->storefront_selection = $storefront_selection;
		$this->drop_out_of_stock = $drop_out_of_stock;
	}

	protected function execute()
	{
		$storage = new shopEditStorefrontStorage();

		$storefront_selection = $this->storefront_selection;

		/** @var shopEditStorefront[] $storefronts */
		$storefronts = array();
		if ($storefront_selection->mode == shopEditStorefrontSelection::MODE_ALL)
		{
			$storefronts = $storage->getAllShopStorefronts();
		}
		elseif ($storefront_selection->mode == shopEditStorefrontSelection::MODE_SELECTED)
		{
			$storefronts = $storage->getShopStorefronts($storefront_selection->storefronts);
		}

		$affected_storefronts = array();
		foreach ($storefronts as $storefront)
		{
			if ($storefront->drop_out_of_stock == $this->drop_out_of_stock)
			{
				continue;
			}

			$storefront->drop_out_of_stock = $this->drop_out_of_stock;
			$affected_storefronts[$storefront->name] = $storefront->name;
		}
		unset($storefront);

		$storage->updateShopStorefronts($storefronts);

		return array(
			'storefront_selection' => $this->storefront_selection->assoc(),
			'drop_out_of_stock' => $this->drop_out_of_stock,
			'affected_storefronts' => array_values($affected_storefronts),
			'affected_storefronts_count' => count($affected_storefronts),
		);
	}

	protected function getAction()
	{
		return $this->action_options->STOREFRONT_SET_DROP_OUT_OF_STOCK;
	}
}