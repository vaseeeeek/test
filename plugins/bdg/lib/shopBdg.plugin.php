<?php

class shopBdgPlugin extends shopPlugin
{
	
	public function backendProducts()
	{
		$view = wa()->getView();
		$model = new shopBdgPluginBadgeModel;
		$badges = $model->getAll();
		$view->assign('badges',$badges);
		return array(
			'toolbar_section' => $view->fetch($this->path.'/templates/backendProducts.html')
		);
	}
	
	
	public function backendProduct($product)
	{
		$product_badge_model = new shopBdgPluginProductBadgeModel;
		$badge_model = new shopBdgPluginBadgeModel;

		$view = wa()->getView();
		$view->assign(array(
			'badges' => $badge_model->getAll(),
			'badge_ids' => $product_badge_model->getBadgeIds($product['id']),
			'product_id' => $product['id']
		));
		
		return array(
			'toolbar_section' => $view->fetch($this->path.'/templates/backendProduct.html')
		);
	}
	
	
	static public function on()
	{
		return wa()->getPlugin('bdg')->getSettings('on');
	}
	
	
	public function productDelete($product_ids)
	{
		$model = new shopBdgPluginProductBadgeModel;
		if ( !empty($product_ids) && is_array($product_ids) )
			foreach ( $product_ids as $product_id )
				$model->deleteByField('product_id', $product_id);
	}
	
	
	public function frontendHead()
	{
		$html = '';
		$settings = $this->getSettings();
		if ( $settings['on'] )
		{
			$response = waSystem::getInstance()->getResponse();
			$response->addJs('plugins/bdg/js/jquery.colorhelpers.js','shop');
			
			$f = new shopBdgPluginFiles;
			$f->addCss('css');
			$f->addJs('js');
			$view = wa()->getView();
			$html = $view->fetch('string:'.$f->getFileContent('head'));
		}
		return $html;
	}
	
	
	 public function saveSettings($settings = array())
	{
		if (  isset($settings['badges']) )
		{
			$data = $settings['badges'];
			$model = new shopBdgPluginBadgeModel;
			if ( !empty($data) && is_array($data) )
			{
				$model->save($data);
				$productBadgeModel = new shopBdgPluginProductBadgeModel;
				$productBadgeModel->updateCode();
			}
			unset($settings['badges']);
		}
		parent::saveSettings($settings);
	}
}