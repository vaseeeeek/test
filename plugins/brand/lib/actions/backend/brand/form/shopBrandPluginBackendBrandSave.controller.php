<?php

class shopBrandPluginBackendBrandSaveController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$state_json = waRequest::post('state');

		$state = json_decode($state_json, true);

		$brand = $state['brand'];
		$brand_pages = $state['brand_pages'];
		$brand_field_values = $state['brand_field_values'];
		$storefront_brand_page_template_layout = $state['storefront_brand_page_template_layout'];
		$brand_filters_order = $state['brand_filters_order'];


		$brand_errors = $this->validateBrand($brand);

		if (count($brand_errors))
		{
			$this->response['errors'] = array(
				'brand' => $brand_errors,
			);
		}
		else
		{
			$brand_id = $this->saveBrand($brand, $brand_filters_order);

			if ($brand_id)
			{
				$this->saveBrandPages($brand_id, $brand_pages);
				$this->saveBrandFieldValues($brand_id, $brand_field_values);
				$this->saveBrandStorefrontTemplateLayouts($brand_id, $storefront_brand_page_template_layout);

				$this->response['success'] = true;

				$brand_storage = new shopBrandBrandStorage();
				$this->response['brand'] = $this->prepareBrand($brand_storage->getById($brand_id));
			}
		}
	}

	private function validateBrand($brand_assoc)
	{
		$brand_storage = new shopBrandBrandStorage();

		$brand = new shopBrandBrand($brand_assoc);

		$errors = array();
		$url = $brand->url;

		if (strlen($url) == 0)
		{
			$errors['url'] = 'Укажите URL';
		}
		else
		{
			$unique_url = $brand_storage->getUniqueUrl($url, $brand->id);

			if ($unique_url != $url)
			{
				$errors['url'] = 'Бренд с таким URL уже существует';
			}
		}

		if (strlen($brand->name) == 0)
		{
			$errors['name'] = 'Укажите название бренда';
		}

		return $errors;
	}

	private function saveBrand($brand, $brand_filters_order)
	{
		$brand_storage = new shopBrandBrandStorage();

		$filter = $brand['filter'];

		if (!$brand['filtration_is_enabled'])
		{
			$brand['filter'] = array();
		}
		elseif (is_array($brand_filters_order) && is_array($filter))
		{
			$filter_by_keys = array();
			foreach ($filter as $id)
			{
				$filter_by_keys[$id] = $id;
			}

			$filter_result = array();
			foreach ($brand_filters_order as $id)
			{
				if (array_key_exists($id, $filter_by_keys))
				{
					$filter_result[] = $id;
				}
			}

			$brand['filter'] = $filter_result;
		}

		return $brand_storage->store($brand);
	}

	private function saveBrandPages($brand_id, $brand_pages)
	{
		$page_storage = new shopBrandBrandPageStorage();

		foreach ($brand_pages as $page_id => $brand_page_assoc)
		{
			$page_storage->store($brand_id, $page_id, $brand_page_assoc);
		}
	}

	private function saveBrandFieldValues($brand_id, $brand_field_values)
	{
		$storage = new shopBrandBrandFieldValueModel();

		foreach ($brand_field_values as $field_id => $value)
		{
			$storage->insert(array(
				'brand_id' => $brand_id,
				'field_id' => $field_id,
				'value' => $value,
			), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
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
		$brand_assoc['frontend_url'] = wa()->getRouteUrl('shop', $route_params);

		return $brand_assoc;
	}

	private function saveBrandStorefrontTemplateLayouts($brand_id, $storefront_brand_page_template_layout)
	{
		$storage = new shopBrandStorefrontTemplateLayoutStorage();

		foreach ($storefront_brand_page_template_layout as $storefront => $page_template_layouts)
		{
			foreach ($page_template_layouts as $page_id => $storefront_template_layout_assoc)
			{
				$storefront_template_layout = new shopBrandStorefrontTemplateLayout($storefront_template_layout_assoc);

				$storage->saveBrandPageMeta($storefront, $page_id, $brand_id, $storefront_template_layout);
			}
		}
	}
}
