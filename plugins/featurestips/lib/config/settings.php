<?php

return array(
    'true_mobile' => array(
        'value' => '1',
        'title' => _w('Mobile version'),
        'description' => _w('Show tips in the mobile version'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'action' => array(
        'value' => 'hover',
        'title' => _w('How to show tips'),
        'description' => _w('Select at what the action will be shown tips'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array (
                'value'       => 'hover',
                'title'       => _w('Mouse hover on the icon'),
                'description' => '',
            ),
            array (
                'value'       => 'click',
                'title'       => _w('Mouse click on the icon'),
                'description' => '',
            ),
        ),
    ),
    'text_color' => array(
        'title'        => _w('Text color'),
        'description'  => _w('Color value in hex format, e.g. #777777'),
        'value'        => '#777777',
        'control_type' => waHtmlControl::INPUT
    ),
    'bg_color' => array(
        'title'        => _w('Background color'),
        'description'  => _w('Background color value in hex format, e.g. #ffffff'),
        'value'        => '#FFFFFF',
        'control_type' => waHtmlControl::INPUT
    ),
    'qmark' => array(
        'value' => 'dark',
        'title' => _w('Style of icons'),
        'description' => _w('Style of icons shown on the frontend'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array (
                'value'       => 'dark',
                'title'       => _w('dark'),
                'description' => '',
            ),
            array (
                'value'       => 'light',
                'title'       => _w('light'),
                'description' => '',
            ),
        ),
    ),
    'qmark-size' => array(
        'value' => '14',
        'title' => _w('Size of icons (px)'),
        'description' => _w('Size of icons shown on the frontend'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array (
                'value'       => '10',
                'title'       => '10 px',
                'description' => '',
            ),
            array (
                'value'       => '11',
                'title'       => '11 px',
                'description' => '',
            ),
            array (
                'value'       => '12',
                'title'       => '12 px',
                'description' => '',
            ),
            array (
                'value'       => '13',
                'title'       => '13 px',
                'description' => '',
            ),
            array (
                'value'       => '14',
                'title'       => '14 px',
                'description' => '',
            ),
            array (
                'value'       => '15',
                'title'       => '15 px',
                'description' => '',
            ),
            array (
                'value'       => '16',
                'title'       => '16 px',
                'description' => '',
            ),
            array (
                'value'       => '17',
                'title'       => '17 px',
                'description' => '',
            ),
            array (
                'value'       => '18',
                'title'       => '18 px',
                'description' => '',
            ),
            array (
                'value'       => '19',
                'title'       => '19 px',
                'description' => '',
            ),
            array (
                'value'       => '20',
                'title'       => '20 px',
                'description' => '',
            ),
        ),
    ),
    'qmark-contrast' => array(
        'value' => '30',
        'title' => _w('Contrast of icons (%)'),
        'description' => _w('Contrast of icons shown on the frontend'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array (
                'value'       => '10',
                'title'       => '10%',
                'description' => '',
            ),
            array (
                'value'       => '20',
                'title'       => '20%',
                'description' => '',
            ),
            array (
                'value'       => '30',
                'title'       => '30%',
                'description' => '',
            ),
            array (
                'value'       => '40',
                'title'       => '40%',
                'description' => '',
            ),
            array (
                'value'       => '50',
                'title'       => '50%',
                'description' => '',
            ),
            array (
                'value'       => '60',
                'title'       => '60%',
                'description' => '',
            ),
            array (
                'value'       => '70',
                'title'       => '70%',
                'description' => '',
            ),
            array (
                'value'       => '80',
                'title'       => '80%',
                'description' => '',
            ),
            array (
                'value'       => '90',
                'title'       => '90%',
                'description' => '',
            ),
            array (
                'value'       => '100',
                'title'       => '100%',
                'description' => '',
            ),
        ),
    ),
    'hide_instruction' => array(
        'value' => '0',
        'title' => _w('Hide instruction'),
        'description' => _w('Hide instruction in the backend'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);