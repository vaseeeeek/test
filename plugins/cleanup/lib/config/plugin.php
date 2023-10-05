<?php

return array(
    'name' =>'Cleanup',
    'version' => '1.300',
    'vendor' => '991739',
    'shop_settings'=> true,
    'img' => 'img/cleanup.png',
    'handlers' => array(
        'backend_order' => 'backendOrder',
    ),
    'description' => _wp('Clean up old entries')
);
