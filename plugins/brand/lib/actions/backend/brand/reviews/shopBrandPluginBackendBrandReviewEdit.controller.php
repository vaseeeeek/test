<?php

class shopBrandPluginBackendBrandReviewEditController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$state_json = waRequest::post('state');

		$state = json_decode($state_json, true);

		$review_id = $this->getReviewId($state);
		$review = $this->getReview($state);

		$review_storage = new shopBrandBrandReviewStorage();

		$review_storage->updateReview($review_id, $review);
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
	private function getReview($state)
	{
		$review_attributes = ifset($state['review']);

		if (is_array($review_attributes))
		{
			//return new shopBrandBrandReview($review_attributes);
			return $review_attributes;
		}

		throw new waException('invalid review');
	}
}
