<?php

/**
 * Class shopBrandBrandReview
 *
 * @property int $id
 * @property int $left_key
 * @property int $right_key
 * @property int $depth
 * @property int $parent_id
 * @property int $brand_id
 * @property array|null $product
 * @property string $datetime
 * @property string $status
 * @property string $title
 * @property string $content
 * @property string $pros
 * @property string $cons
 * @property int $rate
 * @property int $contact_id
 * @property string $contact_name
 * @property string $contact_email
 * @property string $contact_phone
 * @property string $auth_type
 * @property int $ip
 * @property array $images
 *
 * @property shopBrandBrandReview[] $replies
 * @property shopBrandBrandReview[] $contact
 */
class shopBrandBrandReview extends shopBrandPropertyAccess
{
	const STATUS_PUBLISHED = 'PUBLISHED';
	const STATUS_MODERATION = 'MODERATION';
	const STATUS_DELETED = 'DELETED';

	const AUTH_AUTH = 'AUTH';
	const AUTH_GUEST = 'GUEST';

	/** @var shopBrandBrandReview[] */
	private $_review_replies;

	/** @var waContact */
	private $_contact;

	private $_product;

	public function __construct($entity_array = null, $review_replies = null)
	{
		parent::__construct($entity_array);

		$this->_review_replies = is_array($review_replies)
			? $review_replies
			: array();

		$this->_contact = new waContact($this->contact_id);

		if (!$this->_contact->exists())
		{
			$this->_contact->set('name', $this->contact_name);
			$this->_contact->set('email', $this->contact_email);
			$this->_contact->set('phone', $this->contact_phone);
		}

		if (array_key_exists('product', $this->_entity_array))
		{
			$this->_product = $this->_entity_array['product'];
		}
	}

	public function assoc()
	{
		$review_assoc = parent::assoc();

		$review_assoc['replies'] = array();
		foreach ($this->_review_replies as $reply)
		{
			$review_assoc['replies'][] = $reply->assoc();
		}

		return $review_assoc;
	}

	protected function getEntityFieldValue($name)
	{
		if ($name == 'replies')
		{
			return $this->_review_replies;
		}
		elseif ($name == 'contact')
		{
			return $this->_contact;
		}
		elseif ($name == 'product')
		{
			return $this->_product;
		}
		elseif ($name === 'images')
		{
			return array_key_exists('images', $this->_entity_array)
				? $this->_entity_array['images']
				: array();
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
			'datetime' => date('Y:m:d H:i:s'),
			'status' => self::STATUS_PUBLISHED,
			'title' => '',
			'pros' => '',
			'cons' => '',
			'content' => '',
			'rate' => null,
			'contact_id' => null,
			'contact_name' => '',
			'contact_email' => '',
			'contact_phone' => '',
			'auth_type' => self::AUTH_GUEST,
			'ip' => waRequest::getIp(true),
		);
	}

	public function offsetExists($offset)
	{
		if ($offset == 'replies' || $offset == 'product' || $offset == 'images')
		{
			return true;
		}
		else
		{
			return parent::offsetExists($offset);
		}
	}
}