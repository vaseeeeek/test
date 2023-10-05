<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPluginFilterAction extends waViewAction
{

    public function execute()
    {
        $id = waRequest::get("id", 0, waRequest::TYPE_INT);
        $model = new shopDelpayfilterPluginModel();

        $filter = $model->getFilter($id);

        $this->view->assign('currencies', $this->getConfig()->getCurrencies());
        $this->view->assign("filter", $filter);
        $this->view->assign('plugin_url', wa()->getPlugin('delpayfilter')->getPluginStaticUrl());
    }

}
