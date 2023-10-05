<?php

class shopBundlingPluginAddBundleGroupController extends waJsonController
{
	public function execute()
	{
		$by = waRequest::request('by');
		$option = waRequest::request('option');
		$feature_value = waRequest::request('feature_value', 0, 'int');
		$title = waRequest::request('title');
		$multiple = waRequest::request('multiple', 0, 'int');
		$subcategories = waRequest::request('subcategories', 0, 'int');
		
		if(!in_array($by, array('type', 'category', 'feature')))
			return $this->setError(_wp('Error! Groups can be determined only by category, product type or product feature'));
		
		$model = new shopBundlingModel();
		switch($by) {
			case 'type':
				$type_model = new shopTypeModel();
				$type = $type_model->getById($option);
				if(!$type)
					return $this->setError(_wp('Can\'t find selected type'));
				$name = $type['name'];
				break;
			case 'category':
				$category_model = new shopCategoryModel();
				$category = $category_model->getById($option);
				if(!$category)
					return $this->setError(_wp('Can\'t find selected category'));
				$name = $category['name'];
				
				break;
			case 'feature':
				if(strpos($option, '-') > 0) {
					$option_params = explode('-', $option);
					$option = $option_params[0];
					$feature_value = $option_params[1];
				}
				
				$feature_model = new shopFeatureModel();
				$feature = $feature_model->getById($option);
				if(!$feature)
					return $this->setError(_wp('Can\'t find selected feature'));
				$feature_values_model = new shopFeatureValuesVarcharModel();
				$_feature_value = $feature_values_model->getById($feature_value);
				if(!$_feature_value)
					return $this->setError(_wp('Can\'t find selected feature'));
				$name = '<span class="gray">' . htmlspecialchars($feature['name'], ENT_COMPAT, 'utf-8') . '</span> ' . htmlspecialchars($_feature_value['value'], ENT_COMPAT, 'utf-8');
				break;
		}
		
		$data = array(
			'title' => $title,
			$by . '_id' => intval($option),
			'multiple' => $multiple
		);
		
		if($by == 'feature')
			$data['feature_value'] = $feature_value;
		
		if($by == 'category' && $subcategories) {
			$subcategories_list = $category_model->getTree($option);
			unset($subcategories_list[$option]);
			
			foreach(array_keys($subcategories_list) as $subcategory_id) {
				$_data = $data;
				$_data['category_id'] = intval($subcategory_id);
				
				$model->insert($_data);
			}
		}
		
		$id = $model->insert($data);
		
		$this->response = array(
			'id' => $id,
			'name' => $by == 'feature' ? $name : htmlspecialchars($name, ENT_COMPAT, 'utf-8'),
			'title' => htmlspecialchars($title, ENT_COMPAT, 'utf-8')
		);
	}
}