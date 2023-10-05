<?php

class shopBrandStorefrontTemplateLayoutStorage extends shopBrandStorage
{
	const DEFAULT_BRAND_ID = 0;

	/**
	 * @param string $storefront
	 * @return shopBrandStorefrontTemplateLayout[]
	 */
	public function getMeta($storefront) {
		$template_layouts_raw = $this->model->getByField(array(
			'storefront' => $storefront,
			'brand_id' => self::DEFAULT_BRAND_ID,
		), true);

		$template_layouts = array();
		foreach ($template_layouts_raw as $template_layout_raw)
		{
			$template_layouts[$template_layout_raw['page_id']] = new shopBrandStorefrontTemplateLayout($this->prepareStorableForAccessible($template_layout_raw));
		}

		return $template_layouts;
	}

	/**
	 * @param string $storefront
	 * @param int $brand_id
	 * @return shopBrandStorefrontTemplateLayout[]
	 */
	public function getBrandMeta($storefront, $brand_id) {
		$template_layouts_raw = $this->model->getByField(array(
			'storefront' => $storefront,
			'brand_id' => $brand_id,
		), true);

		$template_layouts = array();
		foreach ($template_layouts_raw as $template_layout_raw)
		{
			$template_layouts[$template_layout_raw['page_id']] = new shopBrandStorefrontTemplateLayout($this->prepareStorableForAccessible($template_layout_raw));
		}

		return $template_layouts;
	}

	/**
	 * @param string $storefront
	 * @param int $page_id
	 * @param int $brand_id
	 * @return shopBrandStorefrontTemplateLayout|null
	 */
	public function getBrandPageMeta($storefront, $page_id, $brand_id)
	{
		$meta_raw = $this->model->getByField(array(
			'storefront' => $storefront,
			'page_id' => $page_id,
			'brand_id' => $brand_id,
		));

		if (!$meta_raw)
		{
			return null;
		}

		return new shopBrandStorefrontTemplateLayout($this->prepareStorableForAccessible($meta_raw));
	}

	/**
	 * @param string $storefront
	 * @param int $page_id
	 * @return shopBrandStorefrontTemplateLayout|null
	 */
	public function getPageMeta($storefront, $page_id)
	{
		$meta_raw = $this->model->getByField(array(
			'storefront' => $storefront,
			'page_id' => $page_id,
			'brand_id' => self::DEFAULT_BRAND_ID,
		));

		if (!$meta_raw)
		{
			return null;
		}

		return new shopBrandStorefrontTemplateLayout($this->prepareStorableForAccessible($meta_raw));
	}

	public function savePageMeta($storefront, $page_id, shopBrandStorefrontTemplateLayout $template_layout)
	{
		$template_layout->storefront = $storefront;
		$template_layout->page_id = $page_id;
		$template_layout->brand_id = self::DEFAULT_BRAND_ID;

		if ($template_layout->isEmpty())
		{
			return $this->delete($template_layout);
		}

		$to_save = $this->prepareAccessibleToStorable($template_layout);

		return $this->model->insert($to_save, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function saveBrandPageMeta($storefront, $page_id, $brand_id, shopBrandStorefrontTemplateLayout $template_layout)
	{
		$template_layout->storefront = $storefront;
		$template_layout->page_id = $page_id;
		$template_layout->brand_id = $brand_id;

		if ($template_layout->isEmpty())
		{
			return $this->delete($template_layout);
		}

		$to_save = $this->prepareAccessibleToStorable($template_layout);

		return $this->model->insert($to_save, waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}

	public function delete(shopBrandStorefrontTemplateLayout $template_layout)
	{
		return $this->model->deleteByField(array(
			'storefront' => $template_layout->storefront,
			'page_id' => $template_layout->page_id,
			'brand_id' => $template_layout->brand_id,
		));
	}

	protected function accessSpecification()
	{
		$specification = new shopBrandDataFieldSpecificationFactory();

		return array(
			'storefront' => $specification->string(),
			'page_id' => $specification->integer(),
			'brand_id' => $specification->integer(),
			'meta_title' => $specification->string(),
			'meta_description' => $specification->string(),
			'meta_keywords' => $specification->string(),
			'h1' => $specification->string(),
			'description' => $specification->string(),
			'additional_description' => $specification->string(),
			'content' => $specification->string(),
		);
	}

	protected function dataModel()
	{
		return new shopBrandStorefrontTemplateLayoutModel();
	}
}