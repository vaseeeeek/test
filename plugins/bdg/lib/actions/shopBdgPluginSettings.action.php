<?php

class shopBdgPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		
		$model = new shopBdgPluginBadgeModel;
		$f = new shopBdgPluginFiles;
		$this->view->assign(array(
			'badges' => $model->getAll(),
			'css' => $f->getFileContent('css'),
			'settings' => wa()->getPlugin('bdg')->getSettings()
		));
	}
}