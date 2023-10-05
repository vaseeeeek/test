<?php

abstract class shopBrandBackendAction extends shopBrandWaBackendViewAction
{
	protected function preExecute()
	{
		parent::preExecute();

		$layout = new shopBrandBackendLayout();
		$layout->assign('no_level2', true);
		$this->setLayout($layout);

		$info = wa('shop')->getConfig()->getPluginInfo('brand');
		$this->view->assign('asset_version', waSystemConfig::isDebug() ? time() : $info['version']);

		$this->getResponse()->addJs('wa-content/js/ace/ace.js');
	}
}
