<?php

class shopProductgroupViewHelper
{
	private static $is_enabled;

	public static function getGroupsBlock($product_or_id)
	{
		$product_id = self::getProductId($product_or_id);

		if (!$product_id || !self::isPluginEnabled())
		{
			return '';
		}

		return self::renderGroups($product_id);
	}

	/**
	 * @param $product_or_id
	 * @param int|int[] $group_ids
	 * @return string
	 */
	public static function getSpecificGroupsBlock($product_or_id, $group_ids)
	{
		$product_id = self::getProductId($product_or_id);

		if (!$product_id || !self::isPluginEnabled())
		{
			return '';
		}

		$group_ids = self::prepareGroupIds($group_ids);

		return self::renderGroups($product_id, $group_ids);
	}

	/**
	 * блок группы товаров для страницы категории
	 *
	 * @param array|int $product_or_id
	 * @param int[]|null $group_ids выводимые группы
	 * @param array|null $products массив товаров на текущей страницы. используется для оптимизации. по умолчанию берется значение переменной $products из шаблона
	 * @return string
	 */
	public static function getCategoryProductBlock($product_or_id, $group_ids = null, $products = null)
	{
		$product_id = self::getProductId($product_or_id);

		if (!$product_id || !self::isPluginEnabled())
		{
			return '';
		}

		if ($group_ids !== null)
		{
			$group_ids = self::prepareGroupIds($group_ids);
		}

		if (!is_array($products))
		{
			$products = self::getViewProducts();
		}

		return self::renderCategoryGroups($product_id, $group_ids, $products);
	}

	/**
	 * @param int|array|shopProduct $product_or_id
	 * @return array
	 * @throws waException
	 * @deprecated
	 */
	public static function getGroups($product_or_id)
	{
		$product_id = self::getProductId($product_or_id);

		if (!$product_id || !self::isPluginEnabled())
		{
			return [];
		}

		return shopProductgroupGroupsBlockHelper::getGroups($product_id);
	}

	/**
	 * @param int $product_id
	 * @param int[]|null $group_ids
	 * @return string
	 * @throws waException
	 */
	private static function renderGroups($product_id, $group_ids = null)
	{
		$theme_id = waRequest::isMobile() && waRequest::param('theme_mobile')
			? waRequest::param('theme_mobile')
			: waRequest::param('theme');

		if (!$theme_id)
		{
			return '';
		}

		$view = new shopProductgroupWaView(wa()->getView());

		$renderer = new shopProductgroupGroupsBlockRenderer($view);

		if (is_array($group_ids))
		{
			if (count($group_ids) === 0)
			{
				return '';
			}

			return $renderer->renderSpecificGroupsBlock($product_id, $theme_id, $group_ids);
		}
		else
		{
			return $renderer->renderGroupsBlock($product_id, $theme_id);
		}
	}

	private static function renderCategoryGroups($product_id, $group_ids, $products)
	{
		$theme_id = waRequest::isMobile() && waRequest::param('theme_mobile')
			? waRequest::param('theme_mobile')
			: waRequest::param('theme');

		if (!$theme_id)
		{
			return '';
		}

		$view = new shopProductgroupWaView(wa()->getView());

		$renderer = new shopProductgroupGroupsBlockRenderer($view);

		if (is_array($group_ids))
		{
			if (count($group_ids) === 0)
			{
				return '';
			}

			return $renderer->renderSpecificCategoryGroupsBlock($product_id, $products, $theme_id, $group_ids);
		}
		else
		{
			return $renderer->renderCategoryGroupsBlock($product_id, $products, $theme_id);
		}
	}

	/** @return bool */
	private static function isPluginEnabled()
	{
		if (self::$is_enabled === null)
		{
			try
			{
				/** @var shopProductgroupPlugin $plugin */
				$plugin = wa('shop')->getPlugin('productgroup');
			}
			catch (waException $e)
			{
				return false;
			}

			self::$is_enabled = $plugin->getPluginEnv()->plugin_config->is_enabled;
		}

		return self::$is_enabled;
	}

	private static function getProductId($product_or_id)
	{
		if (wa_is_int($product_or_id))
		{
			return intval($product_or_id);
		}

		if (is_object($product_or_id) && ($product_or_id instanceof shopProduct))
		{
			return $product_or_id->id;
		}

		if (is_array($product_or_id) && isset($product_or_id['id']))
		{
			return $product_or_id['id'];
		}

		return null;
	}

	/**
	 * @param int|int[] $group_ids
	 * @return array
	 */
	private static function prepareGroupIds($group_ids)
	{
		if (is_array($group_ids))
		{
			$group_ids_filtered = [];
			$map = [];
			foreach ($group_ids as $group_id)
			{
				if (wa_is_int($group_id) && $group_id > 0)
				{
					$group_id = intval($group_id);

					if (!isset($map[$group_id]))
					{
						$group_ids_filtered[] = $group_id;
						$map[$group_id] = true;
					}
				}
			}

			$group_ids = $group_ids_filtered;
		}
		else
		{
			$group_ids = wa_is_int($group_ids)
				? [$group_ids]
				: [];
		}

		return $group_ids;
	}

	private static function getViewProducts()
	{
		return wa()->getView()->getVars('products');
	}
}
