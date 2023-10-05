<?php

class shopAdvancedservicesPluginFrontendAdvservicedescriptionAction extends shopFrontendAction
{


	   
	public function execute()
	{
		$id = waRequest::param('id') ? (waRequest::param('id')) : null ;
		
		$page = [
						'id' => '',
						'name' => '',
						'content' =>'',
					];
					
		if ($id) {
		  
		   	$settings = wa('shop')->getPlugin('advancedservices')->getSettings();
		 	$service_settings = $settings['service_settings'];
		 	for ($i = 0; $i < count ($service_settings); $i++) {
		 		
		 		if ($service_settings[$i]['id'] == $id) {
		 			
		 			$name = 'Подробнее об услуге "'.$service_settings[$i]['name'].'"';
		 			$content = htmlspecialchars_decode($service_settings[$i]['tooltip']);
		 			$page = [
		 				 'id' => $id,
						'name' => $name,
						'content' => $content,
					];
		 		}
		 	}
		 	
			$this->view->assign('title', $page['name']);
			$this->getResponse()->setTitle($page['name']);
			$this->view->assign('page', $page);
			
			$this->view->assign('frontend_page', wa()->event('frontend_page'));
			$this->setThemeTemplate('page.html');
	
			waSystem::popActivePlugin();

}

	}
		
 
	

}
