<?php

// todo collection
class shopBrandBrandReviewStorage
{
	private $review_model;
	private $review_accessible_meta;

	public function __construct()
	{
		$this->review_model = new shopBrandBrandReviewModel();

		$this->review_accessible_meta = $this->review_model->getMetadata();

		unset($this->review_accessible_meta[$this->review_model->getTableLeft()]);
		unset($this->review_accessible_meta[$this->review_model->getTableRight()]);
		unset($this->review_accessible_meta[$this->review_model->getTableDepth()]);
		unset($this->review_accessible_meta[$this->review_model->getTableParent()]);
	}

	/**
	 * @return shopBrandBrandReview[]
	 */
	public function getAll()
	{
		$reviews = array();
		$all = $this->review_model
			->select('*')
			->order('left_key DESC')
			->query();

		$prev_review = null;

		$children_buffer = array();

		foreach ($all as $review_assoc)
		{
			if ($prev_review === null)
			{
				$prev_review = $review_assoc;
				$children_buffer = array(new shopBrandBrandReview($review_assoc));

				continue;
			}

			if ($review_assoc['parent_id'] == $prev_review['parent_id'])
			{
				$children_buffer[] = new shopBrandBrandReview($review_assoc);
			}
			elseif ($review_assoc['id'] == $prev_review['parent_id'])
			{
				$parent_review = new shopBrandBrandReview($review_assoc, $children_buffer);

				$children_buffer = array($parent_review);
			}
			else
			{
				$reviews = array_merge($reviews, $children_buffer);

				$children_buffer = array(new shopBrandBrandReview($review_assoc));
			}

			$prev_review = $review_assoc;
		}

		if (count($children_buffer))
		{
			$reviews = array_merge($reviews, $children_buffer);
		}

		return $reviews;
	}

	public function getAllAssoc()
	{
		$reviews = $this->getAll();

		//wa_dump($reviews);

		$assoc = array();
		foreach ($reviews as $review)
		{
			$assoc[] = $review->assoc();
		}
		unset($review);

		return $assoc;
	}

	public function getById($review_id)
	{
		$review_row = $this->review_model->getById($review_id);

		return $review_row
			? new shopBrandBrandReview($review_row)
			: null;
	}

	public function getByIdWithDescendants($review_id)
	{
		$review_row = $this->review_model->getById($review_id);

		if (!$review_row)
		{
			return null;
		}

		$review_replies = $this->review_model
			->descendants($review_id)
			->order('right_key DESC')
			->fetchAll();

		return new shopBrandBrandReview($review_row, $review_replies);
	}

	public function getByIdWithPublishedDescendants($review_id)
	{
		$review_row = $this->review_model->getById($review_id);

		if (!$review_row)
		{
			return null;
		}

		$review_replies = $this->review_model
			->descendants($review_id)
			->where('status = :status', array('status' => shopBrandBrandReview::STATUS_PUBLISHED))
			->order('right_key DESC')
			->fetchAll('id');

		return new shopBrandBrandReview($review_row, $review_replies);
	}

	public function getForBrand($brand_id)
	{
		$query = $this->review_model
			->select('id')
			->where('parent_id = 0')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->order('datetime DESC');

		$reviews = array();
		foreach ($query->query() as $row)
		{
			$reviews[] = $this->getByIdWithDescendants($row['id']);
		}

		return $reviews;
	}

	public function getPublishedForBrand($brand_id)
	{
		$query = $this->review_model
			->select('id')
			->where('parent_id = 0')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->where('status = :status', array('status' => shopBrandBrandReview::STATUS_PUBLISHED))
			->order('datetime DESC');

		$reviews = array();
		foreach ($query->query() as $row)
		{
			$reviews[] = $this->getByIdWithDescendants($row['id']);
		}

		return $reviews;
	}

	/**
	 * @param $review
	 * @return int
	 */
	public function addReview($review)
	{
		$review_to_add = $this->prepare($review);

		return $this->review_model->add($review_to_add);
	}

	/**
	 * @param $review_id
	 * @param $reply
	 * @return int
	 */
	public function addReply($review_id, $reply)
	{
		$review_to_add = $this->prepare($reply);

		return $this->review_model->add($review_to_add, $review_id);
	}

	public function publishReview($review_id)
	{
		return $this->review_model->updateById($review_id, array(
			'status' => shopBrandBrandReview::STATUS_PUBLISHED,
		));
	}

	public function deleteReview($review_id)
	{
		return $this->review_model->updateById($review_id, array(
			'status' => shopBrandBrandReview::STATUS_DELETED,
		));
	}

	public function completeDeleteReview($review_id)
	{
		$review = $this->getById($review_id);

		if (!$review)
		{
			return false;
		}

		$delete_sql = '
DELETE FROM ' . $this->review_model->getTableName() . '
WHERE left_key >= :left_key AND right_key <= :right_key 
';
		$query_params = array(
			'left_key' => $review->left_key,
			'right_key' => $review->right_key,
		);

		$this->review_model->exec($delete_sql, $query_params);

		return true;
	}

	/**
	 * @param int $review_id
	 * @param array $data
	 * @return bool
	 */
	public function updateReview($review_id, $data)
	{
		$prepared = $this->prepare($data);

		return $this->review_model->updateById($review_id, $prepared);
	}

	public function count($brand_id)
	{
		return (int) $this->review_model
			->select('COUNT(id)')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->where('status = :status', array('status' => shopBrandBrandReview::STATUS_PUBLISHED))
			->fetchField();
	}

	public function getUserBrandReview($user_id, $brand_id)
	{
		$review_raw = $this->review_model
			->select('*')
			->where('brand_id = :brand_id', array('brand_id' => $brand_id))
			->where('contact_id = :contact_id', array('contact_id' => $user_id))
			->where('parent_id = 0')
			->where('status = :status', array('status' => shopBrandBrandReview::STATUS_PUBLISHED))
			->fetchAssoc();

		return $review_raw ? new shopBrandBrandReview($review_raw) : null;
	}

	/**
	 * @param array $review
	 * @return array
	 */
	private function prepare($review)
	{
		$review_obj = new shopBrandBrandReview($review);

		$review_to_add = $review_obj->assoc();

		foreach (array_keys($review_to_add) as $field)
		{
			if (!array_key_exists($field, $this->review_accessible_meta))
			{
				unset($review_to_add[$field]);
			}
		}

		unset($review_to_add['id']);

		return $review_to_add;
	}
}
