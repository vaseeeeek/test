<?php

/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/18/16
 * Time: 1:37 AM
 */
class shopInvitePluginMail
{
    /**
     * @var waView $view
     */
    private static $view;
    private static function getView()
    {
        if (!isset(self::$view)) {
            self::$view = waSystem::getInstance()->getView();
        }
        return self::$view;
    }

    /**
     * @var shopInvitePlugin $plugin
     */
    private static $plugin;
    private static function getPlugin()
    {
        if (!isset(self::$plugin)) {
            self::$plugin = wa()->getPlugin('invite');
        }
        return self::$plugin;
    }

    public function send($code) {
        $plugin = self::getPlugin();
        $settings = $plugin->getSettings();
        $view = self::getView();
        $invitations_model = new shopInvitePluginInvitationsModel();

        $invite = $invitations_model->getByField(
            array(
                'code'          => $code,
                'registered'    => 0,
            )
        );

        if (!empty($invite)) {
            /**
             * @var shopConfig $config
             */
            $config = wa('shop')->getConfig();
            $from = $config->getGeneralSettings('email');
            $name = $config->getGeneralSettings('name');
            $subject = $settings['subj'];

            $to = $invite['email'];

            $template_path = wa()->getDataPath('plugins/invite/templates/Mail.html', false, 'shop', true);
            if (!file_exists($template_path)) {
                $template_path = wa()->getAppPath('plugins/invite/templates/Mail.html', 'shop');
            }

            $contact = new waContact($invite['create_contact_id']);
            $owner = array(
                'name' => $contact->getName(),
            );
            $view->assign('owner', $owner);

            $domain = array(
                //'name'  => wa()->getRouting()->getDomain(null, true),
                //'link'  => '<a href="'.wa('shop')->getRootUrl(true).'" target="_blank">' . wa()->getRouting()->getDomain(null, true) . '</a>',
                'url'  => wa('shop')->getRootUrl(true),
                'name'  => $settings['domain'],
                'link'  => '<a href="'.wa('shop')->getRootUrl(true).'" target="_blank">' . $settings['domain'] . '</a>',
            );
            $view->assign('domain', $domain);

            $shopUrl = wa('shop')->getRouteUrl('shop/frontend', array(), true);

            $invite['url'] = $shopUrl . 'signup/?invite_code=' . $invite['code'];
            $invite['link'] = '<a href="'. $shopUrl . 'signup/?invite_code=' . $invite['code'] .'" target="_blank">' . $shopUrl . 'signup/?invite_code=' . $invite['code'] . '</a>';
            $view->assign('invite', $invite);

            $body = $view->fetch($template_path);

            try {
                $message = new waMailMessage($subject, $body);
                $message->addReplyTo($to);
                $message->setFrom($from, $name);
                $message->setTo($to);
                $status = $message->send();

                if ($settings['log'] == 1) {
                    if ($status) {
                        waLog::log(sprintf(_wp('Code %s has been sent successfully'), $code), 'invite_mail.log');
                    }
                    else {
                        waLog::log(sprintf(_wp('Code %s not been sended'), $code), 'invite_mail.log');
                    }
                }
                return true;
            } catch (Exception $e) {
            }
        }
        else return false;
    }
}