<?php

class shopBrandPluginSettingsSaveController extends shopBrandWaBackendJsonController
{
	protected function preExecute()
	{
		parent::preExecute();

		if (wa()->getUser()->getRights('shop', 'settings') == 0)
		{
			throw new waException('Нет прав', 403);
		}
	}

	public function execute()
	{
		$data_json = waRequest::post('state');

		$data = json_decode($data_json, true);

		$settings = ifset($data['settings']);
		$brands_page_template_layouts = $data['brands_page_template_layouts'];
		$storefront_pages_data = $data['storefront_pages'];
		$brand_fields = $data['brand_fields'];
		$action_theme_template = $data['action_theme_template'];

		$this->saveSettings($settings);
		$this->saveBrandsPageStorefrontTemplates($brands_page_template_layouts);
		$this->saveStorefrontPagesSettings($storefront_pages_data);
		$this->saveBrandFields($brand_fields);
		$this->saveActionThemeTemplate($action_theme_template);

		$this->clearPluginCache();

		$this->response = $this->buildResponse(array_keys($storefront_pages_data));
	}

	private function saveSettings($settings)
	{
		$storage = new shopBrandSettingsStorage();

		$storage->store($settings);
	}

	private function saveBrandsPageStorefrontTemplates($brands_page_template_layouts)
	{
		$storage = new shopBrandBrandsPageTemplateLayoutStorage();

		foreach ($brands_page_template_layouts as $storefront => $meta)
		{
			$storage->store($storefront, $meta);
		}
	}

	private function saveStorefrontPagesSettings($storefront_pages_data)
	{
		$page_storage = new shopBrandPageStorage();

		$pages = $storefront_pages_data['pages'];
		$page_ids_to_delete = $storefront_pages_data['page_ids_to_delete'];

		if (is_array($pages))
		{
			$page_storage->savePages($pages, is_array($page_ids_to_delete) ? $page_ids_to_delete : array());
		}


		$storefront_template_layout_storage = new shopBrandStorefrontTemplateLayoutStorage();

		$storefront_template_layouts = $storefront_pages_data['storefront_template_layouts'];
		foreach ($storefront_template_layouts as $storefront => $page_template_layout)
		{
			foreach ($page_template_layout as $page_id => $template_layout_assoc)
			{
				$template_layout = new shopBrandStorefrontTemplateLayout($template_layout_assoc);

				$storefront_template_layout_storage->savePageMeta($storefront, $page_id, $template_layout);
			}
		}
	}

	private function saveBrandFields($brand_fields)
	{
		$field_storage = new shopBrandBrandFieldStorage();

		foreach ($brand_fields as $field)
		{
			if ($field['is_deleted'])
			{
				if ($field['id'] > 0)
				{
					$field_storage->deleteById($field['id']);
				}
			}
			else
			{
				$field_storage->storeField($field);
			}
		}
	}

	private function buildResponse($page_storefronts)
	{
		$field_storage = new shopBrandBrandFieldStorage();

		return array(
			'success' => true,

			'brand_fields' => $field_storage->getAllFields(),
			'storefront_pages' => $this->getPages($page_storefronts),
			'page_storefronts_with_personal' => $this->getPageStorefrontsWithPersonal(),
			'brands_page_storefronts_with_personal' => $this->getBrandsPageStorefrontsWithPersonal(),
		);
	}

	private function getPages($page_storefronts)
	{
		$storage = new shopBrandPageStorage();

		$storefront_pages_assoc = array();
		foreach ($page_storefronts as $storefront)
		{
			$storefront_pages_assoc[$storefront] = array();

			foreach ($storage->getAll() as $page)
			{
				$storefront_pages_assoc[$storefront][] = $page->assoc();
			}
		}

		return $storefront_pages_assoc;
	}

	private function getPageStorefrontsWithPersonal()
	{
		$model = new shopBrandStorefrontTemplateLayoutModel();

		$storefronts = $model->select('DISTINCT storefront')
			->where('brand_id = :brand_id', array('brand_id' => shopBrandStorefrontTemplateLayoutStorage::DEFAULT_BRAND_ID))
			->fetchAll('storefront');

		return array_keys($storefronts);
	}

	private function getBrandsPageStorefrontsWithPersonal()
	{
		$model = new shopBrandSettingsModel();

		$storefronts = $model->select('DISTINCT storefront')->fetchAll('storefront');

		return array_keys($storefronts);
	}

	private function saveActionThemeTemplate($action_theme_template)
	{
		$template_storage = new shopBrandActionThemeTemplateStorage();

		$template_storage->setActionTheme($action_theme_template['action_theme_template']);
	}

	private function clearPluginCache()
	{
		$plugin_cache_path = wa('shop')->getCachePath('cache/plugins/brand/', 'shop');

		if (file_exists($plugin_cache_path) && is_dir($plugin_cache_path))
		{
			waFiles::delete($plugin_cache_path);
		}
	}
}
