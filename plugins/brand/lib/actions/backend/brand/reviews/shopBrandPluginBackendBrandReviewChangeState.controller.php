<?php

class shopBrandPluginBackendBrandReviewChangeStateController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$review_id = $this->getReviewId();
		$status = $this->getReviewStatus();

		$review_storage = new shopBrandBrandReviewStorage();

		if ($status == shopBrandBrandReview::STATUS_PUBLISHED)
		{
			$review_storage->publishReview($review_id);
		}
		elseif ($status == shopBrandBrandReview::STATUS_DELETED)
		{
			$review_storage->deleteReview($review_id);
		}
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

	private function getReviewStatus()
	{
		$status = waRequest::post('status');

		if (
			$status == shopBrandBrandReview::STATUS_PUBLISHED
			|| $status == shopBrandBrandReview::STATUS_DELETED
			|| $status == shopBrandBrandReview::STATUS_MODERATION
		)
		{
			return $status;
		}

		throw new waException('invalid review status');
	}
}
