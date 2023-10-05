<?php

class shopGiftPlugin extends shopPlugin
{

	public static function getCartGifts($product_id = 1)
	{
		$h = new shopGiftPluginHelper;
		$cart_gifts = $h->getCartGifts();

		return $cart_gifts;
	}

	public function frontendCart()
	{
		$html = '';
		if ( $this->getSettings('on') )
		{
			$response = waSystem::getInstance()->getResponse();
			$aurl = 'plugins/gift/js/arcticmodal/';
			$response->addCss($aurl.'jquery.arcticmodal-0.3.css','shop');
			$response->addCss($aurl.'themes/simple.css','shop');
			$response->addJs($aurl.'jquery.arcticmodal-0.3.min.js','shop');
			$response->addJs('wa-content/js/jquery-plugins/jquery.cookie.js');
			
			$f = new shopGiftPluginFiles;
			$f->addCss('css');
			$f->addJs('js');
			$url = wa()->getAppUrl();
			$html = '<div id="gift-p-list-wr" data-url="'.$url.'"></div>';
		}

		return $html;
	}
	
	
	public function frontendCheckout()
	{
		$html = '';
		if ( $this->getSettings('checkout') )
			$html = $this->frontendCart();
		return $html;
	}
	
	
	public function orderActionCreate($data)
	{
		$order_id = $data['order_id'];
		$model = new shopOrderItemsModel;
		$o = new shopGiftPluginOrder;
		$h = new shopGiftPluginHelper;
		$gifts = array();
		$cart_gifts = $h->getCartGifts();
		if ( !empty($cart_gifts) )
			foreach ( $cart_gifts as $product_id => $v )
				if ( !empty($v['gifts']) )
					foreach ( $v['gifts'] as $gift )
						if ( isset($gifts[$gift['id']]) )
							$gifts[$gift['id']]['quantity'] += $gift['quantity'];
						else
							$gifts[$gift['id']] = $gift;
		
		if ( !empty($gifts) )
			foreach ( $gifts as $gift )
				if ( $gift['quantity'] > 0 )
					$o->insertOrderItem($gift,$order_id);
		
		$o->sendNotification($data);

		$storage = wa()->getStorage();
		$this->_cart_gifts = $storage->remove('shopGiftPlugin');
	}
	
	
	public function backendProduct($product)
	{
		$html = '';
		
		$model = new shopGiftPluginProductGiftModel;
		$selected_gifts = $model->getGiftIds($product->id);
		
		$h = new shopGiftPluginHelper(false);
		$img_url = wa()->getAppStaticUrl('shop').$this->getUrl('img/gift.png', true);
		$gift_list = $h->getGiftList();
		if ( !empty($gift_list) )
		{
			$ids = array_keys($gift_list);
			if ( !empty($selected_gifts) )
				foreach ( $selected_gifts as $k=>$id )
					if ( !in_array($id,$ids) )
					{
						$model->query('DELETE FROM '.$model->getTableName().' WHERE gift_id='.(int)$id.' AND product_id = '.(int)$product->id);
						unset($selected_gifts[$k]);
					}
		}
		$view = wa()->getView();
		$view->assign('img_url',$img_url);
		$view->assign('gift_list',$gift_list);
		$view->assign('product_id',$product->id);
		$view->assign('selected_gifts',$selected_gifts);
		$html = $view->fetch($this->path.'/templates/toolbar.html');
		return array(
			'toolbar_section' => $html,
		);
	}
	
	
	public function getPath()
	{
		return $this->path;
	}
	
	
	/* HELPER */
	static public function gifts($product_id,$plugin = null)
	{
		$html = '';
		if ( $plugin == null )
			$plugin = wa()->getPlugin('gift');
		if ( $plugin->getSettings('on') )
		{
			$list = new shopGiftPluginHelper;
			$gifts = $list->getGifts($product_id);
			
			if ( !empty($gifts) )
			{
				$img_url = $plugin->getPluginStaticUrl().'img/gift36.png';
				$view = wa()->getView();
				$view->assign('gifts',$gifts);
				$view->assign('img_url',$img_url);
				
				$f = new shopGiftPluginFiles;
				$f->addCss('css');
				$f->addJs('js');
				$html = $view->fetch('string:'.$f->getFileContent('gift'));
			}
		}
		return $html;
	}
	
	/* HELPER */
	static public function products($gift_id,$product_id=0,$plugin = null)
	{
		$html = '';
		if ( $plugin == null )
			$plugin = wa()->getPlugin('gift');
		if ( $plugin->getSettings('on') )
		{
			$gp = new shopGiftPluginGiftProducts($plugin->getSettings('max_product_count'));
			if ( !empty($gift_id) )
				$products = $gp->getByGiftId($gift_id);
			elseif ( $product_id > 0 )
				$products = $gp->getByProductId($product_id);
			
			if ( !empty($products) )
			{
				$view = wa()->getView();
				$view->assign('products',$products);
				$view->assign('gift_id',$gift_id);
				
				$f = new shopGiftPluginFiles;
				$f->addCss('css');
				$f->addJs('js');
				$html = $view->fetch('string:'.$f->getFileContent('products'));
			}
		}
		return $html;
	}
	
	
	public function frontendProduct($product)
	{
		$html = '';
		if ( $this->getSettings('on') )
		{
			$html_gifts = ( $this->getSettings('gift') ) ? self::gifts($product->id,$this) : '';
			$html_products = ( $this->getSettings('products') ) ? self::products(0,$product->id,$this) : '';
		}
		return array(
			'block'=> $html_gifts.$html_products
		);
	}

}