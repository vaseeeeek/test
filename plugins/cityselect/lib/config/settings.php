<?php
/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
return array(

    //Системыне настройки для внутреннего использования
    'templates' => 'template,template_change',

    'current_route' => '0',

    'in_admin' => '1',

    //Настройки для каждой витрины
    'routes' => array(
        'value' => array(
            0 => array(
                'enable' => '1',
                'enable_plugin' => '1',

                'disable_auto' => 0,

                'disable_order_redirect' => 1,

                'token' => '',

                'bounds' => 'city',

                'notifier' => '',
                'notifier_custom' => array('checkout', 'signup', 'onestep'),

                'default_city' => 'Москва',
                'default_region' => '77',
                'default_zip' => '101000',

                'hook' => 'header',

                'in_checkout' => '1',
                'in_custom_form' => '.wa-signup-form-fields,.quickorder-form,#storequickorder .wa-form',
                'in_custom_city' => '[name="example"]',

                'user_template' => '0',
                'template' => '',
                'template_change' => '',

                'user_css' => 0,

                'plugin_dp' => 1,

                'cities' => array(
                    0 =>
                        array(
                            'city' => 'Абакан',
                            'region' => '19',
                            'zip' => '655000',
                            'bold' => 0,
                        ),
                    1 =>
                        array(
                            'city' => 'Алушта',
                            'region' => '91',
                            'zip' => '298500',
                            'bold' => 0,
                        ),
                    2 =>
                        array(
                            'city' => 'Барнаул',
                            'region' => '22',
                            'zip' => '656000',
                            'bold' => 0,
                        ),
                    3 =>
                        array(
                            'city' => 'Волгоград',
                            'region' => '34',
                            'zip' => '400000',
                            'bold' => 1,
                        ),
                    4 =>
                        array(
                            'city' => 'Воронеж',
                            'region' => '36',
                            'zip' => '394000',
                            'bold' => 1,
                        ),
                    5 =>
                        array(
                            'city' => 'Грозный',
                            'region' => '20',
                            'zip' => '364000',
                            'bold' => 0,
                        ),
                    6 =>
                        array(
                            'city' => 'Дмитровск',
                            'region' => '57',
                            'zip' => '303240',
                            'bold' => 0,
                        ),
                    7 =>
                        array(
                            'city' => 'Екатеринбург',
                            'region' => '66',
                            'zip' => '620000',
                            'bold' => 1,
                        ),
                    8 =>
                        array(
                            'city' => 'Заводоуковск',
                            'region' => '72',
                            'zip' => '627140',
                            'bold' => 0,
                        ),
                    9 =>
                        array(
                            'city' => 'Ижевск',
                            'region' => '18',
                            'zip' => '426000',
                            'bold' => 0,
                        ),
                    10 =>
                        array(
                            'city' => 'Ишим',
                            'region' => '72',
                            'zip' => '627705',
                            'bold' => 0,
                        ),
                    11 =>
                        array(
                            'city' => 'Казань',
                            'region' => '16',
                            'zip' => '420000',
                            'bold' => 0,
                        ),
                    12 =>
                        array(
                            'city' => 'Калининград',
                            'region' => '39',
                            'zip' => '236001',
                            'bold' => 0,
                        ),
                    13 =>
                        array(
                            'city' => 'Кемерово',
                            'region' => '42',
                            'zip' => '650000',
                            'bold' => 0,
                        ),
                    14 =>
                        array(
                            'city' => 'Кострома',
                            'region' => '44',
                            'zip' => '156000',
                            'bold' => 0,
                        ),
                    15 =>
                        array(
                            'city' => 'Красноярск',
                            'region' => '24',
                            'zip' => '660000',
                            'bold' => 1,
                        ),
                    16 =>
                        array(
                            'city' => 'Лангепас',
                            'region' => '86',
                            'zip' => '628671',
                            'bold' => 0,
                        ),
                    17 =>
                        array(
                            'city' => 'Магнитогорск',
                            'region' => '74',
                            'zip' => '455000',
                            'bold' => 0,
                        ),
                    18 =>
                        array(
                            'city' => 'Москва',
                            'region' => '77',
                            'zip' => '101000',
                            'bold' => 1,
                        ),
                    19 =>
                        array(
                            'city' => 'Мирный',
                            'region' => '29',
                            'zip' => '164170',
                            'bold' => 0,
                        ),
                    20 =>
                        array(
                            'city' => 'Муром',
                            'region' => '33',
                            'zip' => '602205',
                            'bold' => 0,
                        ),
                    21 =>
                        array(
                            'city' => 'Набережные Челны',
                            'region' => '16',
                            'zip' => '423800',
                            'bold' => 0,
                        ),
                    22 =>
                        array(
                            'city' => 'Нефтеюганск',
                            'region' => '86',
                            'zip' => '628303',
                            'bold' => 0,
                        ),
                    23 =>
                        array(
                            'city' => 'Нижневартовск',
                            'region' => '86',
                            'zip' => '628600',
                            'bold' => 0,
                        ),
                    24 =>
                        array(
                            'city' => 'Новосибирск',
                            'region' => '54',
                            'zip' => '630000',
                            'bold' => 1,
                        ),
                    25 =>
                        array(
                            'city' => 'Новый Уренгой',
                            'region' => '89',
                            'zip' => '629300',
                            'bold' => 0,
                        ),
                    26 =>
                        array(
                            'city' => 'Нижний Новгород',
                            'region' => '52',
                            'zip' => '603000',
                            'bold' => 1,
                        ),
                    27 =>
                        array(
                            'city' => 'Озерск',
                            'region' => '74',
                            'zip' => '456780',
                            'bold' => 0,
                        ),
                    28 =>
                        array(
                            'city' => 'Омск',
                            'region' => '55',
                            'zip' => '644000',
                            'bold' => 1,
                        ),
                    29 =>
                        array(
                            'city' => 'Пенза',
                            'region' => '58',
                            'zip' => '440000',
                            'bold' => 0,
                        ),
                    30 =>
                        array(
                            'city' => 'Пермь',
                            'region' => '59',
                            'zip' => '614000',
                            'bold' => 1,
                        ),
                    31 =>
                        array(
                            'city' => 'Ростов-на-Дону',
                            'region' => '61',
                            'zip' => '344000',
                            'bold' => 1,
                        ),
                    32 =>
                        array(
                            'city' => 'Самара',
                            'region' => '63',
                            'zip' => '443000',
                            'bold' => 1,
                        ),
                    33 =>
                        array(
                            'city' => 'Санкт-Петербург',
                            'region' => '78',
                            'zip' => '190000',
                            'bold' => 1,
                        ),
                    34 =>
                        array(
                            'city' => 'Тюмень',
                            'region' => '72',
                            'zip' => '625000',
                            'bold' => 1,
                        ),
                    35 =>
                        array(
                            'city' => 'Уфа',
                            'region' => '02',
                            'zip' => '450000',
                            'bold' => 1,
                        ),
                    36 =>
                        array(
                            'city' => 'Челябинск',
                            'region' => '74',
                            'zip' => '454000',
                            'bold' => 1,
                        ),
                    37 =>
                        array(
                            'city' => 'Южно-Сахалинск',
                            'region' => '65',
                            'zip' => '693000',
                            'bold' => 0,
                        ),
                    38 =>
                        array(
                            'city' => 'Ялуторовск',
                            'region' => '72',
                            'zip' => '627010',
                            'bold' => 0,
                        ),
                )
            )
        )
    )
);