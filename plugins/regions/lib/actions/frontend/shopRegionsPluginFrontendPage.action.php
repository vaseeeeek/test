<?php

class shopRegionsPluginFrontendPageAction extends shopFrontendPageAction
{
	public function __construct($params = null)
	{
		parent::__construct($params);

		$page = $this->findShopPageByUrl(waRequest::param('regions_page_url'));
		waRequest::setParam('page_id', $page ? $page['id'] : null);
	}

	public function execute()
	{
		parent::execute();

		$page = $this->view->getVars('page');

		if (!$page)
		{
			return;
		}

		$full_url = $page['full_url'];
		$ignored_pages = waRequest::param('regions_ignore_default_pages', array());
		if (!ifset($ignored_pages[$full_url], false) && !empty($page['content']))
		{
			return;
		}

		$settings = new shopRegionsSettings();
		$page_template = $settings->getPageTemplate($full_url);
		if ($page_template)
		{
			$context = new shopRegionsPluginContext();
			$optimizer_manager = new shopRegionsOptimizerManager($context);

			$optimizer = $optimizer_manager->getPageOptimizer();
			$optimizer->execute();
		}
	}

	private function findShopPageByUrl($page_url)
	{
		$model = new shopPageModel();
		$routing = wa()->getRouting();
		$route = $routing->getRoute();

		$sql = '
SELECT *
FROM `' . $model->getTableName() . '`
WHERE `full_url` = :full_url AND `status` = 1
ORDER BY (`domain` = :domain AND `route` = :route) DESC, `id` ASC
LIMIT 1
';

		$params = array(
			'full_url' => $page_url,
			'domain' => $routing->getDomain(null, true),
			'route' => $route['url'],
		);

		return $model->query($sql, $params)->fetchAssoc();
	}
}