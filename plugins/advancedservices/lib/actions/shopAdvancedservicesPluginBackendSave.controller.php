<?php

class shopAdvancedservicesPluginBackendSaveController extends waJsonController 
{

	public function execute()
	{
	try {
			if (waRequest::post()) {
	
		$on = waRequest::post('advs_enabled');
		$minus = waRequest::post('advs_minus_service');
		$usetheme = waRequest::post('advs_use_theme_popup');
		$usemob = waRequest::post('advs_use_mobile');
	
	if (waRequest::post('advs_service_array')) {
			$advs_services = explode(',', waRequest::post('advs_service_array'));
			$advs_services_settings = array();
	
			
			$i = 0;
			foreach ($advs_services as $id) {
				
				
				if ("on" == waRequest::post('advs_service_enabled_'.$id)) {
					$enabled = true;
					} 
					else {
						$enabled = "";
					}
				if (null!= waRequest::post('advs_service_link_'.$id)) {
					$link =  htmlspecialchars(waRequest::post('advs_service_link_'.$id));
					} 
					else {
						$link= "";
					}
					
				if (null!= waRequest::post('advs_service_category_filter_'.$id)) {
		
					$category_filter =  htmlspecialchars(waRequest::post('advs_service_category_filter_'.$id));
					
					} 
					else {
							$category_filter = "";
					}	
					
				if (null!= waRequest::post('advs_service_tooltip_'.$id)) {
		
					$tooltip =  htmlspecialchars(waRequest::post('advs_service_tooltip_'.$id));
					
					} 
					else {
							$tooltip = "";
					}
				if ("on" == waRequest::post('advs_service_popup_'.$id)) {
							$popup = true;
					} 
					else {
							$popup = "";
					}
				
				if ("on" == waRequest::post('advs_service_ondefault_'.$id)) {
							$ondefault = true;
					} 
					else {
							$ondefault = "";
					}	
				
				if ("on" == waRequest::post('advs_service_divider_'.$id)) {
							$divider = true;
					} 
					else {
							$divider = "";
					}	
					
			 	if (null!= waRequest::post('advs_service_name_'.$id)) {
						$name = waRequest::post('advs_service_name_'.$id);
					} 
					else {
							$name = "";
					}
			 	
			 
					$advs_services_settings[$i] = array(
						    'id' => $id,
						    'enabled' => 	$enabled,
						    'link' => $link,
						    'category_filter' => $category_filter,
						  	'name' => $name,
						    'variant' => false,
						    'popup' => 	$popup,
						    'ondefault' => $ondefault,
						    'divider' => $divider,
						    'tooltip' => $tooltip,   
					    );
				
				
						
					    ++$i;
					}
				
			}
			else {
				 $advs_services_settings = null;
			}
		
			wa('shop')->getPlugin('advancedservices')->saveSettings( 
				array(
			'enabled' => $on,
			'use_theme_popup' => $usetheme,
			'use_mobile' => $usemob,
			
			        ));
		
		$this->update($advs_services_settings);
		
	  	$this->response['message'] = "Сохранено";
		}
			
			else  $this->response['message'] = "Error";
			
		} catch (Exception $ex) {
         	   $this->setError($ex->getMessage());
		}
			
	}

	
	 private function update($settings) {
          
          $db = new shopAdvancedservicesPluginModel();
          
          try {
          	
          	
               foreach ($settings as $set) {
                 $id = $db->insert(array(
                 	'id' => $set["id"],
				    'enabled' => 	$set["enabled"],
				    'link' => $set["link"],
				    'category_filter' => $set["category_filter"],
				  	'name' => $set["name"],
				    'variant' => false,
				    'popup' => 	$set["popup"],
				    'ondefault' => $set["ondefault"],
				    'divider' => $set["divider"],
				    'tooltip' => $set["tooltip"],   
                 
                 	), 1);
               }
          		return true;
          }
          
          catch (Exception $ex) {
                 	   return $this->setError($ex->getMessage());
        			}
    
        
    }

}
