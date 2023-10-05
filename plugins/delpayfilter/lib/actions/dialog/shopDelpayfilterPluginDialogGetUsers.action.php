<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPluginDialogGetUsersAction extends waViewAction
{

    public function execute()
    {
        // Категории контакта
        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $view_model = new contactsViewModel();
            $contact_categories = $view_model->getAllViews(null, true);
            contactsViewModel::setIcons($contact_categories);
        } catch (Exception $ex) {
            $ccm = new waContactCategoryModel();
            $contact_categories = $ccm->getAll('id');
        }

        // Всего контактов
        $cm = new waContactModel();
        $count = $cm->countAll();

        $this->view->assign('categories', $contact_categories);
        $this->view->assign('count_all', $count);
        $this->view->assign('plugin_url', wa()->getPlugin('delpayfilter')->getPluginStaticUrl());
    }

}
