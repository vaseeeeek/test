<?php
return array(
    'shop_frequent_questions' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'question' => array('text', 'null' => 0),
        'answer' => array('text', 'null' => 0),
        'enable' => array('int', 1, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);