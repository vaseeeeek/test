<?php

/**
 * Class shopBrandBrandPage
 *
 * @property int $id
 * @property int $page_id
 * @property int $brand_id
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $h1
 * @property string $content
 * @property string $description
 * @property string $additional_description
 * @property string $create_datetime
 * @property string $update_datetime
 * @property int $create_contact_id
 * @property int $sort
 */
class shopBrandBrandPage extends shopBrandPropertyAccess
{
	const STATUS_PUBLISHED = 'PUBLISHED';
	const STATUS_DRAFT = 'DRAFT';

	const TYPE_PAGE = 'PAGE';
	const TYPE_CATALOG = 'CATALOG';
	const TYPE_REVIEWS = 'REVIEWS';

	function __set($name, $value)
	{
		$this->_entity_array[$name] = $value;
	}

	public function getTemplatePath($theme_id = null)
	{
		if (!strlen(trim($this->template)))
		{
			return null;
		}

		if ($theme_id === null)
		{
			$theme_id = waRequest::getTheme();
		}

		$themes = wa()->getThemes('shop');

		if (!isset($themes[$theme_id]))
		{
			return null;
		}

		$theme = $themes[$theme_id];

		$template_path = $theme->getPath() . '/' . $this->template;

		return file_exists($template_path)
			? $template_path
			: null;
	}

	public function getTemplateContent()
	{
		if (!$this->haveThemeTemplate())
		{
			return null;
		}

		$content = file_get_contents($this->getTemplatePath());

		return trim($content);
	}

	public function haveThemeTemplate()
	{
		$path = $this->getTemplatePath();

		return !!$path;
	}

	public function getFrontendUrl(shopBrandBrand $brand)
	{
		$route_params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'brandPage',
			'brand' => $brand->url,
		);

		if ($this->url != shopBrandPluginFrontendBrandPageAction::PAGE_CATALOG)
		{
			$route_params['brand_page'] = $this->url;
		}

		$routing = wa()->getRouting();

		return $routing->getUrl('shop', $route_params, true);
	}

	protected function getDefaultAttributes()
	{
		return array(
			'id' => null,
			'brand_id' => 0,
			'name' => '',
			'url' => '',
			'meta_title' => '',
			'meta_description' => '',
			'meta_keywords' => '',
			'h1' => '',
			'content' => '',
			'description' => '',
			'additional_description' => '',
			'template' => '',
			'create_datetime' => date('Y:m:d H:i:s'),
			'update_datetime' => date('Y:m:d H:i:s'),
			'create_contact_id' => null,
			'status' => self::STATUS_PUBLISHED,
			'sort' => 0,
			'type' => self::TYPE_PAGE,
		);
	}

	public function offsetExists($offset)
	{
		if ($offset == 'frontend_url')
		{
			return true;
		}
		else
		{
			return parent::offsetExists($offset);
		}
	}

	public function isEmpty()
	{
		$content = $this->content;

		return !is_string($content) || strlen($content) == 0;
	}
}
