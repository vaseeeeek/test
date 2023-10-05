<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginBackendEditAction extends waViewAction
{

    public function execute()
    {
        // Получаем ID комплекта
        $id = waRequest::get('id', '', waRequest::TYPE_INT);
        $set = [];

        if ($id) {
            $psm = new shopProductsetsPluginModel();
            $set = $psm->getSet($id);

            $data_class = (new shopProductsetsData())->getProductData();
            $sku_ids = $data_class->collectProductSkuIds($set);

            if ($sku_ids) {
                $set = (new shopProductsetsProductData($sku_ids))->normalizeProducts($set);
            }
        }
        $this->view->assign('pset', $set);

        $helper = new shopProductsetsPluginHelper();
        $plugin = wa()->getPlugin('productsets');

        $this->view->assign('currencies', $this->getConfig()->getCurrencies());
        $this->view->assign('lang', substr(wa()->getLocale(), 0, 2));
        $this->view->assign('storefronts', $helper->getStorefronts());
        $this->view->assign('old_settings', $helper->getSettings());
        $this->view->assign('version', $plugin->getVersion());
        $this->view->assign('plugin_url', $plugin->getPluginStaticUrl());

        $this->setLayout(new shopProductsetsPluginBackendLayout());
    }



}
