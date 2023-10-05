<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/7/16
 * Time: 10:47 PM
 */

class shopInvitePluginSettingsAction extends waViewAction
{
    /**
     * @var shopInvitePlugin $plugin
     */
    private static $plugin;

    public function execute()
    {
        $control_params = array(
            'id'                  => waRequest::get('id'),
            'namespace'           => 'shop_invite',
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper'     => '<div class="name">%s</div><div class="value">%s %s</div>'
        );

        $ccm = new waContactCategoryModel();
        $categories = $ccm->getAll();
        $this->view->assign('user_categories', $categories);

        $invitations_model = new shopInvitePluginInvitationsModel();
        $invitations = $invitations_model->getAllInvitations();
        $this->view->assign('invitations', $invitations);

        $this->view->assign('contacts_url', wa()->getAppUrl('contacts'));
        $this->view->assign('inviteDataPath', wa()->getDataUrl('plugins/invite/', TRUE));
        $settings = self::$plugin->getSettings();

        $this->view->assign('settings', $settings);
        $this->view->assign('plugin_id', 'invite');
        $this->view->assign('tabs', shopInvitePlugin::getTabs());
        $this->view->assign('plugin_settings_controls', $this->getPluginSettingsControls($control_params));
    }

    public function __construct($params = NULL)
    {
        $plugin = wa('shop')->getPlugin('invite');
        self::$plugin = $plugin;
        parent::__construct($params);
    }

    /**
     * Возвращает элементы формы для вкладки Samples Settings
     *
     * @param array $params
     * @return string
     */
    private function getPluginSettingsControls($params)
    {
        $controls = array(
            'basic'     => self::$plugin->getControls($params + array('subject' => 'basic_settings')),
            'invites'   => self::$plugin->getControls($params + array('subject' => 'invites_settings')),
            'templates' => self::$plugin->getControls($params + array('subject' => 'templates_settings')),
            'info'      => self::$plugin->getControls($params + array('subject' => 'info_settings')),
        );
        return $controls;
    }
}