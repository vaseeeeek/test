<?php

return array(
  'name' => 'SEO-Топы и списки',
  'description' => 'Создание товарных топов и списков',
  'img' => 'img/rating.png',
  'vendor' => '953700',
  'version' => '2.0.0',
  'shop_settings' => true,
  'frontend' => true,
  'handlers' => array(
    'backend_products' => 'backendProducts',
    'frontend_product' => 'frontendProduct',
    'frontend_products' => 'frontendProducts',
    'frontend_nav' => 'frontendNav',
  )
);
