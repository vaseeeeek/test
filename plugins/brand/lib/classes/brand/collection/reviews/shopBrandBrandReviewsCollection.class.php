<?php

class shopBrandBrandReviewsCollection
{
	private $review_model;
	private $reviews_storage;
	/** @var shopProductReviewsImagesModel|null */
	private $product_reviews_images_model;

	private $brand_id;

	private $in_stock_only = false;
	private $with_product_reviews = false;
	private $published_only = false;
	private $with_descendants = false;
	private $filter_empty_reviews = false;
	private $sort = '';
	private $sort_order = 'DESC';
	private $image_sizes;

	public function __construct($route = null)
	{
		$this->review_model = new shopBrandBrandReviewModel();
		$this->reviews_storage = new shopBrandBrandReviewStorage();

		if (is_array($route) && array_key_exists('drop_out_of_stock', $route))
		{
			$this->in_stock_only = $route['drop_out_of_stock'] == '2';
		}

		if (class_exists('shopProductReviewsImagesModel'))
		{
			$this->product_reviews_images_model = new shopProductReviewsImagesModel();
		}

		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();
		$this->image_sizes = $config->getImageSizes();
	}

	/**
	 * @return shopBrandBrandReview[]
	 */
	public function getReviews()
	{
		$reviews = array();

		if ($this->with_product_reviews && $this->brand_id > 0)
		{
			try
			{
				$brand_feature = shopBrandHelper::getBrandFeature();
			}
			catch (waException $e)
			{
				return array();
			}

			$hash = 'search/' . $brand_feature['code'] . '.value_id=' . $this->brand_id;
			$product_collection = new shopProductsCollection($hash);

			$product_collection->addWhere('r.parent_id = 0');

			if ($this->published_only)
			{
				$product_collection->addWhere('r.`status` = \'' . shopProductReviewsModel::STATUS_PUBLISHED . '\'');
			}

			if ($this->filter_empty_reviews)
			{
				$product_collection->addWhere('r.`text` != \'\'');
			}

			if ($this->in_stock_only)
			{
				$product_collection->filters(array('in_stock_only' => true));
			}

			$sql = $product_collection->getSQL();

			$product_reviews_sql = "
SELECT
	r.*,
	p.id AS product_id,
	p.name AS product_name,
	p.url AS product_url,
	p.category_id AS product_category_id
FROM shop_product_reviews AS r
	JOIN shop_product AS p
		ON p.id = r.product_id
" . preg_replace('/FROM shop_product p/i', '', $sql, 1) . '
GROUP BY r.id
ORDER BY ' . $this->getProductReviewsSortColumn() . ' ' . $this->sort_order;

			$product_reviews_query = $this->review_model->query($product_reviews_sql);

			$review_ids = array();
			foreach ($product_reviews_query as $row)
			{
				try
				{
					$contact = new waContact($row['contact_id']);
				}
				catch (waException $e)
				{
					$contact = null;
				}

				$review_obj_params = array(
					'id' => $row['id'],
					'left_key' => $row['left_key'],
					'right_key' => $row['right_key'],
					'parent_id' => $row['parent_id'],
					'product_id' => $row['product_id'],
					'review_id' => $row['review_id'],
					'depth' => $row['depth'],
					'contact_name' => $contact && $contact->exists() ? $contact->getName() : $row['name'],
					'rate' => $row['rate'],
					'datetime' => $row['datetime'],
					'content' => $row['text'],
					'product' => array(
						'id' => $row['product_id'],
						'name' => $row['product_name'],
						'url' => $this->getProductUrl($row['product_url'], $row['product_category_id']),
					),
				);
				$review_ids[] = $row['id'];
				$review_replies = array();

				$review = new shopBrandBrandReview($review_obj_params, $review_replies);

				$reviews[] = $review;
			}

			if (isset($this->product_reviews_images_model) && count($review_ids) > 0)
			{
				$review_images = $this->product_reviews_images_model->getImages($review_ids, $this->image_sizes, 'review_id');
				foreach ($reviews as $review)
				{
					$review->images = array_key_exists($review->id, $review_images)
						? $review_images[$review->id]
						: array();
				}
			}
		}

		$query = $this->review_model
			->select('id')
			->where('parent_id = 0')
			->order($this->getBrandReviewsSortColumn() . ' ' . $this->sort_order);

		if ($this->brand_id > 0)
		{
			$query->where('brand_id = :brand_id', array('brand_id' => $this->brand_id));
		}

		if ($this->published_only)
		{
			$query->where('status = :status', array('status' => shopBrandBrandReview::STATUS_PUBLISHED));
		}

		foreach ($query->query() as $row)
		{
			$reviews[] = $this->with_descendants
				? (
				$this->published_only
					? $this->reviews_storage->getByIdWithPublishedDescendants($row['id'])
					: $this->reviews_storage->getByIdWithDescendants($row['id'])
				)
				: $this->reviews_storage->getById($row['id']);
		}

		return $reviews;
	}

