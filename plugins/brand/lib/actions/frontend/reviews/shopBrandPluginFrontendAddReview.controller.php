<?php

class shopBrandPluginFrontendAddReviewController extends waJsonController
{
	private $settings;

	public function __construct()
	{
		$settings_storage = new shopBrandSettingsStorage();
		$this->settings = $settings_storage->getSettings();
	}

	public function execute()
	{
		if (!$this->settings->is_enabled)
		{
			return;
		}

		$review = $this->getReview();

		if (count($this->errors))
		{
			return;
		}

		$review_storage = new shopBrandBrandReviewStorage();
		$user = wa()->getUser();

		if (waRequest::post('update_review') && $user->isAuth())
		{
			$existing_review = $review_storage->getUserBrandReview($user->getId(), $review['brand_id']);

			if ($existing_review)
			{
				$update_result = $review_storage->updateReview($existing_review->id, $review);

				if ($update_result)
				{
					$this->response['msg'] = 'Ваш отзыв обновлён';
				}
				else
				{
					$this->errors['save'] = 'Отзыв не обновлён';
				}

				return;
			}
		}


		if ($review_id = $review_storage->addReview($review))
		{
			$this->response['msg'] = 'Ваш отзыв добавлен';
		}
		else
		{
			$this->errors['save'] = 'Отзыв не сохранен';
		}
	}

	private function getReview()
	{
		$review = waRequest::post('review');

		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();

		if (!$this->settings->disable_add_review_captcha && $config->getGeneralSettings('require_captcha') && !wa()->getCaptcha()->isValid())
		{
			$this->errors['captcha'] = 'Неверный код в captcha';
		}

		$review['name'] = strip_tags($review['name']);
		$review['pros'] = strip_tags($review['pros']);
		$review['cons'] = strip_tags($review['cons']);
		$review['content'] = strip_tags($review['content']);

		if (!$review['rate']) {
			$this->errors['rate'] = 'Пожалуйста поставьте оценку';
		}
		if (!$review['pros']) {
			$this->errors['pros'] = 'Заполните поле';
		}
		if (!$review['cons']) {
			$this->errors['cons'] = 'Заполните поле';
		}

		$user = wa()->getUser();
		if ($user->isAuth())
		{
			$review['auth_type'] = shopBrandBrandReview::AUTH_AUTH;
			$review['contact_id'] = $user->getId();
			$review['contact_name'] = $review['name'] ? $review['name'] : $user->getName();
			$review['contact_email'] = ifset($review['email']);
			$review['contact_phone'] = ifset($review['phone']);
		}
		else
		{
			if (!$review['name']) {
				$this->errors['name'] = 'Введите имя';
			}
			$review['auth_type'] = shopBrandBrandReview::AUTH_GUEST;
			$review['contact_name'] = $review['name'];
		}

		$status = $this->settings->new_review_status;

		$review['status'] = $status ? $status : shopBrandBrandReview::STATUS_PUBLISHED;

		return $review;
	}
}