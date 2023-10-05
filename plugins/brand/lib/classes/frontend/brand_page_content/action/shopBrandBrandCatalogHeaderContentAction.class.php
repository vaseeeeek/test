<?php

class shopBrandBrandCatalogHeaderContentAction extends shopBrandBrandPageContentAction
{
	protected function executeBrandPage(shopBrandFetchedLayout $fetched_layout)
	{
	}

	/** @return shopBrandActionTemplate */
	protected function getActionTemplate()
	{
		return new shopBrandCatalogHeaderTemplate($this->getTheme());
	}
}