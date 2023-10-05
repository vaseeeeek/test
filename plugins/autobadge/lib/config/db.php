<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    'shop_autobadge' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 500, 'null' => 0, 'default' => ''),
        'conditions' => array('text'),
        'target' => array('text'),
        'status' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
        ':options' => array('engine' => 'MyISAM')
    ),
    'shop_autobadge_params' => array(
        'autobadge_id' => array('int', 11, 'null' => 0),
        'field' => array('varchar', 50, 'null' => 0),
        'ext' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('autobadge_id', 'field', 'ext'),
        ),
        ':options' => array('engine' => 'MyISAM')
    ),
    'shop_autobadge_template' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'settings' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
        ':options' => array('engine' => 'MyISAM')
    ),
);
