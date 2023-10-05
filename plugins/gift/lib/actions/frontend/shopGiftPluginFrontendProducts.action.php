<?php

class shopGiftPluginFrontendProductsAction extends shopFrontendAction
{
	public function execute()
	{
		$gift_id = waRequest::param('id',0);
		
		$model = new shopGiftPluginProductGiftModel;
		$product_ids = $model->getProductIds($gift_id);
		
		$title = '';
		
		//gift
		$gift = array();
		if ( $gift_id )
		{
			$collection = new shopProductsCollection(array($gift_id));
			$gift = $collection->getProducts('*');
			if ( !empty($gift) )
			{
				$gift = array_shift($gift);
				$title = 'Товары с подарком '.$gift['name'];
			}
		}
		else
			$title = 'Товары с подарками';
		
		$c = new shopProductsCollection($product_ids);
		$this->setCollection($c);
		
		$this->view->assign('title', htmlspecialchars($title));
		$this->view->assign('gift', $gift);
		$this->getResponse()->setTitle($title);
		
		/**
		 * @event frontend_search
		 * @return array[string]string $return[%plugin_id%] html output for search
		 */
		$this->view->assign('frontend_search', wa()->event('frontend_search'));
		$this->setThemeTemplate('search.html');

		waSystem::popActivePlugin();
	}
}