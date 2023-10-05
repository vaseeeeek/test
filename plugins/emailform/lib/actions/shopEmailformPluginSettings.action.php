<?php

class shopEmailformPluginSettingsAction extends waViewAction 
{
    public function execute()
    {
		$pluginm = new shopEmailformPluginModel();
		$results = $pluginm->getAll();
		$emails = array();

        $plugin = wa('shop')->getPlugin('emailform');
        $delimiter = ($plugin->getSettings('delimiter')) ? $plugin->getSettings('delimiter') : ' ';
		
        foreach ($results as $email) {
			//if ($email['name']) $email['name'] .= $delimiter;
            //if ($email['phone']) $email['phone'] .= $delimiter;
            $emails[] = $email['email'] . $delimiter . $email['name'] . $delimiter . $email['phone'] . $delimiter;
        }
		$this->view->assign('emails', $emails);
        
        // получаем все настройки плагина, чтобы передать их в шаблон
        $settings = $plugin->getSettings(); 
        $this->view->assign('settings', $settings);
    }
}
