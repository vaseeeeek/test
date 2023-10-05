<?php

abstract class shopBrandBrandPageContentAction extends shopBrandFrontendActionWithMeta
{
	const BRAND_PAGE_ACTION_PARAM = 'brand_plugin_brand_page_action';
	const BRAND_PAGE_ASSIGN_PARAM = 'brand_plugin_brand_page_assign';

	private static $pages_tabs = null;

	/** @var shopBrandPluginFrontendBrandPageAction */
	protected $action;

	protected $brand;
	protected $page;
	protected $brand_page;

	protected $with_pages_tabs = true;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->layout = null;

		if (
			!is_array($params)
			|| !array_key_exists(self::BRAND_PAGE_ACTION_PARAM, $params)
			|| (!($params[self::BRAND_PAGE_ACTION_PARAM] instanceof shopBrandPluginFrontendBrandPageAction))
		)
		{
			throw new waException('param [' . self::BRAND_PAGE_ACTION_PARAM . '] is required');
		}

		$this->action = $params[self::BRAND_PAGE_ACTION_PARAM];

		$this->view = new waSmarty3View(waSystem::getInstance());
		$this->view->assign($this->action->view->getVars());

		$this->brand = $this->action->getBrand();
		$this->page = $this->action->getPage();
		$this->brand_page = $this->action->getBrandPage();
	}

	public function execute()
	{
		if ($this->isEmptyPage())
		{
			throw new waException('not found', 404);
		}

		if ($this->with_pages_tabs)
		{
			$this->initPagesTabs();
		}

		parent::execute();

		/** @var array $vars */
		$vars = $this->view->getVars();
		$var_names = $this->getMainViewVarNamesToReplace();

		foreach (array_keys($vars) as $var_name)
		{
			if (!array_key_exists($var_name, $var_names))
			{
				unset($vars[$var_name]);
			}
		}

		$this->action->view->assign($vars);
		$this->action->view->assign('pages_tabs', $this->getPagesTabs());
		$this->view->assign('pages_tabs', $this->getPagesTabs());
	}

	protected function getMainViewVarNamesToReplace()
	{
		return array(
			'h1' => 'h1',
		);
	}

	protected function getTemplateLayout()
	{
		$collector = new shopBrandTemplateCollector();

		return $collector->getBrandPageTemplateLayout(
			$this->brand->id,
			$this->page->id,
			$this->storefront
		);
	}

	protected function defaultMetaTitle()
	{
		return 'Бренд ' . $this->brand->name;
	}

	protected function getViewBufferTemplateVars()
	{
		$vars = array();

		$brand_field_storage = new shopBrandBrandFieldStorage();
		$brand_fields = $brand_field_storage->getBrandFieldValues($this->brand->id);

		$frontend_url = $this->brand->getFrontendUrl($this->page);

		$vars['brand'] = array(
			'id' => $this->brand->id,
			'name' => $this->brand->name,
			'url' => $this->brand->url,
			'image_url' => $this->brand->getImageUrl(),
			'frontend_url' => $frontend_url,
			'description_short' => $this->brand->description_short,
			'field' => $brand_fields,
			'fields' => $brand_fields,
		);
		$this->view_buffer->assign($vars);
        $template_layout = $this->getTemplateLayout();
        $template_layout_fetched = $this->view_buffer->fetchTemplateLayout($template_layout);
        if(is_string($template_layout_fetched->name) && trim($template_layout_fetched->name) != '') {
            $this->page->name = $template_layout_fetched->name;
        }
        $vars['page'] = array(
            'id' => $this->page->id,
            'name' => $this->page->name,
            'url' => $this->page->url,
            'frontend_url' => $frontend_url,
        );

		return shopBrandHelper::mergeViewVarArrays(parent::getViewBufferTemplateVars(), $vars);
	}

	protected function isEmptyPage()
	{
		return false;
	}

	protected function getPagesTabs()
	{
		return self::$pages_tabs;
	}

	private function initPagesTabs()
	{
		if (self::$pages_tabs !== null)
		{
			return;
		}

		$action_params = array(
			shopBrandBrandPageContentAction::BRAND_PAGE_ACTION_PARAM => $this->action,
		);

		$action = new shopBrandBrandPagesTabsContentAction($action_params);

		self::$pages_tabs = $action->display(false);
	}
}