<?php

class shopFrequent_questionsPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $fq_model = new shopFrequent_questionsPluginModel();
        $fq_items = $fq_model->order("id")->fetchAll();

        $fq_html = "";
        $i = 0;
            foreach ($fq_items as $fq_item) {
                if ($fq_item['enable'] == 1) {
                    $checked = 'checked';
                } else {
                    $checked = '';
                }
                $question = $fq_item['question'];
                $answer = $fq_item['answer'];
          
                $fq_html .= "<tr class='fq_$i'><td class='valign-top width-20'><a style='display:inline' href='#' onclick='return false'><i class='icon16 sort'></i></a></td><td class='valign-top width-20'><a href='#' onclick='return false' data-i='$i' class='fq-edit'><i class='icon16 edit'></i></a></td><td class='valign-top width-20'><a href='#' onclick='return false'  class='fq_delete' id='fq_$i'><i class='icon16 delete'></i></a></td><td><div class='inline-block field-group'>$question</div><div class='field-group fq_$i hide'><div class='field question'><div class='name'><label>"._wp('Question')."</label></div><div class='value'><textarea class='textarea fq_question'>$question</textarea></div></div><div class='field answer'><div class='name'><label>"._wp('Answer')."</label></div><div class='value'><textarea class='textarea fq_answer'>$answer</textarea></div></div><div class='field'><div class='name'><label>"._wp('Show')."</label></div><div class='value'><input type='checkbox' value='1' $checked class='fq_on'></div></div></div></td></tr>";
            $i++;
            }
        $plugin = $this->plugin();
        $fq_plugin_enabled = $plugin->getSettings('fq_plugin_enabled');
        $fq_placing = $plugin->getSettings('fq_placing');
        $fq_qa_display = $plugin->getSettings('fq_qa_display');
        $fq_target_blank = $plugin->getSettings('fq_target_blank');
        $fq_link_title = $plugin->getSettings('fq_link_title');
        $fq_page_title = $plugin->getSettings('fq_page_title');
        $fq_expand = $plugin->getSettings('fq_expand');
        $fq_question_color = $plugin->getSettings('fq_question_color');
        $fq_answer_color = $plugin->getSettings('fq_answer_color');
		$fq_question_size = $plugin->getSettings('fq_question_size');
        $fq_answer_size = $plugin->getSettings('fq_answer_size');
        $fq_plus_color = $plugin->getSettings('fq_plus_color');
        $fq_minus_color = $plugin->getSettings('fq_minus_color');
		$fq_header_color = $plugin->getSettings('fq_header_color');
		$fq_plus_enabled = $plugin->getSettings('fq_plus_enabled');
        $fq_meta_keywords = $plugin->getSettings('fq_meta_keywords');
        $fq_meta_description = $plugin->getSettings('fq_meta_description');
        
        
        $this->view->assign('fq_html', $fq_html);
        $this->view->assign('spectrum_js', $plugin->getPluginStaticUrl().'js/spectrum.js');
        $this->view->assign('frequent_questions_js', $plugin->getPluginStaticUrl().'js/frequent_questions.js');
        $this->view->assign('spectrum_css', $plugin->getPluginStaticUrl().'css/spectrum.css');
        $this->view->assign('settings_css', $plugin->getPluginStaticUrl().'css/settings.css');
        $this->view->assign('fq_link_title', $fq_link_title);
        $this->view->assign('fq_page_title', $fq_page_title);
        $this->view->assign('fq_plugin_enabled', $fq_plugin_enabled);
        $this->view->assign('fq_placing', $fq_placing);
        $this->view->assign('fq_expand', $fq_expand);
        $this->view->assign('fq_qa_display', $fq_qa_display);
        $this->view->assign('fq_target_blank', $fq_target_blank);
        $this->view->assign('fq_helper', "{shopFrequent_questionsPlugin::display()}");
        $this->view->assign('fq_question_color', $fq_question_color);
        $this->view->assign('fq_answer_color', $fq_answer_color);
        $this->view->assign('fq_question_size', $fq_question_size);
        $this->view->assign('fq_answer_size', $fq_answer_size);
    	$this->view->assign('fq_plus_color', $fq_plus_color);
        $this->view->assign('fq_minus_color', $fq_minus_color);
		$this->view->assign('fq_header_color', $fq_header_color);
		$this->view->assign('fq_plus_enabled', $fq_plus_enabled);
        $this->view->assign('fq_meta_keywords', $fq_meta_keywords);
        $this->view->assign('fq_meta_description', $fq_meta_description);
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
