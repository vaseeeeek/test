<?php

class shopBrandPluginFrontendAddReviewPageAction extends shopBrandFrontendActionWithMeta
{
	private $brand_storage;
	private $brand_review_storage;

	/** @var shopBrandBrand */
	private $brand;

	/** @var shopBrandBrandReview */
	private $existing_review = null;

	public function __construct($params = null)
	{
		$this->brand_storage = new shopBrandBrandStorage();
		$this->brand_review_storage = new shopBrandBrandReviewStorage();

		parent::__construct($params);
	}

	/**
	 * @throws waException
	 */
	protected function preExecute()
	{
		$brand_url = waRequest::param('brand');

		$this->tryRedirectToCanonicalUrl($brand_url);

		$this->initBrand($brand_url);

		$this->loadExistingUserReview($this->brand);

		$info = wa('shop')->getConfig()->getPluginInfo('brand');
		$this->view->assign('asset_version', waSystemConfig::isDebug() ? time() : $info['version']);
	}


	protected function executeBrandPage(shopBrandFetchedLayout $fetched_layout)
	{
		waRequest::setParam('brand_plugin_brand', $this->brand);

		$submit_review_action = wa()->getRouteUrl('shop', array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'addReview',
		));

		$submit_instant_review_action = wa()->getRouteUrl('shop', array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'addInstantReviewRating',
		));

		$this->view->assign('existing_review', $this->existing_review);
		$this->view->assign('brand', $this->brand);

		$this->view->assign('submit_review_action', $submit_review_action);
		$this->view->assign('submit_instant_review_action', $submit_instant_review_action);
	}


	protected function getTemplateLayout()
	{
		return new shopBrandTemplateLayout(array());
	}

	protected function getActionTemplate()
	{
		return new shopBrandAddBrandReviewActionTemplate($this->getTheme());
	}

	private function tryRedirectToCanonicalUrl($brand_url)
	{
		$perform_redirect = false;

		$brand_url_canonical = shopBrandHelper::toCanonicalUrl($brand_url);

		if ($brand_url != $brand_url_canonical)
		{
			$brand = $this->brand_storage->getByUrl($brand_url_canonical);
			if (!$brand || !$brand->is_shown)
			{
				throw new waException("unknown brand url [{$brand_url_canonical}]", 404);
			}

			$perform_redirect = true;
		}

		if ($perform_redirect)
		{
			$routing = wa()->getRouting();

			$new_route_params = array(
				'plugin' => 'brand',
				'module' => 'frontend',
				'action' => 'addReviewPage',
				'brand' => $brand_url_canonical,
			);

			$this->redirect($routing->getUrl('shop', $new_route_params, true), 301);
		}
	}

	/**
	 * @param $brand_url
	 * @throws waException
	 */
	private function initBrand($brand_url)
	{
		$brand = $this->brand_storage->getByUrl($brand_url);

		if (!$brand)
		{
			throw new waException("unknown brand url [{$brand_url}]", 404);
		}

		if (!$brand->is_shown)
		{
			throw new waException("brand with id [{$brand->id}] is disabled", 404);
		}

		$brand_field_storage = new shopBrandBrandFieldStorage();
		$brand_fields = $brand_field_storage->getBrandFieldValues($brand->id);
		$brand['field'] = $brand_fields;
		$brand['fields'] = $brand_fields;

		$this->brand = $brand;
	}

	private function loadExistingUserReview(shopBrandBrand $brand)
	{
		$user_id = wa()->getUser()->getId();

		$this->existing_review = $user_id > 0
			? $this->brand_review_storage->getUserBrandReview($user_id, $brand->id)
			: null;
	}
}