<?php

/**
 * Settings Form controller
 *
 * @package Syrattach/controller
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.0.1
 * @since 1.0.1
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
class shopSyrattachPluginSettingsAction extends waViewAction
{
    /** @var shopSyrattachPlugin */
    private $plugin;

    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }

        $this->plugin = wa('shop')->getPlugin('syrattach');
        $this->getResponse()->setTitle(_wp('Attached Files Plugin Settings'));
        $this->view->assign('settings_controls', $this->plugin->getControls(array(
            'id'                  => 'syrattach',
            'namespace'           => 'shop_syrattach',
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper'     => '<div class="name">%s</div><div class="value">%s %s</div>'
        )));
    }
}