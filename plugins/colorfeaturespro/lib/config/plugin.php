<?php

return array(
  'name' => 'Настраиваемые характеристики цвета',
  'description' => 'Позволяет настроить вместо цвета градиент или изображение',
  'version' => '1.0',
  'vendor' => '1232794',
  'shop_settings' => true,
  'img' => 'img/ico16.png',
  'icons' => array(
    16 => 'img/ico16.png',
    32 => 'img/ico32.png',
  ),
  'frontend' => true,
  'handlers' => array(
    'frontend_products' => 'frontendProducts',
  ),
);
//EOF