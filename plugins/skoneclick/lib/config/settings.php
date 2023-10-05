<?php

return array(
    'active' => array(
        'value' => "1",
        'title' => "Активность плагина",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Включен",
            ),
            array(
                "value" => "0",
                "title" => "Отключен",
            ),
        ),
    ),

    'add_comment' => array(
        'value' => "0",
        'title' => "Добавить к форме поле комментария",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        ),
    ),

    'comment_phrase' => array(
        'value' => 'Оформлено через "Купить в 1 клик"',
        'title' => "Добавлять фразу в комментарий к заказу",
        'control_type' => waHtmlControl::INPUT,
    ),

    'personal_data_active' => array(
        'value' => "1",
        'title' => "Добавить чекбокс согласия на обработку персональных данных",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        ),
    ),

    'personal_data_default' => array(
        'value' => "1",
        'title' => "Чекбокс согласия на обработку персональных данных изначально активен",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        ),
    ),

    'personal_data_text' => array(
        'value' => "Я соглашаюсь на обработку персональных данных",
        'title' => "Текст чекбокса согласия на обработку персональных данных",
        'control_type' => waHtmlControl::TEXTAREA,
    ),

    'personal_data_error' => array(
        'value' => "Необходимо согласиться с политикой обработки персональных данных",
        'title' => "Текст ошибки при отсутствии согласия на обработку персональных данных",
        'control_type' => waHtmlControl::TEXTAREA,
    ),

    'cart_goods_show' => array(
        'value' => "1",
        'title' => "Выводить товары в форме оформления из корзины",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        ),
    ),

    'coupon_show' => array(
        'value' => "1",
        'title' => "Добавить возможность использования купона",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        ),
    ),

    'minimization' => array(
        'value' => "1",
        'title' => "Подключать минимизированные скрипты",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        ),
    ),

);
