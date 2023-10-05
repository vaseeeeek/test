<?php

abstract class shopBrandFrontendActionWithMeta extends shopBrandFrontendAction
{
	protected $storefront;
	protected $currency;
	protected $default_currency;

	protected $view_buffer;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->storefront = shopBrandHelper::getStorefront();

		/** @var shopconfig $config */
		$config = $this->getConfig();
		$this->currency = $config->getCurrency(false);
		$this->default_currency = $config->getCurrency(true);

		$this->view_buffer = new shopBrandViewBuffer();
	}

	public function execute()
	{
		parent::execute();

		$template_layout = $this->getTemplateLayout();

		$viewBufferTemplateVars = $this->getViewBufferTemplateVars();
		$this->view_buffer->assign($viewBufferTemplateVars);
		$template_layout_fetched = $this->view_buffer->fetchTemplateLayout($template_layout);

		$this->executeBrandPage($template_layout_fetched);

		$this->applyMeta($template_layout_fetched);
	}

	/**
	 * @return shopBrandTemplateLayout
	 */
	abstract protected function getTemplateLayout();

	abstract protected function executeBrandPage(shopBrandFetchedLayout $fetched_layout);

	protected function getViewBufferTemplateVars()
	{
		$vars = array();

		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();
		$vars['host'] = waRequest::server('HTTP_HOST');
		$vars['store_info'] = array(
			'name' => $config->getGeneralSettings('name'),
			'phone' => $config->getGeneralSettings('phone'),
		);

		$hook_vars = wa()->event(array('shop', 'seofilter_fetch_templates'));

		foreach ($hook_vars as $plugin_id => $_hook_vars)
		{
			$vars = array_merge($vars, $_hook_vars);
		}

		return $vars;
	}

	protected function defaultMetaTitle()
	{
		return 'Бренды';
	}

	protected function applyMeta(shopBrandFetchedLayout $template_layout)
	{
		$response = $this->getResponse();

		$response_meta = new shopBrandResponseMeta();

		$response_meta->applyMeta($response, $template_layout, $this->defaultMetaTitle());
	}
}