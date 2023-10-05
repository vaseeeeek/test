<?php

return [
  'drop_out_of_stock' => [
    'title' => 'Скрыть товары, которых нет на складе',
    'control_type' => waHtmlControl::CHECKBOX,
  ],
  'is_new_status' => [
    'title' => 'Новинки',
    'control_type' => waHtmlControl::CHECKBOX,
  ],
  'is_new' => [
    'title' => 'Новинки: период',
    'control_type' => waHtmlControl::SELECT,
    'value' => '-1 month',
    'options' => [
      [
        'value' => '-1 day',
        'title' => 'День',
      ],
      [
        'value' => '-1 week',
        'title' => 'Неделя',
      ],
      [
        'value' => '-2 weeks',
        'title' => '2 недели',
      ],
      [
        'value' => '-3 weeks',
        'title' => '3 недели',
      ],
      [
        'value' => '-1 month',
        'title' => 'Месяц',
      ],
      [
        'value' => '-2 months',
        'title' => '2 месяца',
      ],
      [
        'value' => '-3 months',
        'title' => '3 месяца',
      ],
      [
        'value' => '-4 months',
        'title' => '4 месяца',
      ],
      [
        'value' => '-5 months',
        'title' => '5 месяцев',
      ],
      [
        'value' => '-6 months',
        'title' => 'Полгода',
      ],
      [
        'value' => '-1 year',
        'title' => 'Год',
      ],
    ],
  ],
  'is_new_min' => [
    'title' => 'Новинки: минимум товаров',
    'control_type' => waHtmlControl::INPUT,
    'value' => '10',
  ],
  'is_discount_status' => [
    'title' => 'Скидки',
    'control_type' => waHtmlControl::CHECKBOX,
  ],
  'is_discount_min' => [
    'title' => 'Скидки: минимум товаров',
    'control_type' => waHtmlControl::INPUT,
    'value' => '10',
  ],
];