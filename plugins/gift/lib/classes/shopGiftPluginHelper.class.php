<?php

class shopGiftPluginHelper
{
	protected $_gifts = array();
	protected $_cart_items = array();
	protected $_cart_item_quantities = array();
	protected $_ignore_stock_count;
	protected $_cart_gifts = array();
	protected $_quantities = array();

	public function __construct($init_cart = true)
	{
		if ($init_cart) {
			$cart = new shopCart();
			$this->_total = $cart->total(false);
			$this->_code = $cart->getCode();
			$this->_cart_items = $cart->items(false);
			if (!empty($this->_cart_items)) {
				foreach ($this->_cart_items as $item) {
					$this->_cart_item_quantities[$item['product_id']] = $item['quantity'];
				}
			}
			if (!empty($this->_cart_items)) {
				foreach ($this->_cart_items as $k => $item) {
					if ($item['type'] != 'product') {
						unset($this->_cart_items[$k]);
					}
				}
			}
		}
		$app_settings_model = new waAppSettingsModel();
		$this->_ignore_stock_count = $app_settings_model->get('shop', 'ignore_stock_count');

		$storage = wa()->getStorage();
		$this->_cart_gifts = $storage->read('shopGiftPlugin');
	}


	public function getItems()
	{
		return $this->_cart_items;
	}


	public function getGiftList()
	{
		if (empty($this->_gifts)) {
			$plugin = waSystem::getInstance('shop')->getPlugin('gift');
			$collection = new shopProductsCollection('set/' . $plugin->getSettings('list_id'));
			$this->_gifts = $collection->getProducts('*');
		}

		return $this->_gifts;
	}


	public function getProducts($product_id)
	{
		$products = array();
		$model = new shopGiftPluginProductGiftModel;
		$product_ids = $model->getProductIds($gift_id);
		if (!in_array($gift_id, $product_ids)) {
			$product_ids[] = $gift_id;
		}
		if (!empty($product_ids)) {
			$collection = new shopProductsCollection($product_ids);
			$products = $collection->getProducts('*');
		}

		return $products;
	}


	public function getGifts($product_id)
	{
		$model = new shopGiftPluginProductGiftModel;
		$ids = $model->getGiftIds($product_id);

		$list = $this->getGiftList();
		$gifts = array();
		if (!empty($list) && !empty($ids)) {
			$app_settings_model = new waAppSettingsModel();
			foreach ($ids as $id) {
				if (isset($list[$id])) {
					$p = $list[$id];
					if ((!$this->_ignore_stock_count && $p['count']) || $this->_ignore_stock_count || $p['count'] === null) {
						$gifts[$id] = $p;
					}
				}
			}
		}

		return $gifts;
	}


	public function getCartGifts()
	{
		$gifts = array();
		$i = array();// счетчик общего количества каждого подарка в отдельности
		if (!empty($this->_cart_items)) {
			foreach ($this->_cart_items as $item) {
				$product_id = $item['product_id'];
				$product_gifts = $this->getGifts($product_id);
				if (!empty($product_gifts)) {
					$gifts[$product_id] = array(
						'name'     => $item['name'],
						'quantity' => $item['quantity'],
						'gifts'    => array(),
					);
					
					$j = 0;// счетчик количества подарков для текущего элемента корзины, значение не должно превышать кол-во элемента
					foreach ($product_gifts as $gift) {
						$id = $gift['id'];
						if (!isset($i[$id])) {
							$i[$id] = 0;
						}
						if (!$this->_ignore_stock_count && $gift['count'] !== null) {
							$gift['max_quantity'] = ($gift['count'] >= $item['quantity']) ? $item['quantity'] : $gift['count'];
							if (isset($this->_cart_item_quantities[$id])) {
								$d = ($gift['max_quantity'] + $this->_cart_item_quantities[$id]) - $gift['count'];
								if ($d > 0) {
									$gift['max_quantity'] -= $d;
								}
							}
							if ($gift['max_quantity'] < 0) {
								$gift['max_quantity'] = 0;
							}
						}
						else {
							$gift['max_quantity'] = $item['quantity'];
						}
						if (isset($this->_cart_gifts[$product_id][$gift['id']])) {
							$q = $this->_cart_gifts[$product_id][$gift['id']];
							$qty = ($q <= $gift['max_quantity']) ? $q : $gift['max_quantity'];
							$i[$id] += $qty;
							$j += $qty;
					
							// если превышено доступное кол-во подарка или количесвто элемента корзины, то уменьшаем $qty
							if ($j > $item['quantity'] || $i[$id] > $gift['count']) {
								$dj = $j - $item['quantity'];
								$di = $i[$id] - $gift['max_quantity'];
								$qty -= ($dj >= $di) ? $dj : $di;
							}
							$gift['quantity'] = $qty;
						}
						else {
							$gift['quantity'] = 0;
						}
						$gifts[$product_id]['gifts'][] = $gift;
					}
					if (empty($gifts[$product_id]['gifts'])) {
						unset($gifts[$product_id]);
					}
				}
			}
		}

		foreach ($gifts as $id => &$gift) {
			$storage_gifts = $this->_cart_gifts[$id];
			foreach ($gift['gifts'] as &$inner_gift) {
				$inner_gift['quantity'] = $storage_gifts[$inner_gift['id']];
			}
		}

				
		return $this->_autoAdd($gifts);
	}


	protected function _getRemain($gifts, $quantity)
	{
		$remain = 0;
		if (!empty($gifts)) {
			$count = 0;
			foreach ($gifts as $g) {
				$count += $g['quantity'];
			}
			$remain = ($count < $quantity) ? $quantity - $count : 0;
		}

		return $remain;
	}


	protected function _autoAdd($gifts)
	{
		if (!empty($gifts)) {
			foreach ($gifts as $product_id => $v) {
				if ($remain = $this->_getRemain($v['gifts'], $v['quantity'])) {
					foreach ($v['gifts'] as $k => $g) {
						if ($remain > 0 && !isset($this->_cart_gifts[$product_id][$g['id']])) {
							if (!$this->_ignore_stock_count && $g['count'] !== null) {
								$c = ($g['count'] >= $remain) ? $remain : $g['count'];
							}
							else {
								$c = $remain;
							}
							$remain -= $c;
							$gifts[$product_id]['gifts'][$k]['quantity'] = $c;
						}
						else {
							break;
						}
					}
				}
			}
		}

		return $gifts;
	}

}