<?php

class shopBrandPluginBackendUploadBrandImageController extends shopUploadController
{
	protected function save(waRequestFile $file)
	{
		$image_storage = new shopBrandImageStorage();

		if (!$image_storage->handleImageUpload($file, $new_file_name))
		{
			return false;
		}

		return array(
			'file_name' => $new_file_name,
			'url' => $image_storage->getOptimizedImageUrl($new_file_name, shopBrandImageStorage::SIZE_BACKEND_LIST),
		);
	}
}
