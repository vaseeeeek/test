<?php

class shopServicesetsPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $modelGroups = new shopServicesetsPluginGroupsModel();
        $this->view->assign('grouplist', $modelGroups->getAll());
        $this->view->assign('plugin_url', wa()->getPlugin('servicesets')->getPluginStaticUrl());
        $this->view->assign('servicelist', $this->getFullServicesList());
        $this->view->assign('variantslist', $this->getFullVariantsList());
    }

    public function getFullServicesList()
    {
        $model = new waModel();
        $services = $model->query("SELECT s.id, s.name, t.id_service, t.description, t.format_one, t.format_two, t.image_one, t.image_two FROM shop_service s LEFT JOIN shop_servicesets_services t ON s.id = t.id_service;
")->fetchAll();
        return $services;
    }
    public function getFullVariantsList()
    {
        $model = new waModel();
        $variants = $model->query("SELECT s.id, s.name, s.service_id, t.description, t.format_one, t.format_two, t.image_one, t.image_two FROM shop_service_variants s LEFT JOIN shop_servicesets_variants t ON s.id = t.id_variants")->fetchAll();
        return $variants;
    }
}