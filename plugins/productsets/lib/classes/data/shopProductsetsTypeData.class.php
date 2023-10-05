<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsTypeData extends shopProductsetsHtmlBuilder
{
    public function __construct($selected = '')
    {
        parent::__construct($selected);
        
        $this->data = (new shopTypeModel())->getTypes(true);
    }

}