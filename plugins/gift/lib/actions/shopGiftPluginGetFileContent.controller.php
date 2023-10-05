<?php

class shopGiftPluginGetFileContentController extends waJsonController
{

	public function execute()
	{
		$theme = waRequest::post('theme','');
		$name = waRequest::post('name','');
		
		$f = new shopGiftPluginFiles($theme);
		$this->response = $f->getFileContent($name);
	}

}