<?php

/**
 * Class shopBrandBrandPage
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $h1
 * @property string $description
 * @property string $additional_description
 * @property string $template
 * @property string $create_datetime
 * @property string $update_datetime
 * @property int $create_contact_id
 * @property string $status
 * @property string $type
 * @property int $sort
 */
class shopBrandPage extends shopBrandPropertyAccess
{
	const STATUS_PUBLISHED = 'PUBLISHED';
	const STATUS_DRAFT = 'DRAFT';

	const TYPE_PAGE = 'PAGE';
	const TYPE_CATALOG = 'CATALOG';
	const TYPE_REVIEWS = 'REVIEWS';

	public function isEqual(shopBrandPage $page)
	{
		return $this->id == $page->id
			&& $this->name == $page->name
			&& $this->url == $page->url
			&& $this->meta_title == $page->meta_title
			&& $this->meta_description == $page->meta_description
			&& $this->meta_keywords == $page->meta_keywords
			&& $this->h1 == $page->h1
			&& $this->description == $page->description
			&& $this->additional_description == $page->additional_description
			&& $this->template == $page->template
			&& $this->status == $page->status
			&& $this->type == $page->type
			&& $this->sort == $page->sort;
	}

	public function getBrandUrl(shopBrandBrand $brand, $absolute = false)
	{
		return $brand->getFrontendUrl($this, $absolute);
	}

	public function isMain()
	{
		return $this->id == shopBrandPageStorage::MAIN_PAGE_ID;
	}
}