<?php
return array(
    'shop_listfeatures_feature' => array(
        'settlement' => array('varchar', 255, 'null' => 0),
        'set_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'feature_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'meta_keywords' => array('text', 'null' => 0),
        'meta_description' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('settlement', 'set_id', 'feature_id'),
        ),
    ),
);
