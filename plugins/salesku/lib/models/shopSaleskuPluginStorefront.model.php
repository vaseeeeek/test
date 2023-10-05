<?php
class shopSaleskuPluginStorefrontModel extends waModel
{
    protected $table = 'shop_salesku_storefront';

    public function getByStorefrontName($name) {
        return $this->getByField('name', $name);
    }
}

