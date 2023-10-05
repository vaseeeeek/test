<?php

class shopBrandPluginBackendGetPageTemplateLayoutController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
        $this->getResponse()->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
		$this->response['success'] = false;

		$storefront = waRequest::get('storefront');
		$page_id = waRequest::get('page_id');
		$brand_id = waRequest::get('brand_id');

		$template_layout_storage = new shopBrandStorefrontTemplateLayoutStorage();

		if ($page_id > 0)
		{
			$storefront_template_layout = $brand_id > 0
				? $template_layout_storage->getBrandPageMeta($storefront, $page_id, $brand_id)
				: $template_layout_storage->getPageMeta($storefront, $page_id);

			if ($storefront_template_layout)
			{
				$this->response['template_layout'] = $storefront_template_layout->assoc();
				$this->response['success'] = true;
			}
		}
		else
		{
			$storefront_template_layouts = $brand_id > 0
				? $template_layout_storage->getBrandMeta($storefront, $brand_id)
				: $template_layout_storage->getMeta($storefront);

			$result_assoc = array();
			foreach ($storefront_template_layouts as $page_id => $storefront_template_layout)
			{
				$result_assoc[$page_id] = $storefront_template_layout->assoc();
			}

			$this->response['template_layouts'] = $result_assoc;
			$this->response['success'] = true;
		}
	}
}
