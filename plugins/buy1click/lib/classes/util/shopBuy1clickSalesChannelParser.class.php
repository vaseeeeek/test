<?php


class shopBuy1clickSalesChannelParser
{
	public function parse($sales_channel)
	{
		if (!preg_match('/^buy1click:(.*)$/', $sales_channel, $matches))
		{
			return null;
		}
		
		return array(
			'storefront' => $matches[1],
		);
	}
}