<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginDialogSettingsAction extends waViewAction
{

    public function execute()
    {
        $this->view->assign('settings', shopAutobadgeHelper::getSettings());
    }

}
