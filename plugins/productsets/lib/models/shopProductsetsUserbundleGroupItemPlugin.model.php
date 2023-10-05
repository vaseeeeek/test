<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsUserbundleGroupItemPluginModel extends shopProductsetsModel
{

    protected $table = 'shop_productsets_userbundle_group_item';

    public function __construct()
    {
        $this->bundle_key = 'group_id';
        parent::__construct();
    }
}
