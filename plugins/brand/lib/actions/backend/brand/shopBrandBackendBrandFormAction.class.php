<?php

abstract class shopBrandBackendBrandFormAction extends shopBrandBackendAction
{
	public function execute()
	{
		$this->setTemplate('BackendBrandForm');

		$brand = $this->getBrand();

		$brand_assoc = $this->prepareBrand($brand);

		//$brand_assoc['prev_brand'] = array(
		//	'name' => '',
		//	'edit_url' => '?plugin=seobrand&action=brandEdit&brand_id=',
		//);
		//$brand_assoc['next_brand'] = array(
		//	'name' => '',
		//	'edit_url' => '?plugin=seobrand&action=brandEdit&brand_id=',
		//);

		$settings_storage = new shopBrandSettingsStorage();

		$state = array(
			'brand' => $brand_assoc,
			'pages' => $this->getPages(),
			'brand_pages' => $this->getBrandPages(),
			'brand_field_values' => $this->getBrandFieldValues(),
			'brand_fields' => $this->getBrandFields(),
			'storefronts' => shopBrandStorefront::getAll(),
			'features' => $this->getFeatures(),
			'storefronts_with_personal_templates' => $this->getStorefrontsWithPersonalTemplates($brand->id),
			'template_variables' => $this->getTemplateVariables(),
			'use_additional_description' => $settings_storage->getSettings()->use_additional_description,
		);
		$this->view->assign('state', $state);
	}

	/**
	 * @return shopBrandBrand
	 */
	abstract protected function getBrand();

	protected function getBrandFields()
	{
		$storage = new shopBrandBrandFieldStorage();

		return $storage->getAllFields();
	}

	protected function getFeatures()
	{
		$model = new shopFeatureModel();

		return $model->select('id,name,code,type')->fetchAll('id');
	}

	protected function getBrandFieldValues()
	{
		$brand_id = waRequest::get('brand_id');
		if (!($brand_id > 0))
		{
			return array();
		}

		$storage = new shopBrandBrandFieldStorage();

		return $storage->getBrandFieldValues($brand_id);
	}

	protected function getBrandPages()
	{
		$brand_id = waRequest::get('brand_id');
		if (!($brand_id > 0))
		{
			return array();
		}

		$brand_page_storage = new shopBrandBrandPageStorage();

		$brand_pages_assoc = array();
		foreach ($brand_page_storage->getPages($brand_id) as $page_id => $brand_page)
		{
			$brand_pages_assoc[$page_id] = $brand_page->assoc();
		}
		unset($brand_page);

		return $brand_pages_assoc;
	}

	protected function getPages()
	{
		$page_storage = new shopBrandPageStorage();

		$pages_assoc = array();
		foreach ($page_storage->getAll() as $page_id => $page)
		{
			$pages_assoc[] = $page->assoc();
		}

		return $pages_assoc;
	}

	protected function prepareBrand(shopBrandBrand $brand)
	{
		$brand_assoc = $brand->assoc();

		$image_url = $brand->getImageUrl();
		if ($image_url)
		{
			$brand_assoc['image_url'] = $image_url;
		}

		$route_params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'brandPage',
			'brand' => $brand->url,
		);
		$brand_assoc['frontend_url'] = $brand->url
			? wa()->getRouteUrl('shop', $route_params)
			: null;

		return $brand_assoc;
	}

	private function getStorefrontsWithPersonalTemplates($brand_id)
	{
		$model = new shopBrandStorefrontTemplateLayoutModel();

		$storefronts = $model->select('DISTINCT storefront')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->fetchAll('storefront');

		return array_keys($storefronts);
	}

	private function getTemplateVariables()
	{
		$variables = new shopBrandTemplateVariables();

		return $variables->getViewState();
	}
}