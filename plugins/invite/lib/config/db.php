<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/16/16
 * Time: 3:05 AM
 */

return array(
    'shop_invite_invitations' => array(
        'code' => array('varchar', 13, 'null' => 0),
        'email' => array('varchar', 100, 'null' => 1),
        'category_id' => array('int', 11, 'null' => 1),
        'create_contact_id' => array('int', 11, 'null' => 1),
        'contact_id' => array('int', 11, 'null' => 1),
        'description' => array('text', 'null' => 1),
        'datetime' => array('datetime', 'null' => 1),
        'confirmed' => array('tinyint', 1, 'default' => 0),
        'registered' => array('tinyint', 1, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => array('code'),
        ),
    ),
);
