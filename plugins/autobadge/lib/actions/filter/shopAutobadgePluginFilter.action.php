<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginFilterAction extends waViewAction
{

    public function execute()
    {
        $id = waRequest::get("id", 0, waRequest::TYPE_INT);

        $filter = (new shopAutobadgePluginModel())->getFilter($id);
        
        // Шаблоны наклеек
        $templates = (new shopAutobadgeTemplatePluginModel())->getAll('id');
        if ($templates) {
            foreach ($templates as $db_id => $db) {
                $templates[$db_id]['settings'] = unserialize($db['settings']);
            }
        }
        
        // Загруженные изображения
        $uploaded_images = waFiles::listdir(wa('shop')->getDataPath('plugins/autobadge/', true, 'site'), true);
        
        $this->view->assign('target', (new shopAutobadgeData())->getTargetData());
        $this->view->assign('currencies', $this->getConfig()->getCurrencies());
        $this->view->assign("filter", $filter);
        $this->view->assign("templates", $templates);
        $this->view->assign("site_access", wa('shop')->getUser()->getRights('site', 'backend'));
        $this->view->assign("image_url", wa('shop')->getDataUrl('plugins/autobadge/', true, 'site', true));
        $this->view->assign("uploaded_images", $uploaded_images);
        $this->view->assign("default_ribbons", include(dirname(__FILE__) . '/../../config/config.php'));
        $this->view->assign('plugin_url', wa('shop')->getPlugin('autobadge')->getPluginStaticUrl(true));
    }
}
