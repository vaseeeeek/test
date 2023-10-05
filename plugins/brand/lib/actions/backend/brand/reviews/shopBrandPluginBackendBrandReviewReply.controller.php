<?php

class shopBrandPluginBackendBrandReviewReplyController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');

		$state = json_decode($state_json, true);

		$review_id = $this->getReviewId($state);
		$reply = $this->getReply($state);

		$review_storage = new shopBrandBrandReviewStorage();

		$review_storage->addReply($review_id, $reply);
	}

	/**
	 * @param array $state
	 * @return int
	 * @throws waException
	 */
	private function getReviewId($state)
	{
		if (array_key_exists('review_id', $state) && wa_is_int($state['review_id']) && $state['review_id'] > 0)
		{
			return (int)$state['review_id'];
		}

		throw new waException('invalid review id');
	}

	/**
	 * @param array $state
	 * @return array
	 * @throws waException
	 */
	private function getReply($state)
	{
		$reply_attributes = ifset($state['reply']);

		if (is_array($reply_attributes))
		{
			//return new shopBrandBrandReview($reply_attributes);
			return $reply_attributes;
		}

		throw new waException('invalid reply');
	}
}