	public function count()
	{
		$count = 0;

		if ($this->with_product_reviews && $this->brand_id > 0)
		{
			try
			{
				$brand_feature = shopBrandHelper::getBrandFeature();
			}
			catch (waException $e)
			{
				return 0;
			}

			$hash = 'search/' . $brand_feature['code'] . '.value_id=' . $this->brand_id;
			$product_collection = new shopProductsCollection($hash);

			$product_collection->addWhere('r.parent_id = 0');

			if ($this->published_only)
			{
				$product_collection->addWhere('r.`status` = \'' . shopProductReviewsModel::STATUS_PUBLISHED . '\'');
			}

			if ($this->filter_empty_reviews)
			{
				$product_collection->addWhere('r.`text` != \'\'');
			}

			if ($this->in_stock_only)
			{
				$product_collection->filters(array('in_stock_only' => true));
			}

			$sql = $product_collection->getSQL();

			$product_reviews_sql = '
SELECT COUNT(DISTINCT r.id)
FROM shop_product_reviews AS r
	JOIN shop_product AS p
		ON p.id = r.product_id
' . preg_replace('/FROM shop_product p/i', '', $sql, 1) . '
ORDER BY r.`datetime` DESC
';

			$count += (int)$this->review_model->query($product_reviews_sql)->fetchField();
		}

		$query = $this->review_model
			->select('COUNT(id)')
			->where('parent_id = 0')
			->order('datetime DESC');

		if ($this->brand_id > 0)
		{
			$query->where('brand_id = :brand_id', array('brand_id' => $this->brand_id));
		}

		if ($this->published_only)
		{
			$query->where('status = :status', array('status' => shopBrandBrandReview::STATUS_PUBLISHED));
		}

		return $count + (int)$query->fetchField();
	}

	public function getAverageRating()
	{
		$reviews = $this->getReviews();

		if (count($reviews) == 0)
		{
			return 0;
		}

		$rates_sum = 0;
		foreach ($reviews as $review)
		{
			$rates_sum += $review->rate;
		}

		return $rates_sum / count($reviews);
	}

	/**
	 * @return shopBrandBrandReviewsCollection
	 */
	public function addProductReviews()
	{
		$this->with_product_reviews = true;

		return $this;
	}

	/**
	 * @param int $brand_id
	 * @return shopBrandBrandReviewsCollection
	 */
	public function filterBrandId($brand_id)
	{
		$this->brand_id = $brand_id;

		return $this;
	}

	/**
	 * @return shopBrandBrandReviewsCollection
	 */
	public function publishedOnly()
	{
		$this->published_only = true;

		return $this;
	}

	/**
	 * @return shopBrandBrandReviewsCollection
	 */
	public function includeDescendants()
	{
		$this->with_descendants = true;

		return $this;
	}

	/**
	 * @return shopBrandBrandReviewsCollection
	 */
	public function filterEmptyReviews()
	{
		$this->filter_empty_reviews = true;

		return $this;
	}

	/**
	 * @param $sort
	 * @param string $order
	 * @return shopBrandBrandReviewsCollection
	 */
	public function sort($sort, $order = 'DESC')
	{
		$this->sort = $sort;
		$this->sort_order = strtoupper(trim($order)) == 'ASC' ? 'ASC' : 'DESC';

		return $this;
	}

	private function getProductUrl($url, $category_id)
	{
		$route_params['product_url'] = $url;

		if ($category_id)
		{
			$category_model = new shopCategoryModel();
			$url_field = waRequest::param('url_type') == '1' ? 'url' : 'full_url';
			$category_url = $category_model->select($url_field)->where('id = ' . $category_id)->fetchField();

			$route_params['category_url'] = $category_url ? $category_url : '';
		}
		else
		{
			$route_params['category_url'] = '';
		}

		return wa()->getRouteUrl('shop/frontend/product', $route_params);
	}

	private function getProductReviewsSortColumn()
	{
		if ($this->sort == 'rate')
		{
			return 'r.rate';
		}
		else
		{
			return 'r.`datetime`';
		}
	}

	private function getBrandReviewsSortColumn()
	{
		if ($this->sort == 'rate')
		{
			return 'rate';
		}
		else
		{
			return 'datetime';
		}
	}
}
