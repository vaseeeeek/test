<?php

class shopBdgPluginSaveFileController extends waJsonController
{

	public function execute()
	{
		$theme = waRequest::post('theme','');
		$f = new shopBdgPluginFiles($theme);
		$f->saveFromPostData();
	}

}