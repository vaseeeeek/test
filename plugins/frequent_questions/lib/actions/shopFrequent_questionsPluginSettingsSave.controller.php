<?php

class shopFrequent_questionsPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        
        $fq_param_enabled = (int) waRequest::post('fq_plugin_enabled');
        $fq_placing = waRequest::post('fq_placing');
        $fq_qa_display = (int) waRequest::post('fq_qa_display');
        $fq_target_blank = (int) waRequest::post('fq_target_blank');
        $fq_link_title = (waRequest::post('fq_link_title')) ? waRequest::post('fq_link_title') : 'FAQ';
        $fq_page_title = waRequest::post('fq_page_title');
        $fq_expand = waRequest::post('fq_expand');
        $fq_question_color = waRequest::post('fq_question_color');
        $fq_answer_color = waRequest::post('fq_answer_color');
		$fq_question_size = waRequest::post('fq_question_size');
        $fq_answer_size = waRequest::post('fq_answer_size');
        $fq_plus_color = waRequest::post('fq_plus_color');
        $fq_minus_color = waRequest::post('fq_minus_color');
		$fq_header_color = waRequest::post('fq_header_color');
		$fq_plus_enabled = (int) waRequest::post('fq_plus_enabled');
        $fq_meta_keywords = waRequest::post('fq_meta_keywords');
        $fq_meta_description = waRequest::post('fq_meta_description');

        
        $this->plugin()->saveSettings(array('fq_plugin_enabled'=> $fq_param_enabled, 'fq_placing' => $fq_placing, 'fq_qa_display' => $fq_qa_display, 'fq_target_blank' => $fq_target_blank, 'fq_link_title' => $fq_link_title, 'fq_page_title' => $fq_page_title, 'fq_expand' => $fq_expand, 'fq_question_color' => $fq_question_color, 'fq_answer_color' => $fq_answer_color, 'fq_question_size' => $fq_question_size, 'fq_answer_size' => $fq_answer_size, 'fq_plus_color' => $fq_plus_color, 'fq_minus_color' => $fq_minus_color,'fq_plus_enabled'=> $fq_plus_enabled, 'fq_header_color' => $fq_header_color,'fq_meta_keywords' => $fq_meta_keywords,'fq_meta_description' => $fq_meta_description,)); 
        
        $fq_model = new shopFrequent_questionsPluginModel();
        
        $fq_questions = waRequest::post('questions');
        $fq_answers = waRequest::post('answers');
        $fq_on = waRequest::post('on');
        
        $fq_model->fqTableErase();
        
        $i = 0;
        $fq_data = array();

        if (!empty($fq_questions)) {
            foreach ($fq_questions as $question) {
                $fq_data['question'] = $question;
                $fq_data['answer'] = $fq_answers[$i];
                $fq_data['enable'] = $fq_on[$i];
                
                if (!empty($fq_data['question']) && !empty($fq_data['answer'])) {
                    $fq_model->insert($fq_data);
                }
                
                $i++;
                
            }            
        }
   
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