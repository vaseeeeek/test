<?php

class shopBrandPluginFrontendAddInstantReviewRatingController extends waJsonController
{
	public function execute()
	{
		$review = waRequest::post('review', array(), waRequest::TYPE_ARRAY);

		$brand_id = ifset($review['brand_id']);
		$rating = ifset($review['rate']);

		$user = wa()->getUser();
		if (!$user->isAuth())
		{
			$this->errors['user'] = 'Требуется авторизация';

			return;
		}

		$user_id = $user->getId();

		$storage = new shopBrandBrandReviewStorage();

		$review = $storage->getUserBrandReview($user_id, $brand_id);

		if ($review)
		{
			$review->rate = $rating;

			$storage->updateReview($review->id, $review->assoc());
		}
		else
		{
			$review_assoc = array();

			$review_assoc['brand_id'] = $brand_id;
			$review_assoc['contact_id'] = $user_id;
			$review_assoc['contact_name'] = $user->getName();
			$review_assoc['contact_email'] = $user->get('email', 'default');
			$review_assoc['contact_phone'] = $user->get('phone', 'default');
			$review_assoc['auth_type'] = shopBrandBrandReview::AUTH_GUEST;
			$review_assoc['status'] = shopBrandBrandReview::STATUS_PUBLISHED;
			$review_assoc['rate'] = $rating;

			$storage->addReview($review_assoc);
		}
	}
}