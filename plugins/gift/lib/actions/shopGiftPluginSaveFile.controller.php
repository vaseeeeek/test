<?php

class shopGiftPluginSaveFileController extends waJsonController
{

	public function execute()
	{
		$theme = waRequest::post('theme','');
		$f = new shopGiftPluginFiles($theme);
		$f->saveFromPostData();
	}

}