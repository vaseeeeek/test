<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/16/16
 * Time: 3:23 AM
 */

class shopInvitePluginInvitationsModel extends waModel
{
    protected $table = 'shop_invite_invitations';

    /**
     * Primary key of the table
     * @var string
     */
    protected $id = 'code';

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

    public function add($data) {
        $user = wa()->getUser();
        $plugin = self::getPlugin();
        $settings = $plugin->getSettings();
        do {
            $data['code'] = uniqid();
            $data['datetime'] = date('Y-m-d H:i:s');
            $data['create_contact_id'] = $user->getId();
        }
        while (!$this->insert($data));

        if ($settings['send']) {
            $mail = new shopInvitePluginMail();
            $mail->send($data['code']);
        }
    }

    public function getAllInvitations($create_contact_id = null) {
        $where = '';

        if ($create_contact_id) {
            $where = ' WHERE i.create_contact_id = ' . intval($create_contact_id);
        }

        $invitations = $this->query(
            'SELECT i.*, cc.name AS category, c.name AS cname, oc.name AS oname FROM shop_invite_invitations i '
            .' LEFT JOIN wa_contact_category cc ON cc.id = i.category_id '
            .' LEFT JOIN wa_contact c ON c.id = i.contact_id '
            .' LEFT JOIN wa_contact oc ON oc.id = i.create_contact_id ' . $where
            .' ORDER BY i.datetime DESC '
        )->fetchAll();
        return $invitations;
    }
}