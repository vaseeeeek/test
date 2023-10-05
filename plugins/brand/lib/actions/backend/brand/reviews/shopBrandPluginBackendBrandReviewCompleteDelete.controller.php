<?php

class shopBrandPluginBackendBrandReviewCompleteDeleteController extends shopBrandWaBackendJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$review_id = waRequest::post('review_id');
		$storage = new shopBrandBrandReviewStorage();
		$storage->completeDeleteReview($review_id);

		$this->response['success'] = true;
	}
}
