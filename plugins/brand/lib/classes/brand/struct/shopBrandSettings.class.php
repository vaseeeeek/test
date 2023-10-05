<?php

/**
 * Class shopBrandSettings
 *
 * @property bool $is_enabled
 * @property int $brand_feature_id
 * @property array $brand_feature
 * @property string $new_review_status
 * @property string $category_link_mode
 * @property bool $use_additional_description
 * @property bool $add_product_reviews
 * @property bool $display_brands_to_frontend_nav
 * @property bool $with_images_only
 * @property bool $hide_reviews_tab_if_empty
 * @property bool $disable_add_review_captcha
 * @property string $brands_default_sort
 * @property bool $routing_is_extended
 * @property bool $use_optimized_images
 * @property string $thumbnail_sizes
 * @property integer $cache_lifetime
 * @property string[] $thumbnail_sizes_array
 * @property string $default_thumbnail_size
 * @property string $empty_page_response_mode
 */
class shopBrandSettings extends shopBrandPropertyAccess
{
	private static $brand_features = array();

	private $storefront;

	function __get($name)
	{
		if ($name === 'brand_feature')
		{
			if (!array_key_exists($this->storefront, self::$brand_features))
			{
				$model = new shopFeatureModel();
				self::$brand_features[$this->storefront] = $model->getById($this->brand_feature_id);
			}

			return self::$brand_features[$this->storefront];
		}
		elseif ($name === 'default_thumbnail_size')
		{
			return $this->getDefaultThumbnailSize();
		}
		else
		{
			return parent::__get($name);
		}
	}

	public function getDefaultThumbnailSize()
	{
		return count($this->thumbnail_sizes_array) === 0
			? shopBrandImageStorage::SIZE_BACKEND_LIST
			: $this->thumbnail_sizes_array[0];
	}

	public function isThumbnailSizeAvailable($size)
	{
		foreach ($this->thumbnail_sizes_array as $valid_size)
		{
			if ($size === $valid_size)
			{
				return true;
			}
		}

		return false;
	}
}
