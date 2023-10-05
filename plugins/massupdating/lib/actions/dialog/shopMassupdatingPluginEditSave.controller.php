<?php

/*
 * mail@shevsky.com
 */

class shopMassupdatingPluginEditSaveController extends waJsonController
{
	public function execute()
	{
		$product_ids = waRequest::post('product_id', array(), 'array_int');
		$hash = waRequest::post('hash', '');
		if((count($product_ids) == 0 && !$hash) || waRequest::post('_csrf') != waRequest::cookie('_csrf'))
			$this->setError(_wp('Ошибка при отправке данных'));
		else {
			$request = waRequest::request();
			$update_empty = true;
			
			if(isset($request['dont-update-empty']))
				$update_empty = false;
			
			$plugin = wa('shop')->getPlugin('massupdating');
			$this->debug = $plugin->getSettings('debug');
			
			$inputs = $plugin->inputs;
			$input['skus_weight'] = true;
			$data = array();
			foreach($inputs as $key => $input) {
				if(isset($request[$key]))
					if(($request[$key] == '' && $update_empty) || $request[$key] != '')
						$data[$key] = $request[$key];
			}
						
			if($hash)
				$product_ids = $plugin->getProductIdsByHash($hash);
			
			$this->update = new shopMassupdatingUpdate(array(
				'plugin' => $plugin,
				'debug' => $this->debug,
				'update_empty' => $update_empty
			));
			
			if(count($product_ids) > 0) {
				$update = $this->update($product_ids, $data, $request, $update_empty, waRequest::file('files'));
				if($update === true) {
					if(!empty($request['generator']['mask']))
						$this->response = _wp('Артикулы успешно сгенерированы!');
					else
						$this->response = true;
				}
				else
					$this->setError(_wp($update ? $update : 'Неизвестная ошибка'));
			}
		}
	}
	
	public function writeLog($str)
	{
		if($this->debug)
			waLog::log($str, 'massupdating.log');
	}

