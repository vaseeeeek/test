<?php
return array(
    'api_key'  => array(
        'title'        => /*_wp*/('Akismet API Key'),
        'description'  => array(
            /*_wp*/('Get an API key for your domain at Akismet website'),
            'https://akismet.com/signup/'
        ),
        'value'        => '', // значение по умолчанию
        'control_type'=> waHtmlControl::INPUT,
    ),
);