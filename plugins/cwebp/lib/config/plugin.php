<?php
return array(
    'name' => 'WEBP Images',
    'img' => 'img/cwebp.png',
    'version' => '4.3.0',
    'vendor' => '1027956',
    'handlers' =>
        array(
            'routing' => 'frontendHook',
            'product_images_delete' => 'deleteImage',
            '*' => array(
                array(
                    'event_app_id' => '*',
                    'event'        => 'routing',
                    'class'        => 'shopCwebpPlugin',
                    'method'       => 'frontendHook'
                )
            )
        ),
);
