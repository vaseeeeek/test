<?php

class shopBrandBrandImageStorage
{
	private $plugin_image_storage;
	private $plugin_settings;

	public function __construct()
	{
		$this->plugin_image_storage = new shopBrandImageStorage();

		// todo refactor ?
		$settings_storage = new shopBrandSettingsStorage();
		$this->plugin_settings = $settings_storage->getSettings();
	}

	/**
	 * @param shopBrandBrand $brand
	 * @throws waException
	 */
	public function deleteImage(shopBrandBrand $brand)
	{
		if (!is_string($brand->image) || strlen($brand->image) == 0)
		{
			return;
		}

		$image_path = $this->plugin_image_storage->getOriginalImagePath($brand->image);
		waFiles::delete($image_path);

		foreach ($this->plugin_image_storage->getAllOptimizedImagePaths($brand->image) as $optimized_image_path)
		{
			waFiles::delete($optimized_image_path);
		}
	}

	public function getOriginalImageUrl(shopBrandBrand $brand)
	{
		return $this->isOriginalFileExists($brand)
			? $this->plugin_image_storage->getOriginalImageUrl($brand->image)
			: '';
	}

	public function getOptimizedImageUrl(shopBrandBrand $brand, $size)
	{
		if (!$this->plugin_settings->use_optimized_images)
		{
			return '';
		}

		$size = !is_string($size) || trim($size) === ''
			? $this->plugin_settings->default_thumbnail_size
			: trim($size);

		return $this->isSizeAvailable($size) && $this->hasImage($brand)
			? $this->plugin_image_storage->getOptimizedImageUrl($brand->image, $size)
			: '';
	}

	public function hasImage(shopBrandBrand $brand)
	{
		$image_name = $brand->image;

		return is_string($image_name)
			&& trim($image_name) !== ''
			&& file_exists($this->plugin_image_storage->getOriginalImagePath(trim($image_name)));
	}

	public function isSizeAvailable($size)
	{
		return $this->plugin_settings->isThumbnailSizeAvailable($size) || $this->plugin_image_storage->isBuiltInImageSize($size);
	}

	private function isOriginalFileExists(shopBrandBrand $brand)
	{
		return $this->plugin_image_storage->isOriginalFileExists($brand->image);
	}
}
