<?php

class shopGiftPluginProductGiftModel extends waModel
{
	protected $table = 'shop_gift_product_gift';


	public function setGift($product_id, $gift_id)
	{
		$product_id = (int)$product_id;
		$gift_id = (int)$gift_id;
		if ($product_id > 0) {
			$this->deleteByField('product_id', $product_id);
			if ($gift_id > 0) {
				$this->insert(array(
					'product_id' => $product_id,
					'gift_id'    => $gift_id,
				));
			}
		}
	}


	public function setGifts($product_id, $ids)
	{
		$product_id = (int)$product_id;
		if ($product_id > 0) {
			$this->deleteByField('product_id', $product_id);
			if (!empty($ids)) {
				foreach ($ids as $order => $gift_id) {
					$this->insert(array(
						'product_id' => $product_id,
						'gift_id'    => $gift_id,
						'order'      => $order,
					));
				}
			}
		}
	}


	public function getGiftIds($product_id)
	{
		$ids = array();
		$product_ids = array();

		if (!empty($product_id)) {
			if (is_array($product_id)) {
				$product_ids = $product_id;
			}
			elseif ($product_id > 0) {
				$product_ids = array($product_id);
			}
		}

		if (!empty($product_ids)) {
			$product_ids = array_map('intval', $product_ids);
			$in = implode(',', $product_ids);
			$r = $this->select('gift_id')->where("product_id IN ($in)");
			if (count($product_ids) == 1) {
				$records = $r->order('`order` ASC')->fetchAll();
			}
			else {
				$records = $r->fetchAll();
			}

			$k = array();
			foreach ($records as $v) {
				$k[$v['gift_id']] = 1;
			}

			$ids = array_keys($k);
		}

		return $ids;
	}


	public function getProductIds($gift_id)
	{
		$ids = array();
		$gift_ids = array();

		if (!empty($gift_id)) {
			if (is_array($gift_id)) {
				$gift_ids = $gift_id;
			}
			elseif ($gift_id > 0) {
				$gift_ids = array($gift_id);
			}
		}

		if (!empty($gift_ids)) {
			$gift_ids = array_map('intval', $gift_ids);
			$in = implode(',', $gift_ids);
			$records = $this->where("gift_id IN ($in)")->fetchAll();

			$k = array();
			foreach ($records as $v) {
				$k[$v['product_id']] = 1;
			}

			$ids = array_keys($k);
		}

		return $ids;
	}
}