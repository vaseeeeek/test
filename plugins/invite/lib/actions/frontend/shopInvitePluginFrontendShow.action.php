<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/17/16
 * Time: 1:50 AM
 */

class shopInvitePluginFrontendShowAction extends shopFrontendMyProfileAction
{
    public function execute()
    {
        $user = wa()->getUser();
        /**
         * @var shopInvitePlugin $plugin
         */
        $plugin = wa()->getPlugin('invite');
        $settings = $plugin->getSettings();

        if (!$user->getId()) {
            $this->redirect(wa()->getRouteUrl('/'));
        }

        $domain = wa()->getRouting()->getDomain();
        if (!isset($settings['domains'][$domain])) {
            $this->redirect(wa()->getRouteUrl('shop/frontend/my'));
        }

        $categories = shopInvitePlugin::getUserCategories();
        if (empty($categories)) {
            $this->redirect(wa()->getRouteUrl('shop/frontend/my'));
        }

        $allowed_cats = array();
        foreach ($categories as $key => $cat) {
            //var_dump($settings['categories'][$cat['id']]);
            if (isset($settings['categories'][$key])) {
                $allowed_cats = array_merge($allowed_cats, array_keys($settings['categories'][$cat['id']]));
            }
        }

        $allowed_cats = array_unique($allowed_cats);
        $category_model = new waContactCategoryModel();
        $cats = $category_model->select('*')->where('id IN('.implode(',', $allowed_cats).')')->fetchAll('id');
        $this->view->assign('user_categories', $cats);

        $shopUrl = wa()->getAppUrl('shop');
        $this->view->assign('shop_url', $shopUrl);

        $invitations_model = new shopInvitePluginInvitationsModel();
        $invitations = $invitations_model->getAllInvitations($user->getId());
        $this->view->assign('invitations', $invitations);
        $this->view->assign('contacts_url', wa()->getAppUrl('contacts'));
        $this->view->assign('inviteDataPath', wa()->getDataUrl('plugins/invite/', TRUE));
        $settings = $plugin->getSettings();
        $this->view->assign('settings', $settings);
        $this->view->assign('plugin_id', 'invite');

        $this->setLayout(new shopFrontendLayout());
        $this->setTemplate($plugin->getPluginPath() . '/templates/actions/frontend/Invites.html');
        $this->getResponse()->setTitle(_wp('Invitations'));
    }
}