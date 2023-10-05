<?php

/**
 * Class shopBrandBrand
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $image
 * @property string $description_short
 * @property string $product_sort
 * @property array $filter
 * @property bool $is_shown
 * @property bool $enable_client_sorting
 * @property string $empty_page_response_mode
 * @property string $sort
 *
 * @property array $fields
 * @property array $field
 * @property string $image_url
 * @property string $frontend_url
 */
class shopBrandBrand extends shopBrandPropertyAccess
{
	const DB_TRUE = '1';
	const DB_FALSE = '0';
	const EMPTY_PAGE_RESPONSE_MODE = 'DEFAULT';

	private static $brand_image_storage = null;

	private static $_brand_main_url_templates = array();
	private static $_brand_url_templates = array();

	private $_fields = array();

	public function __construct($entity_array = null, $fields = null)
	{
		parent::__construct($entity_array);

		$this->_fields = is_array($fields) ? $fields : array();
	}

	public function getImageUrl($size = null)
	{
		if (!$this->image)
		{
			return '';
		}

		if ($size === 'original')
		{
			return $this->getBrandImageStorage()->getOriginalImageUrl($this);
		}

		$optimized_url = $this->getBrandImageStorage()->getOptimizedImageUrl($this, $size);

		return is_string($optimized_url) && $optimized_url !== ''
			? $optimized_url
			: $this->getBrandImageStorage()->getOriginalImageUrl($this);
	}

	public function hasImage()
	{
		return $this->getBrandImageStorage()->hasImage($this);
	}

	/**
	 * @param shopBrandPage|null $page
	 * @param bool $absolute
	 * @param string|null $domain
	 * @param string|null $route_url
	 * @return mixed|null
	 */
	public function getFrontendUrl($page = null, $absolute = false, $domain = null, $route_url = null)
	{
		$key_parts = array();
		if ($domain)
		{
			$key_parts[] = $domain;
		}
		if ($route_url)
		{
			$key_parts[] = $route_url;
		}
		$key_parts[] = $absolute ? '0' : '1';
		$key = implode('/', $key_parts);

		if (!array_key_exists($key, self::$_brand_main_url_templates) || !array_key_exists($key, self::$_brand_url_templates))
		{
			$route_params = array(
				'plugin' => 'brand',
				'module' => 'frontend',
				'action' => 'brandPage',
				'brand' => '%BRAND_URL%',
			);

			self::$_brand_main_url_templates[$key] = wa()->getRouting()->getUrl('shop', $route_params, $absolute, $domain, $route_url);

			$route_params['brand_page'] = '%BRAND_PAGE_URL%';
			self::$_brand_url_templates[$key] = wa()->getRouting()->getUrl('shop', $route_params, $absolute, $domain, $route_url);
		}

		return $page && !$page->isMain()
			? str_replace('%BRAND_URL%', $this->url, str_replace('%BRAND_PAGE_URL%', $page->url, self::$_brand_url_templates[$key]))
			: str_replace('%BRAND_URL%', $this->url, self::$_brand_main_url_templates[$key]);
	}

	protected function getEntityFieldValue($name)
	{
		if ($name == 'image_url')
		{
			return $this->getImageUrl();
		}
		elseif ($name == 'frontend_url')
		{
			return $this->getFrontendUrl();
		}
		elseif ($name == 'fields' || $name == 'field')
		{
			return $this->_fields;
		}
		elseif ($name == 'enable_sorting')
		{
			return parent::getEntityFieldValue('enable_client_sorting');
		}
		else
		{
			return parent::getEntityFieldValue($name);
		}
	}

	protected function getDefaultAttributes()
	{
		return array(
			'id' => null,
			'name' => '',
			'url' => '',
			'image' => null,
			'description_short' => '',
			'product_sort' => '',
			'filter' => '',
			'is_shown' => self::DB_TRUE,
			'enable_client_sorting' => self::DB_TRUE,
			'empty_page_response_mode' => self::EMPTY_PAGE_RESPONSE_MODE,
			'sort' => 0,
		);
	}

	/**
	 * @return shopBrandBrandImageStorage
	 */
	private function getBrandImageStorage()
	{
		if (self::$brand_image_storage === null)
		{
			self::$brand_image_storage = new shopBrandBrandImageStorage();
		}

		return self::$brand_image_storage;
	}

	public function offsetExists($offset)
	{
		if ($offset == 'image_url' || $offset == 'fields' || $offset == 'field')
		{
			return true;
		}
		else
		{
			return parent::offsetExists($offset);
		}
	}
}
