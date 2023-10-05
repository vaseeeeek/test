<?php
return array(
    'shop_emailform_emails' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'email' => array('varchar', 255, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'phone' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            //'email' => array('email', 'unique' => 1), удален с версии 1.03
        ),
    ),
);
