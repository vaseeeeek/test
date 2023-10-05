<?php

class shopBundlingPluginAutocompleteController extends shopBackendAutocompleteController
{
	/* public function execute()
	{
		$data = array();
		$q = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);
		if($q)
			$data = $this->productsAutocomplete($q);
	} */
	
	public static function getStockStatusIcon($count)
	{
		if($count == '')
			return '<i class="icon10 status-green" title="' . _w('In stock') . '"></i>';
		elseif($count == 0)
			return '<i class="icon10 status-red" title="' . _w('Out of stock') . '"></i>';
		elseif($count >= 2 && $count < 6)
			return '<i class="icon10 status-yellow" title="' . _w('Almost out of stock') . '"></i>';
		elseif($count >= 6 || is_null($count))
			return '<i class="icon10 status-green" title="' . _w('In stock') . '"></i>';
	}
	
	public function productsAutocomplete($q, $limit = null)
    {
		$currency = wa('shop')->getConfig()->getCurrency();
		
		$new_data = $data = parent::productsAutocomplete($q, $limit);
		
		$product_model = new shopProductModel();
		$product_skus_model = new shopProductSkusModel();
		foreach($data as $key => $p) {
			$p['stock_status_icon'] = self::getStockStatusIcon($p['count']);
			$value = $p['value'];
			$product_id = $p['id'];
			$p['product_id'] = $product_id;
			$p['category_id'] = (int) $product_model->query("SELECT `category_id` FROM `{$product_model->getTableName()}` WHERE `id` = {$p['id']}")->fetchField('category_id');
			$p['name'] = $value;
			$sku_count = (int) $product_model->query("SELECT `sku_count` FROM `{$product_model->getTableName()}` WHERE `id` = {$p['id']}")->fetchField('sku_count');
			
			if($sku_count > 1 && false) {
				$p_general = $p;
				$p_general['value'] .= ' - ' . _wp('Choose') . ' (' . _wp('user can choose sku and product params') . ')';
				$p_general['label'] = htmlspecialchars($value, ENT_COMPAT, 'utf-8')  . ' - <b style="color: green;">' . _wp('Choose') . ' (' . _wp('user can choose sku and product params') . ')' . '</b>';
				$p_general['sku_id'] = -1;
				$p_general['id'] = $product_id . '--1';
				array_push($new_data, $p_general);
			}
			
			$new_data[$key] = $p;
			
			if($sku_count > 1) {
				$new_data[$key]['value'] .= ' - ' . $p['sku_name'];
				$new_data[$key]['label'] = htmlspecialchars($value, ENT_COMPAT, 'utf-8')  . ' - <b>' . htmlspecialchars($p['sku_name'], ENT_COMPAT, 'utf-8') . '</b>';
				$new_data[$key]['sku_count'] = $sku_count;
				
				$skus = $product_skus_model->getDataByProductId($p['id']);
				unset($skus[$p['sku_id']]);
				foreach($skus as $sku) {
					$p['product_id'] = $product_id;
					$p['id'] = $product_id . '-' . $sku['id'];
					$p['price'] = shop_currency($sku['primary_price'], null, $currency, false);
					$p['price_str'] = wa_currency($p['price'], $currency);
					$p['price_html'] = wa_currency_html($p['price'], $currency);
					$p['sku_id'] = $sku['id'];
					$p['count'] = $sku['count'];
					$p['stock_status_icon'] = self::getStockStatusIcon($p['count']);
					$p['sku_name'] = $sku['name'];
					$p['sku_count'] = $sku_count;
					$p['value'] = $value . ' - ' . $p['sku_name'];
					$p['label'] = htmlspecialchars($value, ENT_COMPAT, 'utf-8')  . ' - <b>' . htmlspecialchars($p['sku_name'], ENT_COMPAT, 'utf-8') . '</b>';
					
					array_push($new_data, $p);
				}
			}
		}

		return $new_data;
	}
}