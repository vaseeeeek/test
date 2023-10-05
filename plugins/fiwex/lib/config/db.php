<?php
return array(
 'shop_fiwex_feat_values_explanations' => array(
        'id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'feature_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'explanations' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
	
  'shop_fiwex_feature_explanations' => array(
        'id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'explanations' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    )
);