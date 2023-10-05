<?php

/**
 * @property-read $id
 * @property-read $name
 * @property-read string $markup_template_id
 * @property-read bool $is_shown
 * @property-read int $related_feature_id
 * @property-read $sort
 */
class shopProductgroupGroup extends shopProductgroupImmutableStructure
{
	protected $id;
	protected $name;
	protected $markup_template_id;
	protected $is_shown;
	protected $related_feature_id;
	protected $sort;

	public function __construct(
		$id,
		$name,
		$markup_template_id,
		$is_shown,
		$related_feature_id,
		$sort
	)
	{
		$this->id = $id;
		$this->name = $name;
		$this->markup_template_id = $markup_template_id;
		$this->is_shown = $is_shown;
		$this->related_feature_id = $related_feature_id;
		$this->sort = $sort;
	}

	public function toAssoc()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'markup_template_id' => $this->markup_template_id,
			'is_shown' => $this->is_shown,
			'related_feature_id' => $this->related_feature_id,
			'sort' => $this->sort,
		];
	}
}