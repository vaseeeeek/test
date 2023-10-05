<?php
class shopFiwexPluginSettingsAction extends waViewAction
{
    //Функция синхронизации данных
    protected function comparisson_Of_arrays($data, $plugin_data, $table_name='')
    {
        $mas_one = array();
        $mas_two = array();
  
        foreach ($data as $key=>$val) {
            $mas_one[] = $val['id'];
        }
  
        foreach ($plugin_data as $key=>$val) {
            $mas_two[] = $val['id'];
        }
  
        if (!empty($table_name)) {
            $model = new waModel;

            //Добавляем данные
            $mas = array_diff($mas_one, $mas_two);

            if ($mas != 0) {
                foreach ($mas as $k) {
	                if ($table_name == 'shop_fiwex_feat_values_explanations') {
	                    $model->query('INSERT INTO '.$table_name.' (id, feature_id) VALUES (?, ?)', $k, $data[$k]['feature_id']);
	                } else {
	                    $model->query('INSERT INTO '.$table_name.' (id) VALUES (?)', $k);
	                }
	            }
            }
   
            //Удаляем данные
            $mas = array_diff($mas_two, $mas_one);

            if ($mas != 0) {
                foreach ($mas as $k) {
	                $model->query('DELETE FROM '.$table_name.' WHERE id=?', $k);
	            }
            }
        }
    }
 
    //Функция получения значений характеристики
    function getFeature()
    {
        $feature_model = new shopFeatureModel();
        $data = $feature_model->query('SELECT sfv.id, sfv.value, sfv.feature_id FROM shop_feature_values_varchar sfv WHERE sfv.feature_id
                                                           IN (SELECT DISTINCT sff.id FROM shop_feature sff WHERE sff.selectable = 1)')->fetchAll('feature_id');
        return $data;
    }
 
    //Функция получения характеристик
    function getFullFeature()
    {
        $feature_model = new shopFeatureModel();
        $full_data = $feature_model->query('SELECT sf.id, sf.name FROM shop_feature sf WHERE sf.parent_id is null')->fetchAll();
        return $full_data;
    }
 
    function execute()
    {
        $feature_model = new waModel();
        $app_settings = new waAppSettingsModel();
        $str = '';

        //Синхронизируем данные плагина и магазна
        $feature_data = $feature_model->query('SELECT id FROM shop_feature')->fetchAll('id');
        $feature_plugin_data = $feature_model->query('SELECT id FROM shop_fiwex_feature_explanations')->fetchAll('id');
        $feature_val_data = $feature_model->query('SELECT id, feature_id  FROM shop_feature_values_varchar')->fetchAll('id');
        $feature_val_plugin_data = $feature_model->query('SELECT id, feature_id FROM shop_fiwex_feat_values_explanations')->fetchAll('id');
  
        $this->comparisson_Of_arrays($feature_data, $feature_plugin_data, 'shop_fiwex_feature_explanations');
        $this->comparisson_Of_arrays($feature_val_data, $feature_val_plugin_data, 'shop_fiwex_feat_values_explanations');
        //Синхронизация закончена////
  
        //Смотрим включен ли плагин
        $enable_plugin = $app_settings->get(wa()->getApp('shop').'.fiwex', 'enable');

        if ($enable_plugin) {
            $enable_flag = '<a href="javascript: void(0);" class="button green" id="wm-fiwex-enable-flag" data-enable="1">Плагин включен</a>';
            $this->view->assign('enable_flag', $enable_flag);
        } else {
            $enable_flag = '<a href="javascript: void(0);" class="button red" id="wm-fiwex-enable-flag" data-enable="0">Плагин выключен</a>';
            $this->view->assign('enable_flag', $enable_flag);
        }
  
        $data = $this->getFeature();
        $full_data = $this->getFullFeature();

        if (!empty($full_data)) {
            foreach ($full_data as $key=>$val) {
	            $str .= '<tr data-feat_id = "'.$val['id'].'">';

	            if (isset($data[$val['id']]['id'])) {
	                $str.='<td><span class="td_feat_name">'.$val['name'].'</span><a href="javascript:void(0);" style="display: inline-block;" class="feat_name"> (значения)</a><span class="fiwex-popup-hint" style="display: inline;" data-type="feat">?</span> </td><td><a class="edit_feat" href="javascript: void(0);" style="display: inline-block; margin-left: 15px;"><i class="icon16 edit"></i></a></td>';
	            } else {
	                $str.='<td><span class="td_feat_name" style="display: inline-block; color: black;">'.$val["name"].'</span><span class="fiwex-popup-hint" style="display: inline;" data-type="feat">?</span></td><td><a class="edit_feat" href="javascript: void(0);" style="display: inline-block; margin-left: 15px;"><i class="icon16 edit"></i></a></td>';
	            }

                $str.='</tr>';
            }
        }
   
        $this->view->assign('features', $str);
        $tooltip_style = $app_settings->get(wa()->getApp('shop').'.fiwex', 'style');

        if ($tooltip_style) {
            $this->view->assign('tooltip_style', $tooltip_style);
        } else {
            $path = wa()->getAppPath('plugins/fiwex/CSS/','shop');
	        $url = wa()->getAppStaticUrl('shop', true);
	        $query_style = file_get_contents($path.'style.css');
	        $query_style =str_replace('{$path}', $url, $query_style);
	        $this->view->assign('tooltip_style', $query_style);
        }
   
    }
 
}