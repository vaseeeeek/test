<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsData
{
    private $category_data;
    private $set_data;
    private $type_data;
    private $product_data;

    public function getCategoryData()
    {
        if (!$this->category_data) {
            $this->category_data = new shopProductsetsCategoryData();
        }
        return $this->category_data;
    }

    public function getSetData()
    {
        if (!$this->set_data) {
            $this->set_data = new shopProductsetsSetData();
        }
        return $this->set_data;
    }

    public function getTypeData()
    {
        if (!$this->type_data) {
            $this->type_data = new shopProductsetsTypeData();
        }
        return $this->type_data;
    }

    public function getProductData($sku_ids = array())
    {
        if (!$this->product_data) {
            $this->product_data = new shopProductsetsProductData($sku_ids);
        }
        return $this->product_data;
    }
}