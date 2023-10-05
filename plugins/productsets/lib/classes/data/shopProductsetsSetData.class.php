<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsSetData extends shopProductsetsHtmlBuilder
{
    public function __construct($selected = '')
    {
        parent::__construct($selected);

        $this->data = (new shopSetModel())->getAll('id');
    }

}