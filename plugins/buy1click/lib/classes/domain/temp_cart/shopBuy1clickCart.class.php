<?php


interface shopBuy1clickCart
{
	public function getCode();
	
	public function addItem($item, $services = array());
	
	public function setQuantity($item_id, $quantity);
	
	public function getHierarchyItems();
	
	public function getItems();
	
	public function getTotalWithDiscount();
	
	public function getTotal();
	
	public function clear();
	
	public function toArray();
}