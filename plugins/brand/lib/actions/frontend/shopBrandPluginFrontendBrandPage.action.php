<?php

class shopBrandPluginFrontendBrandPageAction extends shopBrandFrontendAction
{
	protected $settings;

	private $brand_storage;
	private $page_storage;
	private $brand_page_storage;
	private $storefront_template_layout_storage;

	/** @var shopBrandBrand */
	private $brand;
	/** @var shopBrandPage */
	private $page;
	/** @var shopBrandBrandPage|null */
	private $brand_page;
	/** @var shopBrandPage[] */
	private $pages;

	/** @var shopBrandBrandReviewsCollection */
	private $reviews_collection;
	private $reviews_count = 0;

	protected $storefront;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$settings_storage = new shopBrandSettingsStorage();
		$this->settings = $settings_storage->getSettings();

		$this->brand_storage = new shopBrandBrandStorage();
		$this->page_storage = new shopBrandPageStorage();
		$this->brand_page_storage = new shopBrandBrandPageStorage();
        $this->storefront_template_layout_storage = new shopBrandStorefrontTemplateLayoutStorage();

		$this->storefront = shopBrandHelper::getStorefront();
	}

	/**
	 * @return shopBrandBrand
	 */
	public function getBrand()
	{
		return $this->brand;
	}

	/**
	 * @return shopBrandPage
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @return shopBrandPage[]
	 */
	public function getPages()
	{
		return $this->pages;
	}

	/**
	 * @return null|shopBrandBrandPage
	 */
	public function getBrandPage()
	{
		return $this->brand_page;
	}

	/**
	 * @return shopBrandBrandReviewsCollection
	 */
	public function getReviewsCollection()
	{
		return $this->reviews_collection;
	}

	public function getReviewsCount()
	{
		return $this->reviews_count;
	}

	public function addReviewCaptchaIsDisabled()
	{
		return $this->settings->disable_add_review_captcha;
	}

	/**
	 * @throws waException
	 */
	protected function preExecute()
	{
		$brand_url = waRequest::param('brand');
		$page_url = waRequest::param('brand_page', null);

		$this->tryRedirectToCanonicalUrl($brand_url, $page_url);

		$this->initBrand($brand_url);

		$this->initReviewsCollection($this->brand->id);

		$this->initPage($page_url);
		$this->initBrandPage($this->brand->id, $this->page->id);

		$this->initPages($this->brand->id);

		$this->updateParams();

		$info = wa('shop')->getConfig()->getPluginInfo('brand');
		$this->view->assign('asset_version', waSystemConfig::isDebug() ? time() : $info['version']);

		$route_params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'addReviewPage',
			'brand' => $this->brand->url,
		);
		$routeUrl = wa()->getRouteUrl('shop', $route_params);
		$this->view->assign('add_review_page_url', $routeUrl);
	}

	public function execute()
	{
		parent::execute();

		$this->assignBrand();
		$this->assignPages();
		$this->assignReviewsCount();

		try
		{
			$this->executePageAction();
		}
		catch (waException $e)
		{
			throw new waException($e->getMessage(), 404);
		}
	}

	private function initReviewsCollection($brand_id)
	{
		$this->reviews_collection = new shopBrandBrandReviewSmartCollection($brand_id);

		$this->reviews_count = $this->reviews_collection->count();
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

	/**
	 * @param $page_url
	 * @throws waException
	 */
	private function initPage($page_url)
	{
		if ($page_url === '')
		{
			throw new waException("empty page url", 404);
		}
		elseif ($page_url === null)
		{
			$page = $this->page_storage->getByUrl('');
		}
		else
		{
			$page = $this->page_storage->getByUrl($page_url);
		}

		if (!$page)
		{
			throw new waException("unknown page url [{$page_url}]", 404);
		}

		$page_status_options = new shopBrandPageStatusEnumOptions();
		if ($page->status != $page_status_options->PUBLISHED)
		{
			throw new waException("page with id [{$page->id}] is not published", 404);
		}

		$page_types = new shopBrandPageTypeEnumOptions();
		if ($page->type == $page_types->REVIEWS && $this->settings->hide_reviews_tab_if_empty && $this->reviews_count == 0)
		{
			throw new waException("no reviews", 404);
		}

		$this->page = $page;
	}

	private function initBrandPage($brand_id, $page_id)
	{
        $brand_page_storefront = $this->storefront_template_layout_storage->getBrandPageMeta($this->storefront, $page_id, $brand_id);
        if (!$brand_page_storefront || strlen(trim($brand_page_storefront->content)) == 0) {
            $brand_page_storage = new shopBrandBrandPageStorage();

            $this->brand_page = $brand_page_storage->getPage($brand_id, $page_id);
        } else {
            $this->brand_page = $brand_page_storefront;
        }
	}

	private function initPages($brand_id)
	{
		$pages = array();

		$page_status_options = new shopBrandPageStatusEnumOptions();
		$page_type_options = new shopBrandPageTypeEnumOptions();

		foreach ($this->page_storage->getAll() as $page)
		{
			if (!$page->isMain())
			{
				if ($page->status != $page_status_options->PUBLISHED)
				{
					continue;
				}

				if ($page->type == $page_type_options->PAGE)
				{
				    $brand_page_storefront = $this->storefront_template_layout_storage->getBrandPageMeta($this->storefront, $page->id, $brand_id);
                    if (!$brand_page_storefront || strlen(trim($brand_page_storefront->content)) == 0) {
                        $brand_page = $this->brand_page_storage->getPage($brand_id, $page->id);
                        if (!$brand_page || strlen(trim($brand_page->content)) == 0) {
                            continue;
                        }
                    }
				}
				elseif ($page->type == $page_type_options->CATALOG)
				{
				}
				elseif ($page->type == $page_type_options->REVIEWS)
				{
					if ($this->settings->hide_reviews_tab_if_empty && $this->reviews_count == 0)
					{
						continue;
					}
				}
			}

			$page->is_reviews_page = $page->type == $page_type_options->REVIEWS;

			$pages[] = $page;
		}

		$this->pages = $pages;
	}

	private function tryRedirectToCanonicalUrl($brand_url, $page_url)
	{
		$perform_redirect = false;

		$brand_url_canonical = shopBrandHelper::toCanonicalUrl($brand_url);
		$page_url_canonical = shopBrandHelper::toCanonicalUrl($page_url);

		if ($brand_url != $brand_url_canonical)
		{
			$brand = $this->brand_storage->getByUrl($brand_url_canonical);
			if (!$brand || !$brand->is_shown)
			{
				throw new waException("unknown brand url [{$brand_url_canonical}]", 404);
			}

			$perform_redirect = true;
		}
		else
		{
			$brand = $this->brand_storage->getByUrl($brand_url);

			if ($brand && $brand->url != $brand_url)
			{
				$brand_url_canonical = $brand->url;
				$perform_redirect = true;
			}
		}

		if ($page_url != $page_url_canonical)
		{
			$page = $this->page_storage->getByUrl($page_url_canonical);
			if (!$page)
			{
				throw new waException("unknown page url [{$page_url_canonical}]", 404);
			}

			$perform_redirect = true;
		}

		if ($perform_redirect)
		{
			$routing = wa()->getRouting();

			$new_route_params = array(
				'plugin' => 'brand',
				'module' => 'frontend',
				'action' => 'brandPage',
				'brand' => $brand_url_canonical,
				//'brand_page' => $page_url_canonical,
			);

			if (is_string($page_url_canonical) && trim($page_url_canonical) !== '')
			{
				$new_route_params['brand_page'] = $page_url_canonical;
			}

			$this->redirect($routing->getUrl('shop', $new_route_params), 301);
		}
	}

	private function assignBrand()
	{
		$this->view->assign('brand', $this->brand);
	}

	private function assignPages()
	{
		$this->view->assign('page', $this->page);
		//$this->view->assign('page', waRequest::get('page', 1, waRequest::TYPE_INT));
		$this->view->assign('pages', $this->pages);
	}

	private function assignReviewsCount()
	{
		$this->view->assign('reviews_count', $this->reviews_collection->count());
	}

	/**
	 * @throws waException
	 */
	private function executePageAction()
	{
		$factory = new shopBrandBrandPageActionFactory();

		$page_content_action = $factory->getPageAction($this);

		$page_general_content = $page_content_action->display(false);

		$this->view->assign('page_general_content', $page_general_content);
	}

	private function updateParams()
	{
		waRequest::setParam('brand_plugin_brand', $this->brand);
		waRequest::setParam('brand_plugin_page', $this->page);
		waRequest::setParam('brand_plugin_brand_page', $this->brand_page);
	}

	protected function getActionTemplate()
	{
		return new shopBrandBrandPageActionTemplate($this->getTheme());
	}
}
