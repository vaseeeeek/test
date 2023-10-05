<?php

class shopFrequent_questionsPluginFrontendFrequent_questionsAction extends shopFrontendAction
{

    private $settings;
    public function execute()
    {

        $this->settings = $this->plugin()->getSettings();
        
        if ($this->settings['fq_plugin_enabled'] > 0 ) {
            wa()->getResponse()->setMeta("keywords", $this->settings['fq_meta_keywords']);
            wa()->getResponse()->setMeta("description", $this->settings['fq_meta_description']);
            wa()->getResponse()->setTitle($this->settings['fq_page_title']);
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
    			
                
            $fq_model = new shopFrequent_questionsPluginModel();
            $faq_items = $fq_model->order("id")->fetchAll();
            
            $this->view->assign('faq_items', $faq_items);
            $this->view->assign('fq_question_style', implode($question_style));
            $this->view->assign('fq_answer_style', implode($answer_style));
            

        }   
        $this->view->assign('fq_settings', $this->settings);    

    }
    
    private function plugin()
    {
        static $plugin;
        if (!$plugin) {
            $plugin = wa()->getPlugin('frequent_questions');
        }
        return $plugin;
    }

}
