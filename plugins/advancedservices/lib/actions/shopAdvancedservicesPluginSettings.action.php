<?php

class shopAdvancedservicesPluginSettingsAction extends waViewAction
{	
	public function execute()
		{
		
	
		$settings = wa('shop')->getPlugin('advancedservices')->getSettings();
		$service_list = $this->getServices();
	
		
		$set = $this->getSet();
		
		$this->view->assign('service_settings' ,  isset ( $set ) ? json_encode($set) : null);
		$this->view->assign('advs_services' ,  $service_list ? json_encode($service_list) : null );
		$this->view->assign('advs_enabled' , isset ( $settings["enabled"] ) ? $settings["enabled"] : null);
		$this->view->assign('advs_use_theme_popup' , isset ( $settings["use_theme_popup"] ) ? $settings["use_theme_popup"] : null);
		$this->view->assign('advs_use_mobile' , isset ( $settings["use_mobile"] ) ? $settings["use_mobile"] : null);
	
		$template_dir = dirname(__FILE__).'/../../templates/settings/';
		$this->setTemplate($template_dir.'Settings.html');
		
	
		
		}
		
	private function getServices() {
	
		$services_model = new shopServiceModel();
		$services = $services_model->getAll('id');
	
		
		if (($services!=null)) {  
			
			return (array_values($services)); 
			}
		else return null;
	}	
	

	
	public function getSet() {
	
	 $db = new shopAdvancedservicesPluginModel();
	    try {
          	  $set = $db->getAll();   
          	  if (($set!=null)) {  
					return array_values($set); 
					}
				else return null;
              
          	
          }
          
          catch (Exception $ex) {
                 	   return $this->setError($ex->getMessage());
        			}
    
        
	}	
	
}