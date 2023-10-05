<?php
return array(
    'shop_cwebp_queue' => array(
        'source' => array('varchar', 191, 'null' => 0),
        'destination' => array('varchar', 191, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'source',
            'source' => array('source', 'unique' => 1),
        ),
    ),
);