	public function update($products, $data, $request = false, $update_empty, $files = false)
	{
		$product_model = new shopProductModel();
		$plugin = wa('shop')->getPlugin('massupdating');
		$allowed_product_ids = $plugin->filterAllowedProducts($products);
		$default_currency = shopMassupdatingPlugin::getDefaultCurrency('code');

		if(count($allowed_product_ids) != count($products)) {
			$this->setError(_wp('Доступ к редактированию выбранных товаров запрещен'));
			return false;
		};

		// $allowed_products = $product_model->select('id, name, price, min_price, max_price, currency, compare_price')->where('id IN (i:ids)', array('ids' => $allowed_product_ids))->fetchAll('id');
		
		$to_update = $data;

		if($request && ((!empty($files) && $files->count() > 0) || (!empty($request['photo_action']) && $request['photo_action'] == 'delete'))) {
			try {
				$this->update->photos($allowed_product_ids, $files, ifset($request['photo_action'], 'addtoend'));
			} catch(Exception $e) {
				$this->writeLog(sprintf(_wp('Не могу обновить фотографии') . ': %s', $e->getMessage()));
				return $e->getMessage();
			}
		}
		
		if(!empty($request['name']))
			$product_features_model = new shopProductFeaturesModel();
		if(isset($request['params']))
			$product_params_model = new shopProductParamsModel();
		
		if($request && !empty($request['subpages'])) {
			try {
				$this->update->subpages($request['subpages']);
			} catch(Exception $e) {
				$this->writeLog(sprintf(_wp('Не могу удалить подстраницы') . ': %s', $e->getMessage()));
				return $e->getMessage();
			}
		}
		
		foreach($allowed_product_ids as $id) {
			$product = new shopProduct($id);
			
			$app_info = wa()->getAppInfo('shop');

			if(!empty($to_update['video_url']) && $app_info['version'] >= 7) {
				try {
					$to_update = $this->update->video($to_update);
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить видео') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}
			
			if(!empty($to_update['badge'])) {
				try {
					$to_update = $this->update->badge($to_update, ifset($request['custom_badge']));
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить наклейки') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}

			if(($request && isset($data['prices'])) || (!empty($request['prices_action']) && $request['prices_action'] == 'compare')) {
				try {
					$this->update->prices(array(
						'id' => $id,
						'product' => $product,

						'update_empty' => $update_empty,
						'to' => ifset($data['prices'], 0),
						'action' => ifempty($request['prices_action'], false),
						'currency' => ifset($request['currency'], '%'),
						'price_type' => ifset($request['price_type'], 'price'),
						'from' => ifset($request['from'], 'current'),
						'compare' => ifset($request['compare'], 1),
						'precision' => ifset($request['precision'], 0),
						'mode' => ifset($request['mode'], 'up'),
						'minus' => ifset($request['minus'], false)
					));
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить цены') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}

			if($request && !empty($data['skus'])) {
				try {
					$this->update->skus(array(
						'id' => $id,
						'product' => $product,

						'skus' => ifempty($data['skus'], 0),
						'skus_by_stocks' => ifempty($request['skus_by_stocks'], false),
						'remove_empty_skus' => ifempty($request['remove_empty_skus'], false)
					));
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить состояние складов') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}

			if($request && !empty($request['skus_weight']['to'])) {
				try {
					$this->update->skusWeight($id, $product, $request['skus_weight']);
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить вес артикулов') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}
			
			if($request && isset($request['tags'])) {
				try {
					$this->update->tags($id, ifset($request['tags_action'], 'add'), ifset($data['tags'], ''), ifset($request['tags_update_case'], false));
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить тэги') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}
			
			if($request && !empty($request['features'])) {
				try {
					$this->update->features(array(
						'id' => $id,
						'features' => $request['features'],
						'feature_action' => ifset($request['feature_action']),
						'new_features' => ifempty($request['new_features']),
						'new_multiple_features' => ifempty($request['new_multiple_features']),
					));
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить характеристики') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}
			
			if($request && !empty($request['set_currencies'])) {
				$update_prices_by_rate = !empty($request['update_prices_by_rate']);
				
				try {
					$this->update->currencies($id, $product, $request['set_currencies'], $update_prices_by_rate);
				} catch(Exception $e) {
					$this->writeLog(sprintf(_wp('Не могу обновить валюту товаров') . ': %s', $e->getMessage()));
					return $e->getMessage();
				}
			}
		
			if(isset($to_update['prices']))
				unset($to_update['prices']);
			
			if(isset($to_update['tags']))
				unset($to_update['tags']);
			
			$to_update_individual = array();
			$view = wa()->getView();
			$view->clearAllAssign();
			$view->assign('name', $product['name']);
			$view->assign('price', wa_currency($product['price'], $product['currency']));
			$shop_name = wa('shop')->getConfig()->getGeneralSettings('name');
			$view->assign('shop', ifset($shop_name));
			$view->assign('sku',  $product['skus'][$product['sku_id']]);
			$view->assign('skus',  $product['skus']);
			$view->assign('description',  $product['description']);
			$view->assign('summary',  $product['summary']);
			
			if($request && isset($request['params'])) {
				if(!empty($request['params']) || $update_empty) {
					$params = $request['params'];
					try {
						$params = @$view->fetch('string:' . $params);
						$product_params_model->setData($product, $params);
					} catch(SmartyCompilerException $e) {
						$this->writeLog(sprintf(_wp('Не могу обновить дополнительные параметры товаров') . ': %s', $e->getMessage()));
						$this->setError(_wp('Допущена синтаксическая ошибка в поле для ввода дополнительных товаров!'));
					}
				}
			}
			
			if(!empty($request['generator']['mask']))
				$to_update['generator'] = $request['generator'];
			
			foreach($to_update as $key => $value) {
				if($key == 'summary' || $key == 'description' || $key == 'meta_keywords' || $key == 'meta_title' || $key == 'meta_description') {
					try {
						// $to_update_individual[$key] = @$view->fetch('string:' . (substr($key, 0, 4) == 'meta' ? preg_replace('/\{\$(name|price|summary)\}/', '{literal}{$$1}{/literal}', $value) : $value));
						$to_update_individual[$key] = @$view->fetch('string:' . $value);
					} catch(SmartyCompilerException $e) {
						unset($to_update[$key]);
						$this->writeLog(sprintf(_wp('Не могу обновить дополнительные параметры товаров') . ': %s', $e->getMessage()));
						$this->setError(_wp('Допущена синтаксическая ошибка в поле "' . $key . '"!'));
					}
				}
				 
				if($key == 'name') {
					$name_view = wa()->getView();
					$name_view->clearAllAssign();
					$name_view->assign('product',  $product);
					$name_view->assign('sku',  ifset($product['skus'][$product['sku_id']]));
					$name_view->assign('skus',  ifset($product['skus']));
					$name_view->assign('price',  wa_currency($product['price'], $product['currency']));
					$name_view->assign('min_price',  wa_currency($product['min_price'] ? $product['min_price'] : $product['price'], $product['currency']));
					$name_view->assign('max_price',  wa_currency($product['max_price'] ? $product['max_price'] : $product['price'], $product['currency']));
					$got_features = $product_features_model->getValues($id);
					$name_features = array();
					foreach($got_features as $_key => $_value) {
						$name_features[$_key] = array(
							'value' => null,
							'unit' => null,
							'code' => null,
							'begin' => null,
							'end' => null
						 );

						if($_value instanceof ArrayAccess) {
							$name_features[$_key]['value'] = $_value->offsetGet('value');
							$name_features[$_key]['value_base_unit'] = $_value->offsetGet('value');
							$name_features[$_key]['unit'] = $_value->offsetGet('unit');
							$name_features[$_key]['code'] = $_value->offsetGet('code');
							$name_features[$_key]['begin'] = $_value->offsetGet('begin');
							$name_features[$_key]['end'] = $_value->offsetGet('end');
						} elseif(gettype($_value) == 'array') {
							$i = 0;
							foreach($_value as $__value) {
								if($__value instanceof ArrayAccess) {
									$name_features[$_key]['value'][$i] = $__value->offsetGet('value');
									$name_features[$_key]['code'][$i] = $__value->offsetGet('code');
								} else
									$name_features[$_key]['value'][$i] = $__value;
								
								$i++;
							}
						 } else
							$name_features[$_key]['value'] = $_value;
					}
					 
					$name_view->assign('feature', $name_features);

					try {
						$to_update_individual['name'] = @$name_view->fetch('string:' . $value);
					} catch(SmartyCompilerException $e) {
						unset($to_update['name']);
						$this->writeLog(sprintf(_wp('Не могу обновить наименования товаров') . ': %s', $e->getMessage()));
						$this->setError(_wp('Допущена синтаксическая ошибка в поле для ввода наименования!'));
					}
				}
				
				if($key == 'url' || $key == 'generator') {
					$url_view = wa()->getView();
					$url_view->clearAllAssign();
					$url_view->assign('name', isset($to_update_individual['name']) ? $to_update_individual['name'] : $product['name']);
					$url_view->assign('sku',  ifset($product['skus'][$product['sku_id']]));
					$url_view->assign('skus',  ifset($product['skus']));
					$url_view->assign('id', $id);
					
					$categories = array();
					$category_model = new shopCategoryModel();
					$categories[0] = $category_model->getById($product['category_id']);
					$parent_id = $categories[0]['parent_id'];
					$i = 1;
					while($parent_id != 0) {
						$categories[$i] = $category_model->getById($parent_id);
						if(!empty($categories[$i])) {
							$parent_id = $categories[$i]['parent_id'];
							$i++;
						} else
							break;
					}
					
					$url_view->assign('category', array_column($categories, 'name'));
					$url_view->assign('category_url', array_column($categories, 'url'));

					$url_view->assign('r_category', array_column(array_reverse($categories), 'name'));
					$url_view->assign('r_category_url', array_column(array_reverse($categories), 'url'));

					if($key == 'generator') {
						$generator_data = array();
						foreach($product['skus'] as $sku) {
							$sku_id = $sku['id'];

							if(empty($sku['sku']) || (!empty($sku['sku']) && empty($value['only_for_empty']))) {
								$url_view->assign('sku_id', $sku_id);
								try {
									$code = @$url_view->fetch('string:' . $value['mask']);
									if(!empty($value['transliterate']))
										$code = shopHelper::transliterate($code, false);
									if(!empty($value['uppercase']))
										$code = mb_strtoupper($code);
									
									$generator_data[$sku_id] = $sku;
									$generator_data[$sku_id]['sku'] = $code;
								} catch(SmartyCompilerException $e) {
									unset($to_update[$key]);
									$this->writeLog(sprintf(_wp('Не могу сгенерировать артикулы для товаров') . ': %s', $e->getMessage()));
									$this->setError(_wp('Синтаксическая ошибка в поле для ввода маски артикула!'));
								}
							}
						}
						
						if(!empty($generator_data))
							$product->save(array(
								'skus' => $generator_data
							));
					} else {
						try {
							$to_update_individual[$key] = @$url_view->fetch('string:' . $value);
							$to_update_individual[$key] = shopHelper::transliterate($to_update_individual[$key], false);
						} catch(SmartyCompilerException $e) {
							unset($to_update[$key]);
							$this->writeLog(sprintf(_wp('Не могу обновить URL товаров') . ': %s', $e->getMessage()));
							$this->setError(_wp('Допущена синтаксическая ошибка в поле для ввода URL!'));
						}
					}
				}
			}
			
			if($request && !empty($request['tax_id'])) {
				if($request['tax_id'] == -1)
					unset($to_update['tax_id']);
			}
			
			$to_update['edit_datetime'] = date('Y-m-d H:i:s');
			
			$product_model->updateById($id, $to_update);
			if(count($to_update_individual))
				$product_model->updateById($id, $to_update_individual);
		}
		
		return true;
	}
}