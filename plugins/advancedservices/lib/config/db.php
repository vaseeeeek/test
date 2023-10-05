<?php
return array(
    'shop_advancedservices' => array(
        
        'id' => array('int', 11, 'null' => 0),
        'enabled' => array('int', 11, 'null' => 0),
        'link' => array('varchar', 255, 'null' => 0),
        'category_filter' => array('varchar', 255, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'variant' => array('int', 11, 'null' => 0),
        'popup' => array('int', 11, 'null' => 0),
        'ondefault' => array('int', 11, 'null' => 0),
        'divider' => array('int', 11, 'null' => 0),
        'tooltip' => array ('text', 'null' => 0),
        ':keys' => array(
            
            
            'id' => array('id', 'unique' => 1),   
            
        ),
     ),
);
