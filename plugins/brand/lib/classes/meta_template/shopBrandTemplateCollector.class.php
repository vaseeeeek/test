<?php

class shopBrandTemplateCollector
{
	private $page_storage;
	private $brand_page_storage;
	private $storefront_page_meta_storage;
	private $brands_page_template_layout_storage;

	public function __construct()
	{
		$this->page_storage = new shopBrandPageStorage();
		$this->brand_page_storage = new shopBrandBrandPageStorage();
		$this->storefront_page_meta_storage = new shopBrandStorefrontTemplateLayoutStorage();
		$this->brands_page_template_layout_storage = new shopBrandBrandsPageTemplateLayoutStorage();
	}

	public function getBrandPageTemplateLayout($brand_id, $page_id, $storefront)
	{
		$layout_collection = new shopBrandTemplateLayoutsCollection();


		/** @var shopBrandPropertyAccess[] $meta_sources */
		$meta_sources = array(
			$this->storefront_page_meta_storage->getBrandPageMeta($storefront, $page_id, $brand_id),
			$this->brand_page_storage->getPage($brand_id, $page_id),
			$this->storefront_page_meta_storage->getPageMeta($storefront, $page_id),
			$this->page_storage->getById($page_id)
		);

		foreach ($meta_sources as $meta_source)
		{
			if ($meta_source)
			{
				$layout_collection->push(new shopBrandTemplateLayout($meta_source->assoc()));
			}
		}

		return $layout_collection->mergeTemplateLayouts();
	}

	public function getBrandsPageTemplateLayout($storefront)
	{
		$layout_collection = new shopBrandTemplateLayoutsCollection();

		$layout_collection->push($this->brands_page_template_layout_storage->getMeta($storefront)->getTemplateLayout());
		$layout_collection->push($this->brands_page_template_layout_storage->getMeta(shopBrandStorefront::GENERAL)->getTemplateLayout());

		return $layout_collection->mergeTemplateLayouts();
	}
}