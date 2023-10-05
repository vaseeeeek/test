<?php

return array(
    'name' => /*_wp*/('Frequent questions'),
    'description' => /*_wp*/('Frequently asked questions for users.'),
    'vendor'=>'667213',
    'version'=>'2.0.0',
    'img'=>'img/frequent_questions.png',
    'shop_settings' => true,
    'frontend'    => true,
    'icons'=>array(
        16 => 'img/frequent_questions.png',
    ),
    'handlers' => array(
        'frontend_nav' => 'frontendPage',
        'frontend_head' => 'frontendHead',
    ),

);
//EOF