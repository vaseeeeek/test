<?php

/*
 * mail@shevsky.com
 */
 
class shopMassupdatingPluginCrossAction extends shopMassupdatingDialog
{
	public $title = 'Перекрестные и схожие товары';
	
	public function execute()
	{
		$relates = array();
		$links = array();
		$values = array();
		$cross = array('cross_selling', 'upselling');
		$related_model = new shopProductRelatedModel();
		
		$products = array();
		foreach($this->product_ids as $id) {
			$product = new shopProduct($id);
			$products[$id] = $product;
			
			foreach($cross as $type) {
				if(isset($product[$type])) {
					$values[$type][] = $product[$type];
					
					if($product[$type] == 2) {
						$related = $related_model->getByField(array(
							'product_id' => $id,
							'type' => $type
						), true);
						
						$links[$type][$id][] = (string) $id;
						$relates[$type][$id] = array();
						foreach($related as $link) {
							$links[$type][$id][] = $link['related_product_id'];
							$relates[$type][$id][] = $link['related_product_id'];
						}

						sort($relates[$type][$id]);
						sort($links[$type][$id]);
						
						$relates[$type][$id] = json_encode($relates[$type][$id]);
						$links[$type][$id] = json_encode($links[$type][$id]);
					}
				} else
					$values[$type][] = null;
			}
		}

		foreach($values as $key => $value) {
			if(count($this->product_ids) == count($value)) {
				$unique = array_unique($value);
				if(count($unique) == 1) {
					$values[$key]['value'] = $unique[0];
					if($unique[0] == 2 && count($this->product_ids) > 1) {
						$unique_links = array_unique($links[$key]);
						$values[$key]['linked'] = count($unique_links) == 1;

						$unique_relates = array_unique($relates[$key]);
						$values[$key]['identical'] = count($unique_relates) == 1;

						if(count($unique_relates) == 1) {
							$values[$key]['product_ids'] = json_decode(array_shift($unique_relates), 1);
						}
					} elseif($unique[0] == 2 && count($this->product_ids) == 1) {
						$values[$key]['identical'] = true;
						$values[$key]['product_ids'] = json_decode(array_shift($relates[$key]), 1);
					}
				} else
					$values[$key]['different'] = true;
			} else
				$values[$key]['different'] = true;
		};
		
		$this->view->assign('values', $values);
	}
}