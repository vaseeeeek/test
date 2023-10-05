<?php


class shopBuy1clickShippingFilterCondition
{
	private $is_filter_current_storefront = false;
	private $is_filter_by_shipping_address = false;
	/** @var shopBuy1clickContactInfoShippingAddress */
	private $shipping_address;
	private $is_filter_by_shipping_ids = false;
	private $shipping_ids = array();
	private $is_filter_delpayfilter = false;
	/** @var shopBuy1clickCart */
	private $delpayfilter_cart;
	private $is_filter_checkcustomer = false;

	public function isFilterCurrentStorefront()
	{
		return $this->is_filter_current_storefront;
	}

	public function filterByCurrentStorefront()
	{
		$this->is_filter_current_storefront = true;

		return $this;
	}

	public function isFilterByShippingAddress()
	{
		return $this->is_filter_by_shipping_address;
	}

	public function getShippingAddress()
	{
		return $this->shipping_address;
	}

	public function filterByShippingAddress($shipping_address)
	{
		$this->is_filter_by_shipping_address = true;
		$this->shipping_address = $shipping_address;

		return $this;
	}

	public function isFilterByShippingIDs()
	{
		return $this->is_filter_by_shipping_ids;
	}

	public function getShippingIDs()
	{
		return $this->shipping_ids;
	}

	public function filterByShippingIDs($ids)
	{
		$this->is_filter_by_shipping_ids = true;
		$this->shipping_ids = $ids;
		
		return $this;
	}
	
	public function isFilterDelpayfilter()
	{
		return $this->is_filter_delpayfilter;
	}
	
	public function getDelpayfilterCart()
	{
		return $this->delpayfilter_cart;
	}
	
	public function filterDelpayfilter(shopBuy1clickCart $cart)
	{
		$this->is_filter_delpayfilter = true;
		$this->delpayfilter_cart = $cart;
		
		return $this;
	}
	
	public function isFilterCheckcustomer()
	{
		return $this->is_filter_checkcustomer;
	}
	
	public function filterCheckcustomer()
	{
		$this->is_filter_checkcustomer = true;
		
		return $this;
	}
}