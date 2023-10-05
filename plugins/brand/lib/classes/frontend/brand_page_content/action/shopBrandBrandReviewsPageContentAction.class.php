<?php

class shopBrandBrandReviewsPageContentAction extends shopBrandBrandPageContentAction
{
	protected function executeBrandPage(shopBrandFetchedLayout $fetched_layout)
	{
		$this->getResponse()->addJs('js/rate.widget.js', 'shop');

		$route_params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'addReview',
		);
		$routing = wa()->getRouting();

		$add_review_url = $routing->getUrl('shop', $route_params);

		$sort = waRequest::request('sort');
		$order = waRequest::request('order');
		$reviews = $this->action->getReviewsCollection()
			->sort($sort, $order)
			->getReviews();

		$fetched_h1 = $fetched_layout->h1;

		$this->view->assign('disable_add_review_captcha', $this->action->addReviewCaptchaIsDisabled());
		$this->view->assign('h1', $fetched_h1 ? $fetched_h1 : 'Отзывы о бренде');
		$this->view->assign('reviews', $reviews);
		$this->view->assign('add_review_url', $add_review_url);
	}

	protected function getViewBufferTemplateVars()
	{
		$vars = array();

		$vars['brand']['reviews_count'] = $this->action->getReviewsCollection()->count();

		return shopBrandHelper::mergeViewVarArrays(parent::getViewBufferTemplateVars(), $vars);
	}

	protected function getActionTemplate()
	{
		return new shopBrandBrandReviewsActionTemplate($this->getTheme());
	}

	protected function getTemplateLayout()
	{
		$template_layout = parent::getTemplateLayout();
		$templates = $template_layout->getTemplates();

		$fields_to_check = array(
			'h1',
			'meta_title',
		);

		foreach ($fields_to_check as $field)
		{
			if (!is_string($templates[$field]) || mb_strlen(trim($templates[$field])) == 0)
			{
				$templates[$field] = $this->page->name;
			}
		}

		return new shopBrandTemplateLayout($templates);
	}
}