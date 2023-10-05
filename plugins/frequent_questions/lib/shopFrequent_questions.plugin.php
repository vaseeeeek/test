<?php
/**
* Plugin: Frequently Asked Questions
* For Shop-Script5
* @author welldi-studio.ru, Надежда Гринева <n.grineva@welldi-studio.ru>
*/

class shopFrequent_questionsPlugin extends shopPlugin
{
    
    protected $settings;
    

    
    public function frontendPage()
    {
        if (!isset($this->settings)) {
            $this->settings = $this->getSettings();
        }
        if (!isset($this->settings['fq_plugin_enabled'])) {
            return;
        }

		$fq_link_title = !empty($this->settings['fq_link_title'])?$this->settings['fq_link_title']:'FAQ';
        
        if ($this->settings['fq_plugin_enabled'] > 0 && $this->settings['fq_placing'] == 'default') {
			$this->view()->assign('fq_settings', $this->settings);
            $base_url = wa()->getAppUrl().$this->getUrl('frequent_questions', false);
            wa()->getView()->assign('base_url', $base_url);
            return $this->view()->fetch($this->path.'/templates/Frequent_questionsLink.html'); 
        } else {
            return "";
        }
    }
    
    
    public function frontendHead($params)
    {
        if (!isset($this->settings)) {
            $this->settings = $this->getSettings();
        }
        if (!isset($this->settings['fq_plugin_enabled'])) {
            return;
        }
        
        if ($this->settings['fq_plugin_enabled'] > 0) {
            $question_style = array();
            $answer_style = array();
            if (!empty($this->settings['fq_question_color'])) {
                $question_style[] = "color:".$this->settings['fq_question_color'].";";
            }
    		if (!empty($this->settings['fq_question_size'])) {
                $question_style[] = "font-size:".$this->settings['fq_question_size']."px;";
            }
            if (!empty($this->settings['fq_answer_color'])) {
                $answer_style[] = "color:".$this->settings['fq_answer_color'].";";
            }
    		if (!empty($this->settings['fq_answer_size'])) {
                $answer_style[] = "font-size:".$this->settings['fq_answer_size']."px;";
            }
                
            $css_url = wa()->getAppStaticUrl().$this->getUrl('css/frequent_questions.css', true);
            wa()->getView()->assign('css_url', $css_url);
            wa()->getView()->assign('fq_settings', $this->settings);
            wa()->getView()->assign('fq_question_style', implode($question_style));
            wa()->getView()->assign('fq_answer_style', implode($answer_style));
            return $this->view()->fetch($this->path.'/templates/Frequent_questionsHead.html'); 
        } else {
            return;
        }
    }
    
    
    private function view() 
    {
        static $view;
        if (!$view) {
            $view = wa()->getView();
        }
        return $view;
    }
    
    static function display()
    {
        $plugin = wa()->getPlugin('frequent_questions');

        
        if ($plugin->settings['fq_plugin_enabled'] == 1 && $plugin->settings['fq_placing'] == 'custom') {
			wa()->getView()->assign('fq_settings', $plugin->settings);
            $base_url = wa()->getAppUrl().$plugin->getUrl('frequent_questions', false);
            wa()->getView()->assign('base_url', $base_url);
            return wa()->getView()->fetch($plugin->path.'/templates/Frequent_questionsLink.html'); 
        } else {
            return "";
        }
            
    }
    
    public static function print_faq() 
    {
        
        $fq_plugin = wa()->getPlugin('frequent_questions');
        $settings = $fq_plugin->getSettings();

        wa()->getResponse()->setMeta("keywords", $settings['fq_meta_keywords']);
        wa()->getResponse()->setMeta("description", $settings['fq_meta_description']);
        wa()->getResponse()->setTitle($settings['fq_page_title']);
        $question_style = array();
        $answer_style = array();

        if ($settings['fq_plugin_enabled'] > 0 ) {
        
            if (!empty($settings['fq_question_color'])) {
                $question_style[] = "color:".$settings['fq_question_color'].";";
            }
    		if (!empty($settings['fq_question_size'])) {
                $question_style[] = "font-size:".$settings['fq_question_size']."px;";
            }
            if (!empty($settings['fq_answer_color'])) {
                $answer_style[] = "color:".$settings['fq_answer_color'].";";
            }
    		if (!empty($settings['fq_answer_size'])) {
                $answer_style[] = "font-size:".$settings['fq_answer_size']."px;";
            }
			
            $fq_model = new shopFrequent_questionsPluginModel();
            $faq_items = $fq_model->order("id")->fetchAll();
            
            $fq_plugin->view()->assign('faq_items', $faq_items);
            $fq_plugin->view()->assign('fq_question_style', implode($question_style));
            $fq_plugin->view()->assign('fq_answer_style', implode($answer_style));
            $fq_plugin->view()->assign('fq_settings', $settings);   
            
            $content = $fq_plugin->view()->fetch($fq_plugin->path.'/templates/Frequent_questionsBody.html');
        } else {
            $content = "";
        }
        
        return $content;

    }
    
    

}