<?php

class shopProductgroupViewProductGroupsCompiler
{
	private static $product_url_template = [];
	private static $category_product_url_template = [];

	private $product_color_access;

	public function __construct()
	{
		$this->product_color_access = shopProductgroupPluginContext::getInstance()->getProductColorAccess();
	}

	/**
	 * @param shopProductgroupProductProductsGroup[] $product_groups
	 * @return array
	 */
	public function getGroups($product_groups)
	{
		$view_groups = [];

		foreach ($product_groups as $products_group)
		{
			$current_product_element = null;
			$have_current_product_element_in_elements = false;

			$elements = [];

			foreach ($products_group->group_products as $product)
			{
				$element = $this->buildViewGroupElement($product, $products_group);
				if (!$element)
				{
					continue;
				}

				if (
					!$current_product_element && $products_group->current_product
					&& $product['id'] == $products_group->current_product['id']
				)
				{
					$current_product_element = $element;
					$have_current_product_element_in_elements = true;
				}

				$elements[] = $element;
			}

			if (!$current_product_element && $products_group->current_product)
			{
				$current_product_element = $this->buildViewGroupElement($products_group->current_product, $products_group);
			}

			$view_groups[] = [
				'group' => $products_group->group->toAssoc(),
				'group_settings' => $products_group->group_settings->toAssoc(),
				'scope' => $products_group->scope,
				'elements' => $elements,
				'current_product_element' => $current_product_element,
				'have_current_product_element_in_elements' => $have_current_product_element_in_elements,
			];
		}

		return $view_groups;
	}

	/**
	 * @param array $product
	 * @param shopProductgroupProductProductsGroup $products_group
	 * @return array|null
	 */
	private function buildViewGroupElement($product, shopProductgroupProductProductsGroup $products_group)
	{
		$image_frontend_url = '';
		$color_value = '';

		if ($products_group->group->markup_template_id === shopProductgroupMarkupTemplateId::SIMPLE_GROUP)
		{
			if (empty($products_group->product_labels[$product['id']]))
			{
				return null;
			}
		}

		if ($products_group->group->markup_template_id === shopProductgroupMarkupTemplateId::PHOTO_GROUP)
		{
			$image_frontend_url = $this->getProductImage($product, $products_group->group_settings->image_size);

			if (!$image_frontend_url)
			{
				return null;
			}
		}

		if ($products_group->group->markup_template_id === shopProductgroupMarkupTemplateId::COLOR_GROUP)
		{
			if (!$products_group->group->related_feature_id)
			{
				return null;
			}

			$color_value = $this->getProductColor($product, $products_group->group->related_feature_id);
		}

		$label = empty($products_group->product_labels[$product['id']])
			? $product['name']
			: $products_group->product_labels[$product['id']];

		return [
			'product' => $product,
			'label' => $label,
			'frontend_url' => $this->getProductFrontendUrl($product),
			'image_frontend_url' => $image_frontend_url,
			'color_value' => $color_value,
		];
	}

	private function getProductImage($product, $image_size)
	{
		if (!$product['image_id'])
		{
			return null;
		}

		$image_size = $image_size
			? $image_size
			: '60x60';

		$image_assoc = [
			'id' => $product['image_id'],
			'product_id' => $product['id'],
			'ext' => $product['ext'],
			'filename' => $product['image_filename'],
			'original_filename' => '',
		];

		return shopImage::getUrl($image_assoc, $image_size);
	}

	/**
	 * @param $product
	 * @param $color_feature_id
	 * @return shopColorValue|null
	 */
	private function getProductColor($product, $color_feature_id)
	{
		return $this->product_color_access->getProductColor($product['id'], $color_feature_id);
	}

	private function getProductFrontendUrl($product)
	{
		if (isset($product['frontend_url']))
		{
			return $product['frontend_url'];
		}

		$storefront = $this->getStorefront();

		$this->initProductUrlTemplate($storefront);

		$search = ['%PRODUCT_URL%'];
		$replacement = [$product['url']];
		$template = self::$product_url_template[$storefront];

		if (isset($product['category_url']))
		{
			$search[] = '%CATEGORY_URL%';
			$replacement[] = $product['category_url'];
			$template = self::$category_product_url_template[$storefront];
		}

		return str_replace($search, $replacement, $template);
	}

	private function initProductUrlTemplate($storefront)
	{
		if (isset(self::$product_url_template[$storefront]))
		{
			return;
		}

		$route_params = [
			'product_url' => '%PRODUCT_URL%',
			'category_url' => '',
		];

		self::$product_url_template[$storefront] = wa()->getRouteUrl('shop/frontend/product', $route_params);

		$route_params['category_url'] = '%CATEGORY_URL%';
		self::$category_product_url_template[$storefront] = wa()->getRouteUrl('shop/frontend/product', $route_params);
	}

	private function getStorefront()
	{
		$routing = wa()->getRouting();

		return $routing->getDomain() . ifset($routing->getRoute(), 'url', '*');
	}
}