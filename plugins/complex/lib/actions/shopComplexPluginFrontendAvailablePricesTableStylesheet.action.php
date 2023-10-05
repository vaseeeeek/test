<?php

class shopComplexPluginFrontendAvailablePricesTableStylesheetAction extends waViewAction
{
	public function execute()
	{
		$this->getResponse()->addHeader('Content-type', 'text/css');
		$this->getResponse()->addHeader('X-Content-Type-Options', 'nosniff');

		$plugin = wa('shop')->getPlugin('complex');
		$css = $plugin->getSettings('product_css');

		$this->view->assign('css', $css);
	}
}