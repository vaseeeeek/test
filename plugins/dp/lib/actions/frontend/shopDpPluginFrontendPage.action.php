<?php

class shopDpPluginFrontendPageAction extends shopFrontendAction
{
	public function execute()
	{
		$plugin = shopDpPlugin::getInstance('frontend_page');

		$view = wa()->getView();

		$store = array(
			'name' => '',
			'phone' => ''
		);

		$config = wa('shop')->getConfig();

		if($config instanceof shopConfig) {
			$store = array(
				'name' => $config->getGeneralSettings('name'),
				'phone' => $config->getGeneralSettings('phone')
			);
		}

		$vars = compact('store');
		$view->assign($vars);

		$title = $plugin->getSettings('page_title');
		$keywords = $plugin->getSettings('page_keywords');
		$description = $plugin->getSettings('page_description');

		$this->getResponse()->setTitle($view->fetch("string:$title"));
		$this->getResponse()->setMeta('keywords', $view->fetch("string:$keywords"));
		$this->getResponse()->setMeta('description', $view->fetch("string:$description"));
		$this->getResponse()->setOGMeta('og:title', $view->fetch("string:$title"));
		$this->getResponse()->setOGMeta('og:keywords', $view->fetch("string:$keywords"));
		$this->getResponse()->setOGMeta('og:description', $view->fetch("string:$description"));
		$output = $plugin->getFrontend($this->view)->page(array(
			'plugin_url' => $plugin->getPluginStaticUrl()
		));

		$this->view->assign('page', array(
			'name' => $view->fetch('string:' . $title),
			'id' => 'dp_page',
			'content' => $output
		));

		$this->setThemeTemplate('page.html');
	}
}