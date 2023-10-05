<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/16/16
 * Time: 1:28 AM
 */

class shopInvitePluginBackendAddController extends waJsonController
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
        if (wa()->getUser()->getRights('shop', 'orders')) {
            $invite = waRequest::post('invite');
            $errors = $this->validate($invite);

            if (!empty($errors) || isset($config_error)) {
                if (isset($config_error)) {
                    $errors['config'] = $config_error;
                }
                $this->setError($errors);
            }
            else {
                $invitations_model = new shopInvitePluginInvitationsModel();
                $invitations_model->add($invite);

                $invitations = $invitations_model->getAllInvitations();
                self::$view->assign('invitations', $invitations);
                self::$view->assign('contacts_url', wa()->getAppUrl('contacts'));
                $this->response = array(
                    'invitations_table' => self::$view->fetch(self::$plugin->getPluginPath() . '/templates/actions/settings/Invitations_table.html'),
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
