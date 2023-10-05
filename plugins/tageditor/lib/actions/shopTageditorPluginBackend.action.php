<?php

class shopTageditorPluginBackendAction extends waViewAction
{
    public function execute()
    {
        $tag_count = shopTageditorPluginModels::shopTag()->countAll();

        if ($tag_count > 0 && $tag_count <= shopTageditorPlugin::BACKEND_TAG_LIMIT) {
            $tags = shopTageditorPluginModels::shopTag()
                ->select('*')
                ->order('IF(count = 0, 0, 1), name')
                ->fetchAll();
        } else {
            $tags = null;
        }

        $this->view->assign('tag_limit', shopTageditorPlugin::BACKEND_TAG_LIMIT);
        $this->view->assign('tags_exist', $tag_count > 0);
        $this->view->assign('tags', $tags);
        $this->view->assign('version', wa()->getPlugin('tageditor')->getVersion());
        $this->view->assign('is_ajax', !empty($this->params['is_ajax']));
    }
}
