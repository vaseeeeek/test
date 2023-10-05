<?php

class shopProductalbumPlugin extends shopPlugin
{
	public function handleBackendProduct($product)
	{   
        if (!$product->id)
		{
			return [];
		}

		$view = wa()->getView();
		$view->assign('product', $product);

		$templatePathProductEditSectionLi = wa()->getAppPath('plugins/productalbum/templates/hooks/', 'shop') . 'BackendProduct.EditSectionLi.html';

		return [
			'edit_section_li' => $view->fetch($templatePathProductEditSectionLi),
		];
	}
    public function frontendHead(){
        $plugin_path = wa()->getAppPath('plugins/productalbum/', 'shop');
        $this->addCss($plugin_path . 'css/productalbum.css');
        $this->addJs($plugin_path . 'js/productalbum.js');
    }

	public function getAlboms($id)
	{	
		$model = new shopProductalbumModel();
		$album = $model->getAlbumsByProduct($id);
		if ($album) {
			return $album['album_id'];
		} else {
			return false;
		}
	}
}
