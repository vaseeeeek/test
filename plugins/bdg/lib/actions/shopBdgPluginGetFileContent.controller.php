<?php

class shopBdgPluginGetFileContentController extends waJsonController
{

	public function execute()
	{
		$theme = waRequest::post('theme','');
		$name = waRequest::post('name','');
		
		$f = new shopBdgPluginFiles($theme);
		$this->response = $f->getFileContent($name);
	}

}