<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginDialogImportAppearanceAction extends waViewAction
{
    public function execute()
    {
        $sets = (new shopProductsetsPluginModel())->select('*')->where('id <> i:id', ['id' => waRequest::get('id', 0)])->fetchAll('id');
        $this->view->assign('sets', $sets);
    }
}