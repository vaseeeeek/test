<?php

class shopBrandBrandReviewModel extends waNestedSetModel
{
	protected $table = 'shop_brand_brand_review';

	protected $left = 'left_key';
	protected $right = 'right_key';
	protected $depth = 'depth';
	protected $parent = 'parent_id';
}