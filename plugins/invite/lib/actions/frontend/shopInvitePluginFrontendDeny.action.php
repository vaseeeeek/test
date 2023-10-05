<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/18/16
 * Time: 10:35 PM
 */

class shopInvitePluginFrontendDenyAction extends shopFrontendAction
{
    public function execute()
    {
        $this->setLayout(new shopFrontendLayout());

        $template_path = wa()->getDataPath('plugins/invite/templates/Deny.html', false, 'shop', true);
        if (!file_exists($template_path)) {
            $template_path = wa()->getAppPath('plugins/invite/templates/Deny.html', 'shop');
        }
        $this->setTemplate($template_path);
        $this->getResponse()->setTitle(_wp('Deny'));
    }
}