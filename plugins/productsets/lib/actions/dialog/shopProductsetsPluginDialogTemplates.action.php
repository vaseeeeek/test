<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginDialogTemplatesAction extends waViewAction
{
    public function execute()
    {
        $id = waRequest::get('id');
        $templates = (new shopProductsetsPluginHelper())->getTemplates();

        if (isset($templates[$id])) {
            if (!empty($templates[$id]['changed'])) {
                $template = file_get_contents($templates[$id]['changed']);
                $this->view->assign('is_changed', 1);
            } else {
                $template = file_get_contents($templates[$id]['path']);
            }
            $this->view->assign('template_key', $id);
            $this->view->assign('template', $template);
        }
    }
}