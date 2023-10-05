<?php
return array(
    'shop_favorites' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('contact_id', 'product_id'),
        ),
    ),
);