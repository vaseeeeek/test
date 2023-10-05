<?php

/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectVariablesTypeModel extends waModel
{
    public $table = "shop_cityselect__variables_type";


    public function checkCode($code, $id = 0)
    {
        return $this->query("select * from $this->table where code=s:code and id!=i:id limit 1", array('code' => $code, 'id' => $id))->count();
    }

    public function deleteById($value)
    {
        $variables_model = new shopCityselectVariablesModel();
        $variables_model->deleteByField('type_id', $value);
        return parent::deleteById($value);
    }
}