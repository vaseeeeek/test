<?php

/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectVariablesModel extends waModel
{
    public $table = "shop_cityselect__variables";


    public static $_variables = array();

    public function parseVariable($variable, $remove_template = false)
    {
        if (empty($variable)) {
            return $variable;
        }

        $variable['value'] = !empty($variable['value']) ? $variable['value'] : '';

        $view = wa()->getView();
        foreach ($variable as $key => $value) {
            if ($key == 'template') {
                continue;
            }
            $view->assign($key, $value);
        }
        $html = $view->fetch('string:' . $variable['template']);

        if ($remove_template) {
            unset($variable['template']);
        }

        $variable['html'] = $html;
        return $variable;
    }

    public function parseVariables($variables, $remove_template = false)
    {
        if (!empty($variables)) {

            foreach ($variables as $key => $variable) {
                $variables[$key] = $this->parseVariable($variable, $remove_template);
            }
        }

        return $variables;
    }

    public function findVariables($region, $city)
    {

        $find = array();

        $find['region'] = (int)$region;
        $find['city'] = trim($city);

        $static_key = $find['region'] . '_' . $find['city'];


        if (!isset(self::$_variables[$static_key])) {

            $type_model = new shopCityselectVariablesTypeModel();

            $types = $type_model->getAll('id');

            if (empty($types)) {
                return array();
            }

            $region_model = new shopCityselectRegionModel();

            $find_default = $region_model->findDefault();
            $find_default_region = $region_model->findDefaultRegion($region);
            $find_region = $region_model->findRegion($find);

            $regions = array();

            if (!empty($find_region)) {
                $regions[] = new shopCityselectRegionModel($find_region['id']);
            }

            if (!empty($find_default_region)) {
                $regions[] = new shopCityselectRegionModel($find_default_region['id']);
            }

            if (!empty($find_default)) {
                $regions[] = new shopCityselectRegionModel($find_default['id']);
            }

            foreach ($regions as $region) {
                $variables = $region->getVariables();

                foreach ($types as $key => $type) {
                    if (!empty($type['value'])) {
                        continue;
                    }

                    if (isset($variables[$type['id']]) && (!empty($variables[$type['id']]['value']))) {
                        $types[$key]['value'] = $variables[$type['id']]['value'];
                    }
                }
            }

            self::$_variables[$static_key] = $types;
        }

        return self::$_variables[$static_key];
    }

    public function findVariable($code, $location)
    {
        $result = array();

        $find = array();

        if (empty($location)) {
            $find['region'] = 0;
            $find['city'] = '';
        }

        if (!empty($location['region'])) {
            $find['region'] = (int)$location['region'];
        }

        if (!empty($location['city'])) {
            $find['city'] = trim($location['city']);
        }

        $variables = $this->findVariables($find['region'], $find['city']);

        foreach ($variables as $variable) {
            if ($variable['code'] == $code) {
                $result = $variable;
                break;
            }
        }

        return $result;
    }

}