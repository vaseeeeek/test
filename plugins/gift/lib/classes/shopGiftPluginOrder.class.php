<?php

class shopGiftPluginOrder
{
	public function insertOrderItem($product,$order_id)
	{
		if ( $order_id > 0 )
		{
			$model = new shopOrderItemsModel;
			$fields = array(
				'order_id' => $order_id,
				'sku_id' => $product['sku_id']
			);
			
			if ( $row = $model->getByField($fields) )
				$model->updateById($row['id'],array('quantity'=>$row['quantity']+1));
			else
			{
				$data = array(
					'order_id' => $order_id,
					'name' => $product['name'],
					'product_id' => $product['id'],
					'sku_id' => $product['sku_id'],
					'type' => 'product',
					'price' => 0,
					'quantity' => $product['quantity'],
				);
				$model->insert($data);
			}
		}
	}
	
	
	public function sendNotification($data)
	{
		$order_id = $data['order_id'];
		$data['action_id'] = 'gift';
		$order_model = new shopOrderModel();
		$order = $order_model->getById($order_id);
		$params_model = new shopOrderParamsModel();
		$order['params'] = $params_model->get($order_id);
		// send notifications
		shopNotifications::send('order.gift', array(
			'order' => $order,
			'customer' => new waContact($order['contact_id']),
			'status' => '',
			'action_data' => $data
		));
	}

}