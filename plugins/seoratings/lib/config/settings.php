<?php

return [
  'slug' => [
    'title' => 'Url',
    'control_type' => waHtmlControl::INPUT,
    'value' => '',
  ],
  'breadcrumbs_category_title' => [
    'title' => 'Название категории',
    'control_type' => waHtmlControl::INPUT,
    'value' => 'Рейтинги',
  ],
  'ratings_list_meta_title' => [
    'title' => 'Meta title',
    'control_type' => waHtmlControl::INPUT,
    'value' => '',
  ],
  'ratings_list_meta_description' => [
    'title' => 'Meta description',
    'control_type' => waHtmlControl::TEXTAREA,
    'value' => '',
  ],
  'rating_css_styles' => [
    'title' => 'Дополнительные стили',
    'control_type' => waHtmlControl::TEXTAREA,
    'value' => '',
  ],
  'ratings_list_meta_keywords' => [
    'title' => 'Meta keywords',
    'control_type' => waHtmlControl::TEXTAREA,
    'value' => '',
  ],
  'category_badge' => [
    'title' => 'Бейджик в категории',
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
  ],
  'hooks' => [
    'title' => 'Выводить через хуки',
    'control_type' => waHtmlControl::GROUPBOX,
    'options' => [
      [
        'value' => 'frontend_product.block_aux',
        'title' => _w('frontend_product.block_aux'),
      ],
      [
        'value' => 'frontend_product.block',
        'title' => _w('frontend_product.block'),
      ],
      [
        'value' => 'frontend_nav',
        'title' => _w('frontend_nav'),
      ],
    ],
  ],
  'developer_templates_file' => [
    'title'         => 'Шаблоны разработчика',
    'description'   => 'Загрузить шаблоны помеченные как "для разработчиков", которые впоследствии можно удалить',
    'value'         => '',
    'control_type'  => waHtmlControl::FILE,
  ],
];
