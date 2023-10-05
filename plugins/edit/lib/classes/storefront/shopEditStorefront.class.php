<?php

/**
 * Class shopEditStorefront
 *
 * @property string $theme
 * @property string $theme_mobile
 * @property string $locale
 * @property string $title
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $og_title
 * @property string $og_image
 * @property string $og_video
 * @property string $og_description
 * @property int $url_type
 * @property string $url
 * @property string $currency
 * @property int $drop_out_of_stock
 * @property array $payment_id
 * @property array $shipping_id
 */
class shopEditStorefront
{
	public $name;

	public $domain;
	public $route = array();

	public function __construct($domain, $route)
	{
		$this->domain = $domain;
		$this->route = $route;

		$this->name = $domain . '/' . $route['url'];
	}

	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		if (array_key_exists($name, $this->route))
		{
			return $this->route[$name];
		}

		if ($name == 'payment_id' || $name == 'shipping_id')
		{
			return '0';
		}

		return null;
	}

	public function __set($name, $value)
	{
		if (property_exists($this, $name))
		{
			$this->$name = $value;
		}
		else
		{
			$this->route[$name] = $value;
		}
	}

	public function assoc()
	{
		return array(
			'name' => $this->name,
			'domain' => $this->domain,
			'route' => $this->route,
		);
	}
}