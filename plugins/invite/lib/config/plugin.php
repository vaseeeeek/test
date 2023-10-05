<?php
return array (
    'name' => _wp('invitations'),
    'description' => _wp('Allows you to register by invitation'),
    'icon' => 'img/invite16.png',
    'img' => 'img/invite16.png',
    'version' => '1.0.4',
    'vendor' => '964801',
    'shop_settings' => true,
    'frontend' => true,
    'handlers' =>
        array (
            'signup'            => 'signupHandler',
            'frontend_my_nav'   => 'frontendMy',
            'frontend_head'     => 'frontendHead',
        ),
);
