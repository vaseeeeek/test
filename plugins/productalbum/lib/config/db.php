<?php
return array(
    'shop_productalbum' => array(
        'product_id' => array('int', 11, 'null' => 0),
        'album_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('product_id', 'album_id'),
        ),
    ),
);