<?php

/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectRegionModel extends waModel
{
    public $table = "shop_cityselect__region";

    public $_id;

    public $_data = array();

    public $is_loaded = false;

    public function __construct($id = null)
    {
        parent::__construct();

        if (!empty($id)) {
            $this->load($id);
        }
    }

    public function deleteById($value)
    {
        $variables_model = new shopCityselectVariablesModel();
        $variables_model->deleteByField('region_id', $value);
        return parent::deleteById($value);
    }


    public function getAll($key = null, $normalize = false)
    {

        $all = $this->query('select * from ' . $this->table . ' order by region, city, id');
        $result = array();

        foreach ($all as $item) {
            $result[$item['id']] = new shopCityselectRegionModel($item['id']);
        }

        return $result;
    }


    public function prepareData()
    {
        if (!empty($this->_data)) {

            if ($this->_data['region'] < 10) {
                $this->_data['region'] = '0' . $this->_data['region'];
            }

            $model = new waRegionModel();
            $data = $model->where("country_iso3 = 'rus' and code = '" . $model->escape($this->_data['region']) . "'")->query()->fetchAssoc();

            if (!empty($data)) {
                $this->_data['region_name'] = $data['name'];
            } else {
                $this->_data['region_name'] = '';
            }
        }
    }

    public function load($id)
    {
        $this->_id = $id;
        $this->is_loaded = true;
        $this->_data = $this->getById($id);

        if (empty($this->_data)) {
            $this->_data = array();
        } else {
            $this->prepareData();
        }
    }

    public function __get($name)
    {
        $method_name = 'get' . ucfirst($name);
        if (method_exists($this, $method_name)) {
            return $this->$method_name();
        }

        if (!$this->is_loaded) {
            return null;
        }
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }


    public function getVariables()
    {
        //Загрузка типов
        $model = new shopCityselectVariablesTypeModel();

        $model_variables = new shopCityselectVariablesModel();
        $variables_value = $model_variables->where('region_id=' . (int)$this->_id)->fetchAll('type_id', true);

        $all = $model->getAll('id');
        if (!empty($all)) {
            foreach ($all as $key => $variable) {
                $all[$key]['value'] = isset($variables_value[$variable['id']]) ? $variables_value[$variable['id']]['value'] : '';
            }
        }else{
            //Для единообразия результатов
            $all = array();
        }

        return $all;
    }

    public function checkRegion($data)
    {
        $region = (int)$data['region'];
        $city = trim($data['city']);
        $id = (int)$data['id'];

        $sql = 'SELECT * FROM ' . $this->table .
            ' WHERE ' . $this->getWhereByField('region', $region) . ' AND ' .
            $this->getWhereByField('city', $city) . ' AND id !=' . $id;
        $sql .= ' LIMIT 1';
        $result = $this->query($sql);

        return $result->count();
    }

    public function save($data)
    {
        $id = (int)$data['id'];

        if (empty($id)) {
            $id = $this->insert($data);
        } else {
            $this->updateById($id, $data);
        }

        //Сохраняем значение переменных
        $variables_model = new shopCityselectVariablesModel();
        $variables_model->deleteByField('region_id', $id);

        if (!empty($data['variable'])) {
            foreach ($data['variable'] as $type_id => $value) {
                $variables_model->insert(array('type_id' => $type_id, 'region_id' => $id, 'value' => $value));
            }
        }

        return $id;
    }

    public function findDefault()
    {
        $sql = "select * from $this->table where region='0' AND city='' limit 1";
        return $this->query($sql)->fetchAssoc();
    }


    public function findDefaultRegion($region)
    {
        $region = (int)$region;

        if (empty($region)) {
            return null;
        }

        if ($region < 10) {
            $region = '0' . $region;
        }

        $sql = "select * from $this->table where region='$region' AND city='' limit 1";
        return $this->query($sql)->fetchAssoc();
    }

    public function findRegion($location)
    {
        if ((empty($location['region'])) || (empty($location['city']))) {
            return null;
        }

        $region = $location['region'];

        if ($region < 10) {
            $region = '0' . $region;
        }

        $city = $this->escape($location['city']);

        $sql = "select * from $this->table where region='$region' AND city like '$city' limit 1";
        return $this->query($sql)->fetchAssoc();

    }
}