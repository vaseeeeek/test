<?php

/**
 * @property-read bool $is_shown
 * @property-read bool $show_in_stock_only
 * @property-read bool $show_on_primary_product_only
 * @property-read bool $show_header
 * @property-read bool $current_product_first
 * @property-read string $image_size
 */
class shopProductgroupGroupSettings extends shopProductgroupImmutableStructure
{
	protected $is_shown;
	protected $show_in_stock_only;
	protected $show_on_primary_product_only;
	protected $show_header;
	protected $current_product_first;
	protected $image_size;

	public function __construct(
		$is_shown,
		$show_in_stock_only,
		$show_on_primary_product_only,
		$show_header,
		$current_product_first,
		$image_size
	)
	{
		$this->is_shown = $is_shown;
		$this->show_in_stock_only = $show_in_stock_only;
		$this->show_on_primary_product_only = $show_on_primary_product_only;
		$this->show_header = $show_header;
		$this->current_product_first = $current_product_first;
		$this->image_size = $image_size;
	}

	public function toAssoc()
	{
		return [
			'is_shown' => $this->is_shown,
			'show_in_stock_only' => $this->show_in_stock_only,
			'show_on_primary_product_only' => $this->show_on_primary_product_only,
			'show_header' => $this->show_header,
			'current_product_first' => $this->current_product_first,
			'image_size' => $this->image_size,
		];
	}
}