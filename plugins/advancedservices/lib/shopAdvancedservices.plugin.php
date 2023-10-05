<?php
class shopAdvancedservicesPlugin extends shopPlugin
{

	public function frontendFooter()
	{
		
		if($this->getSettings('enabled')) {
			
			$view = wa()->getView();
			
			$set = new shopAdvancedservicesPluginSettingsAction();
			
			$optionsArray = $set->getSet();
			
			if ($optionsArray) {
			   $view->assign('settings', json_encode($optionsArray)); 
			}
			else {
				  $view->assign('settings', null) ; 
			}
			
			$usemobile = $this->getSettings('use_mobile');
			$view->assign('use_mobile', isset ( $usemobile ) ? $usemobile : 0) ; 
			
		
			
			$view = wa()->getView();
			
			if ($this->getSettings('use_theme_popup')) {
					$template_path =wa()->getAppPath('plugins/advancedservices/templates/front/Advancedservices_themedialog.html',  'shop') ;
		
			}
			else {
				
		
		   
				$template_path = wa()->getAppPath('plugins/advancedservices/templates/front/Advancedservices.html',  'shop') ;
		
			}
			
			$html = $view->fetch($template_path) ;
			return $html;
		}
		
		
	   
	}

 
	
}
