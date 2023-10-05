<?php

/*
 * mail@shevsky.com
 */
 
class shopMassupdatingPluginFarDefaultAction extends shopMassupdatingDialog
{
	public $title = 'Найти и заменить';
	
	public function execute()
	{
		$this->view->assign('features', $this->plugin->getFeatures());
	}
}