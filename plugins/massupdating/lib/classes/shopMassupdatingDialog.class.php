<?php

/*
 * mail@shevsky.com
 */
 
class shopMassupdatingDialog extends waViewAction
{
	public $title, $method;
	
	public function __construct($params = null)
	{
		parent::__construct($params);
		
		$this->plugin = wa('shop')->getPlugin('massupdating');
		
		preg_match('/shopMassupdatingPlugin([A-Z][a-z0-9]+)[a-zA-Z0-9]*Action/', get_class($this), $matches);
		$this->module = $matches[1];
		$this->setTemplate('file:' . $this->getPluginRoot() . 'templates/dialog/' . strtolower($this->module) . '.html');
	}
	
	public function preExecute()
	{
		$product_ids = waRequest::post('product_id', array(), 'array_int');
		$hash = waRequest::post('hash', '');
		if(count($product_ids) == 0 && !$hash)
			$this->setTemplate('string:error:zero');
		
		$this->plugin->memoryErrorCatcher('error:memory');
		
		if($hash)
			$product_ids = $this->plugin->getProductIdsByHash($hash);

		if(count($product_ids) > 0) {
			$this->product_ids = $product_ids;
			
			$this->view->assign('title', _wp($this->title));
			$this->view->assign('product_ids', $this->product_ids);
		}
	}
}