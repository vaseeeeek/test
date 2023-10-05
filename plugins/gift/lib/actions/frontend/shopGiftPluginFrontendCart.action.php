<?php

class shopGiftPluginFrontendCartAction extends waViewAction
{

	public function execute()
	{
		$h = new shopGiftPluginHelper;
		$cart_gifts = $h->getCartGifts();
		
		$gift_products_block = '';
		$plugin = wa()->getPlugin('gift');
		if ( ( $plugin->getSettings('products') ) )
		{
			$gift_ids = array();
			if ( !empty($cart_gifts) )
				foreach ( $cart_gifts as $item )
					if ( !empty($item['gifts']) )
						foreach ( $item['gifts'] as $gift )
							$gift_ids[] = $gift['id'];
			$gift_products_block = shopGiftPlugin::products($gift_ids);
		}
		$this->view->assign('gifts',$cart_gifts);
		$this->view->assign('gift_products_block',$gift_products_block);
	}

}