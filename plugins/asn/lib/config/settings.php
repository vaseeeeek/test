<?php
/**
 * Automatic SKU ID Generator plugin for Shop-Script 6
 *
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.1.0
 * @copyright Serge Rodovnichenko, 2015
 * @license http://www.webasyst.com/terms/#eula Webasyst
 * @package asn.config
 */
return array(
    'use'      => array(
        'value'        => true,
        'title'        => 'Включить плагин',
        'description'  => 'Генерировать артикул автоматически, если артикул не указан',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'template' => array(
        'value'        => '',
        'title'        => 'Шаблон',
        'description'  =>
            'Если нужно генерировать с каким-то префиксом или суффиксом. Доступные переменные: ' .
            '<b>{$sku_id}</b> - ID артикула в БД',
        'control_type' => waHtmlControl::INPUT
    )
);
