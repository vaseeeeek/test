<?php

class shopEditStorefrontSetUrlTypeAction extends shopEditLoggedAction
{
	private $storefront_selection;
	private $url_type;

	public function __construct(shopEditStorefrontSelection $storefront_selection, $url_type)
	{
		parent::__construct();

		$this->storefront_selection = $storefront_selection;
		$this->url_type = $url_type;
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
			if ($storefront->url_type == $this->url_type)
			{
				continue;
			}

			$storefront->url_type = $this->url_type;
			$affected_storefronts[$storefront->name] = $storefront->name;
		}

		$storage->updateShopStorefronts($storefronts);

		return array(
			'storefront_selection' => $this->storefront_selection->assoc(),
			'url_type' => $this->url_type,
			'affected_storefronts' => $affected_storefronts,
			'affected_storefronts_count' => count($affected_storefronts),
		);
	}

	protected function getAction()
	{
		return $this->action_options->STOREFRONT_SET_URL_TYPE;
	}
}