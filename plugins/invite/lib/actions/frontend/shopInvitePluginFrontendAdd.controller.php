<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/19/16
 * Time: 12:04 AM
 */

class shopInvitePluginFrontendAddController extends waJsonController
{
    /**
     * @var shopInvitePlugin $plugin
     */
    private static $plugin;

    /**
     * @var waView $view
     */
    private static $view;

    public function execute()
    {
        $user = wa()->getUser();
        if ($user) {

            $settings = self::$plugin->getSettings();

            $domain = wa()->getRouting()->getDomain();
            if (!isset($settings['domains'][$domain])) {
                $this->setError(_wp('Access denied'));
                return false;
            }

            $categories = shopInvitePlugin::getUserCategories();
            if (empty($categories)) {
                $this->setError(_wp('Access denied'));
                return false;
            }

            $allowed_cats = array();
            foreach ($categories as $key => $cat) {
                if (isset($settings['categories'][$key])) {
                    $allowed_cats = array_merge($allowed_cats, array_keys($settings['categories'][$cat['id']]));
                }
            }

            $invite = waRequest::request('invite');
            $errors = $this->validate($invite);

            if (!empty($invite) && isset($invite['category_id'])) {
                if (!in_array(intval($invite['category_id']), $allowed_cats)) {
                    $this->setError(_wp('Access denied'));
                    return false;
                }
            }

            if (!empty($errors) || isset($config_error)) {
                if (isset($config_error)) {
                    $errors['config'] = $config_error;
                }
                $this->setError($errors);
            }
            else {
                $invitations_model = new shopInvitePluginInvitationsModel();
                $invitations_model->add($invite);

                $invitations = $invitations_model->getAllInvitations($user->getId());
                self::$view->assign('invitations', $invitations);
                self::$view->assign('contacts_url', wa()->getAppUrl('contacts'));
                $this->response = array(
                    'invitations_table' => self::$view->fetch(self::$plugin->getPluginPath() . '/templates/actions/frontend/Invitations_table.html'),
                );
            }
        }
        else {
            $this->setError(_wp('Access denied'));
        }
    }

    public function __construct($params = null)
    {
        self::$plugin = wa('shop')->getPlugin('invite');
        self::$view = wa()->getView();
    }

    private function validate($invite) {
        $errors = array();
        if (!filter_var($invite['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = _wp('Invalid e-mail');
        }
        return $errors;
    }
}
