<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 24.01.2022
 * Time: 15:45
 */

class shopBrandImportexportHelper
{
    public static function getProductSortOptions()
    {
        return array(
            'MANUAL',
            'NAME',
            'PRICE_ASC',
            'PRICE_DESC',
            'RATING_ASC',
            'RATING_DESC',
            'TOTAL_SALES_ASC',
            'TOTAL_SALES_DESC',
            'COUNT',
            'CREATE_DATETIME',
            'STOCK_WORTH',
        );
    }

}