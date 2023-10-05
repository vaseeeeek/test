<?php

class shopSeoratingsPluginSettingsAction extends waViewAction
{
  public function execute()
  {
    $hooks = [
      'frontend_product.block_aux' => 'frontend_product.block_aux',
      'frontend_product.block' => 'frontend_product.block',
      'frontend_nav' => 'frontend_nav'
    ];

    $plugin = wa('shop')->getPlugin('seoratings');
    $this->view->assign('plugin', $plugin);
    $this->view->assign('hooks', $hooks);
    $this->view->assign('settings', $plugin->getSettings());
  }
}
