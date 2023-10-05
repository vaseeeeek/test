<?php

return array(

    'dadata_token' => array(
        'title' => 'API-ключ',
        'description' => 'Можно получить в личном кабинете DaData: <a href="https://dadata.ru/profile/">https://dadata.ru/profile/</a>. Нет ключа, нет подсказок по ФИО и городу.',
        'value' => '',
        'control_type' => waHtmlControl::INPUT
    ),

    'redirect' => array(
        'title' => 'Переадресация',
        'description' => 'Переадресация со старого оформления заказа /checkout/ на новое /buy/',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX
    ),

    'replace_city' => array(
        'title' => 'Префикс города',
        'description' => 'Поле "Город" будет очищено от указанных префиксов. Указывать через пробел. Точка в конце не обязательна.',
        'value' => 'г. дп. кп. п. пос. д. дер. массив пгт п/о рп с/а с/о с село деревня',
        'control_type' => waHtmlControl::INPUT
    ),


    'hints' => array(
        'title' => '',
        'description' => '',
        'value' => 'Подсказки',
        'control_type' => waHtmlControl::TITLE
    ),
    'hint_zip' => array(
        'title' => 'Индекс',
        'description' => '',
        'value' => 'Если вы не знаете свой индекс — поставьте прочерк.',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'hint_region' => array(
        'title' => 'Регион',
        'description' => '',
        'value' => '',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'hint_city' => array(
        'title' => 'Город',
        'description' => '',
        'value' => 'Введите точное название города или населенного пункта одним словом. Например — <strong>Москва</strong>',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'hint_street' => array(
        'title' => 'Улица',
        'description' => '',
        'value' => '',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'placeholders' => array(
        'title' => '',
        'description' => '',
        'value' => 'Плейсхолдеры',
        'control_type' => waHtmlControl::TITLE
    ),
    'placeholder_zip' => array(
        'title' => 'Индекс',
        'description' => '',
        'value' => '155037',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'placeholder_region' => array(
        'title' => 'Регион',
        'description' => '',
        'value' => '&lt;выберите регион&gt;',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'placeholder_city' => array(
        'title' => 'Город',
        'description' => '',
        'value' => 'Москва',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'placeholder_street' => array(
        'title' => 'Город',
        'description' => '',
        'value' => 'просп. Калинина',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'placeholder_house' => array(
        'title' => 'Дом',
        'description' => '',
        'value' => '1/4',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'placeholder_korp' => array(
        'title' => 'Корпус',
        'description' => '',
        'value' => '3',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),
    'placeholder_apt' => array(
        'title' => 'Квартира',
        'description' => '',
        'value' => '123',
        'class' => 'long',
        'control_type' => waHtmlControl::INPUT
    ),

);
