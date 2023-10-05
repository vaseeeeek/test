<?php
/**
 * Restore Original Template controller
 *
 * @package Syrattach/controller
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2014, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopSyrattachPluginSettingsOriginaltemplateController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }

        $template_path = 'plugins/syrattach/templates/frontend_product.html';
        $original_template = waSystem::getInstance()->getAppPath($template_path, 'shop');
        $modified_template = waSystem::getInstance()->getDataPath($template_path, FALSE, 'shop', FALSE);
        $this->getResponse()->addHeader('Content-type', 'application/json');

        waFiles::delete($modified_template);

        $this->response['template'] = file_get_contents($original_template);
    }
}
