<?php

class shopGiftPluginSaveController extends waJsonController
{

	public function execute()
	{
		$css = waRequest::post('gift_css','',waRequest::TYPE_STRING_TRIM);
		if ( !empty($css) )
		{
			$file = wa()->getDataPath("plugins/gift/shopGiftPlugin.css", true, 'shop');
			file_put_contents($file,$css);
		}
		
		$js = waRequest::post('gift_js','',waRequest::TYPE_STRING_TRIM);
		if ( !empty($js) )
		{
			$file = wa()->getDataPath("plugins/gift/shopGiftPlugin.js", true, 'shop');
			file_put_contents($file,$js);
		}
		
		$products_html = waRequest::post('products_html','',waRequest::TYPE_STRING_TRIM);
		if ( !empty($products_html) )
		{
			$file = wa()->getDataPath("plugins/gift/products.html", true, 'shop');
			file_put_contents($file,$products_html);
		}
		
		$gift_html = waRequest::post('gift_html','',waRequest::TYPE_STRING_TRIM);
		if ( !empty($gift_html) )
		{
			$file = wa()->getDataPath("plugins/gift/gift.html", true, 'shop');
			file_put_contents($file,$gift_html);
		}
	}

}