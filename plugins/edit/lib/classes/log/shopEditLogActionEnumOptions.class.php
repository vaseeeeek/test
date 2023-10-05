<?php

/**
 * Class shopEditLogActionEnumOptions
 *
 * @property-read string $CATEGORY_TOGGLE_INCLUDE_SUBCATEGORIES
 * @property-read string $CATEGORY_TOGGLE_CLIENT_SORTING
 * @property-read string $CATEGORY_SET_DEFAULT_SORTING
 * @property-read string $BRAND_CHANGE_DEFAULT_SORTING
 * @property-read string $BRAND_TOGGLE_CLIENT_SORTING
 * @property-read string $CATEGORY_MOVE_META_TAGS
 * @property-read string $CATEGORY_CHANGE_FILTERS
 * @property-read string $PRODUCT_UPDATE_PRICE
 * @property-read string $SITE_SAVE_APPS_MENU
 * @property-read string $SITE_CLEAR_HEAD_JS
 * @property-read string $SITE_COPY_APPS_MENU
 * @property-read string $SITE_COPY_AUTH_CONFIG
 * @property-read string $STOREFRONT_SET_URL_TYPE
 * @property-read string $STOREFRONT_SET_DROP_OUT_OF_STOCK
 * @property-read string $STOREFRONT_SET_SHIPPING
 * @property-read string $STOREFRONT_SET_PAYMENT
 * @property-read string $META_DELETE
 * @property-read string $STOREFRONT_SET_THEME
 * @property-read string $SITE_COPY_STRUCTURE
 * @property-read string $SITE_COPY_ROUTING
 * @property-read string $BRAND_CHANGE_FILTERS
 * @property-read string $SEOFILTER_MOVE_META_TAGS
 * @property-read string $CATEGORY_UPDATE_EMPTY_CATEGORY
 * @property-read string $CATALOG_UPDATE_PARAMS
 */
class shopEditLogActionEnumOptions extends shopEditEnumOptions
{
	protected function getOptionValues()
	{
		return array(
			'CATEGORY_TOGGLE_INCLUDE_SUBCATEGORIES',
			'CATEGORY_TOGGLE_CLIENT_SORTING',
			'CATEGORY_SET_DEFAULT_SORTING',
			'CATEGORY_MOVE_META_TAGS',
			'CATEGORY_CHANGE_FILTERS',
			'CATEGORY_UPDATE_EMPTY_CATEGORY',
			'PRODUCT_UPDATE_PRICE',
			'SITE_SAVE_APPS_MENU',
			'SITE_CLEAR_HEAD_JS',
			'SITE_COPY_APPS_MENU',
			'SITE_COPY_AUTH_CONFIG',
			'STOREFRONT_SET_URL_TYPE',
			'STOREFRONT_SET_DROP_OUT_OF_STOCK',
			'STOREFRONT_SET_SHIPPING',
			'STOREFRONT_SET_PAYMENT',
			'META_DELETE',
			'STOREFRONT_SET_THEME',
			'SITE_COPY_STRUCTURE',
			'SITE_COPY_ROUTING',
			'BRAND_CHANGE_FILTERS',
			'BRAND_CHANGE_DEFAULT_SORTING',
			'BRAND_TOGGLE_CLIENT_SORTING',
			'SEOFILTER_MOVE_META_TAGS',
			'CATALOG_UPDATE_PARAMS',
		);
	}
}
