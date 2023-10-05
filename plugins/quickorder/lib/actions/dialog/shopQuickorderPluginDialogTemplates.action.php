<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginDialogTemplatesAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::get('id');

        $storefront = waRequest::get('storefront', 'all');
        $settings = (new shopQuickorderPluginSettingsModel())->getSettings($storefront);
        $templates = (new shopQuickorderPluginHelper())->getTemplates($settings);

        if (isset($templates[$id])) {
            if (!empty($templates[$id]['changed'])) {
                $template = $templates[$id]['changed'];
                $this->view->assign('is_changed', 1);
            } else {
                $template = file_get_contents($templates[$id]['path']);
            }
            $this->view->assign('template_key', $id);
            $this->view->assign('template', $template);
        }
        $this->view->assign('templates', $templates);
        $this->view->assign('storefront', $storefront);
    }
}