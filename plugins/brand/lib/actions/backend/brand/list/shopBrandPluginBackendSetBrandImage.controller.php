<?php

class shopBrandPluginBackendSetBrandImageController extends shopBrandPluginBackendUploadBrandImageController
{
	protected function save(waRequestFile $file)
	{
		$result = parent::save($file);

		if (is_array($result) && array_key_exists('file_name', $result))
		{
			$file_name = $result['file_name'];
			$brand_id = waRequest::post('brand_id');

			$storage = new shopBrandBrandStorage();
			$brand = $storage->getById($brand_id);

			if ($brand)
			{
				$brand->image = $file_name;

				$storage->store($brand->assoc());
			}
		}

		return $result;
	}
}