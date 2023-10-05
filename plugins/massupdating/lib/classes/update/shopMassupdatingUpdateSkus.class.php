<?php

class shopMassupdatingUpdateSkus
{
	public function __construct()
	{
		$this->product_skus_model = new shopProductSkusModel();
		/* $this->stocks_model = new shopProductStocksModel();
		$this->stocks_log_model = new shopProductStocksLogModel(); */
	}
	
	public function calculateCount($current_count, $action, $type, $to)
	{
		switch($type) {
			case 1:
				switch($action) {
					case 'minus':
						$count = $current_count - $to;
						break;
					case 'plus':
						$count = $current_count + $to;
						break;
				}
						
				break;
			case 2:
				$count = $to == -1 ? '' : $to;
				break;
		}
		
		return ifset($count);
	}

	public function update($params)
	{
		extract($params);
		
		$product_skus = $this->product_skus_model->getDataByProductId($id);
		
		if(!$skus_by_stocks) {
			$action = $skus[-1]['action'];
			$type = intval($skus[-1]['type']);
			$to = $skus[-1]['to'] != '' ? intval($skus[-1]['to']) : null;
						
			if(!is_null($to))
				foreach($product_skus as $sku) {
					$sku_id = $sku['id'];
					
					$this->product_skus_model->deleteFromStocks($id, $sku_id);
					$this->product_skus_model->deleteStocksLog($id, $sku_id);
					
					$count = $this->calculateCount($sku['count'], $action, $type, $to);
					
					$data = array(
						'stock' => array(
							0 => $count,
						),
						'price' => $sku['price']
					);
					
					$this->product_skus_model->update($sku_id, $data);
				}
		} else {
			unset($skus[-1]);
			
			$stocks = array();
			
			foreach($product_skus as $sku) {
				$sku_id = $sku['id'];
				
				reset($skus);
				foreach($skus as $stock_id => $stock_params) {
					if(!empty($stock_params['on']) && $stock_params['to'] != '') {
						$action = $stock_params['action'];
						$type = intval($stock_params['type']);
						$to = intval($stock_params['to']);

						$current_count = ifset($sku['stock'][$stock_id], null);

						if(is_null($current_count)) {
							$current_count = ifset($sku['count'], '');
							if(isset($sku['count']))
								unset($sku['count']);
						}
						
						$count = $this->calculateCount($current_count, $action, $type, $to);
							
						$stocks[$stock_id] = $count;
					}
				}
				
				if(!empty($stocks)) {
					$data = array(
						'stock' => $stocks,
						'price' => $sku['price']
					);

					$this->product_skus_model->update($sku_id, $data);
				}
			}
		}
		
		if($remove_empty_skus)
			foreach($product_skus as $sku) {
				$remove = empty($sku['name']) && empty($sku['sku']) && empty($sku['price']) && empty($sku['count']);
				if($remove)
					$this->product_skus_model->deleteById($sku['id']);
			}
	}
}