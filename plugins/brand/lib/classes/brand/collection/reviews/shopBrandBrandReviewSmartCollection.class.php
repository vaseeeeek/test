<?php

class shopBrandBrandReviewSmartCollection extends shopBrandBrandReviewsCollection
{
	private $settings;
	private $brand_id;

	public function __construct($brand_id, $route = null)
	{
		parent::__construct($route);

		$this->brand_id = $brand_id;

		$settings_storage = new shopBrandSettingsStorage();
		$this->settings = $settings_storage->getSettings();

		$this
			->filterBrandId($brand_id)
			->publishedOnly()
			->includeDescendants()
			->filterEmptyReviews();

		if ($this->settings->add_product_reviews)
		{
			$this->addProductReviews();
		}
	}
}
