<?php

class shopBrandPluginBackendBrandReviewAddReplyController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$review_id = $this->getReviewId();
		$reply_text = $this->getReviewReplyText();

		$review_storage = new shopBrandBrandReviewStorage();

		$review = $review_storage->getById($review_id);

		$user = wa()->getUser();

		$reply = array(
			'parent_id' => $review->id,
			'brand_id' => $review->brand_id,
			'datetime' => date('Y-m-d H:i:s'),
			'status' => shopBrandBrandReview::STATUS_PUBLISHED,
			'title' => '',
			'content' => $reply_text,
			'contact_id' => $user->getId(),
			'contact_name' => $user->getName(),
			'contact_email' => $this->getEmail($user),
			'contact_phone' => $this->getPhone($user),
			'auth_type' => shopBrandBrandReview::AUTH_AUTH,
			'ip' => ip2long(waRequest::getIp()),
			'replies' => array(),
		);

		$reply_id = $review_storage->addReply($review_id, $reply);

		$reply['id'] = $reply_id;

		$this->response = array(
			'reply' => $reply,
		);
	}

	private function getReviewId()
	{
		$review_id = waRequest::post('review_id');

		if (wa_is_int($review_id) && $review_id > 0)
		{
			return $review_id;
		}

		throw new waException('invalid review id');
	}

	private function getReviewReplyText()
	{
		$reply_text = waRequest::post('reply_text');

		if ($reply_text !== null)
		{
			return (string)$reply_text;
		}

		throw new waException('invalid review reply text');
	}

	/**
	 * @param waUser $user
	 * @return mixed
	 */
	private function getEmail($user)
	{
		$first = $user->getFirst('email');

		return $first && isset($first['value']) ? $first['value'] : '';
	}

	/**
	 * @param waUser $user
	 * @return mixed
	 */
	private function getPhone($user)
	{
		$first = $user->getFirst('phone');

		return $first && isset($first['value']) ? $first['value'] : '';
	}
}
