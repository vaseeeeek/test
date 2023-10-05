<?php

class shopBrandImportProductbrandsBrands
{
	/**
	 * @throws waException
	 */
	public function import()
	{
		if (!$this->isProductbrandsPluginInstalled())
		{
			return;
		}

		$collection = new shopProductbrandsPluginCollection();
		$brand_storage = new shopBrandBrandStorage();
		$page_storage = new shopBrandPageStorage();
		$brand_page_storage = new shopBrandBrandPageStorage();
		$plugin_image_storage = new shopBrandImageStorage();

		$brand_sort_options = new shopBrandProductSortEnumOptions();

		$main_page = $page_storage->getMainPage();

		$brands = $collection->getBrands();

		$image_path = wa()->getDataPath('brands/', true, 'shop', false);

		$sort = 1;

		foreach ($brands as $brand_row)
		{
			$brand_id = $brand_row['id'];

			$image = null;
			if (isset($brand_row['image']) && $brand_row['image'])
			{
				$image_filename = $brand_id . $brand_row['image'];
				$image_full_path = $image_path . $brand_id . '/' . $image_filename;

				if (file_exists($image_full_path))
				{
					$image = $image_filename;

					$destination_fill_path = $plugin_image_storage->getOriginalImagePath($image_filename);
					waFiles::copy($image_full_path, $destination_fill_path);
				}
			}

			$filter = explode(',', ifset($brand_row['filter'], ''));

			$brand_assoc = array(
				'id' => $brand_id,
				'name' => $brand_row['name'],
				'url' => $brand_row['url'] ? $brand_row['url'] : $brand_row['name'],
				'image' => $image,
				'description_short' => ifset($brand_row['summary'], ''),
				'product_sort' => $brand_sort_options->MANUAL, // todo
				'filter' => json_encode($filter),
				'is_shown' => ifset($brand_row['hidden'], '0') == '0' ? shopBrandBrand::DB_TRUE : shopBrandBrand::DB_FALSE,
				'enable_client_sorting' => ifset($brand_row['enable_sorting'], '1') == '1' ? shopBrandBrand::DB_TRUE : shopBrandBrand::DB_FALSE,
				'sort' => $sort++,
			);

			$brand_storage->store($brand_assoc);


			$brand_page = new shopBrandBrandPage();
			$brand_page->meta_title = ifset($brand_row['title'], '');
			$brand_page->meta_description = ifset($brand_row['meta_description'], '');
			$brand_page->meta_keywords = ifset($brand_row['meta_keywords'], '');
			$brand_page->h1 = ifset($brand_row['h1'], '');
			$brand_page->description = ifset($brand_row['description'], '');
			$brand_page->additional_description = ifset($brand_row['seo_description'], '');

			$brand_page_storage->store($brand_id, $main_page->id, $brand_page);
		}
	}

	private function isProductbrandsPluginInstalled()
	{
		return class_exists('shopProductbrandsPluginCollection');
	}
}
