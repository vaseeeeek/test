<?php

/**
 * @deprecated
 */
class shopProductgroupGroupsBlockHelper
{
	/**
	 * @param int $product_id
	 * @return array
	 * @throws waException
	 */
	public static function getGroups($product_id)
	{
		$groups_collection = new shopProductgroupWaProductGroupsCollection($product_id);

		$view_groups = [];
		foreach ($groups_collection->getGroups() as $products_group)
		{
			$elements = [];

			foreach ($products_group->group_products as $product)
			{
				$label = isset($products_group->product_labels[$product['id']])
					? $products_group->product_labels[$product['id']]
					: $product['name'];

				$elements[] = [
					'product' => $product,
					'label' => $label,
					'url' => $product['frontend_url'],
					'image_frontend_url' => self::getProductImage($product),
				];
			}

			$view_groups[] = [
				'group' => $products_group->group->toAssoc(),
				'products' => $elements,
			];
		}

		return $view_groups;
	}

	private static function getProductImage($product)
	{
		if (!$product['image_id'])
		{
			return null;
		}

		$image_assoc = [
			'id' => $product['image_id'],
			'product_id' => $product['id'],
			'ext' => $product['ext'],
			'filename' => $product['image_filename'],
			'original_filename' => '',
		];

		return shopImage::getUrl($image_assoc, '60x60');
	}
}