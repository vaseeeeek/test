<?php

class shopCleanupPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $loc = array();
        foreach (array(
                    'Are you sure you want to remove',
                    'product images from',
                    'tags',
                    'categories',
                    'actions',
                    'Reset workflow?',
                    'No actions selected.',
                    'No tags selected.',
                    'No categories selected.'
                    ) as $key) {
            $loc[$key] = _wp($key);
        }
        $this->view->assign('loc', $loc);
        
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $this->view->assign('app_config', $app_config->getPluginPath('cleanup'));
        $version = $app_config->getPluginInfo('cleanup');
        $this->view->assign('version', $version['version']);

        $plugin_path=wa()->getAppStaticUrl('shop', true).'plugins/cleanup';
        $this->view->assign('plugin_path', $plugin_path);
        //main
        $this->view->assign('orders', shopCleanupPluginBackendActions::orders("counter"));
        $this->view->assign('reviews', shopCleanupPluginBackendActions::reviews("counter"));
        $this->view->assign('images', shopCleanupPluginBackendActions::images("counter"));
        $this->view->assign('badges', shopCleanupPluginBackendActions::badges("counter"));
        $set_model = new shopSetModel();
        $this->view->assign('sets', $set_model->getAll());
        $type_model = new shopTypeModel();
        $this->view->assign('types', $type_model->getTypes());
        //need to optimize for large image sites
        //$this->view->assign('missingimages', shopCleanupPluginBackendActions::missingImages());
        
        //categories
        $this->view->assign('categories', shopCleanupPluginBackendActions::showcategory());
        
        //tags
        $this->view->assign('tags', shopCleanupPluginBackendActions::showtags());

        //workflow
        $orig_wfactions = array ('create','process', 'pay','ship', 'refund', 'edit', 'delete', 'restore', 'complete', 'comment', 'callback', 'message');
        $app_config=wa()->getConfig()->getAppConfig('shop');
        $path=$app_config->getConfigPath('workflow.php', true);
        if (file_exists($path)) {
            $apps=include($path);
            $actions = array_diff_key($apps['actions'], array_flip($orig_wfactions));
            if (empty($actions)) {
                unset($actions);
            }
            $this->view->assign(compact('actions'));
        }
        
    }
}
