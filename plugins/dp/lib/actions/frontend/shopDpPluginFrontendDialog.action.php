<?php

class shopDpPluginFrontendDialogAction extends waViewAction
{
	public function execute()
	{
		$this->view->assign('plugin_url', wa()->getAppStaticUrl('shop') . 'plugins/dp/');

		$current_theme_id = wao(new shopDpEnv())->getCurrentTheme();
		$this->view->assign('current_theme_id', $current_theme_id);

		$templates_instance = new shopDpTemplates();
		$templates_instance->register($this->view);
	}
}