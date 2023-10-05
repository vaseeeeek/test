<?php

class shopBrandPluginBackendDeleteBrandImageController extends shopBrandWaBackendJsonController
{
	/**
	 * @throws waException
	 */
	public function execute()
	{
		$this->response['success'] = false;

		$brand_id = waRequest::post('brand_id');

		$storage = new shopBrandBrandStorage();
		$brand = $storage->getById($brand_id);
		if (!$brand)
		{
			return;
		}

		$brand_image_storage = new shopBrandBrandImageStorage();
		$brand_image_storage->deleteImage($brand);

		if (is_string($brand->image) && $brand->image !== '')
		{
			$brand->image = '';

			$storage->store($brand->assoc());
		}

		$this->response['success'] = true;
	}
}
