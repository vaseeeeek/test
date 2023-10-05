<?php

class shopEditSeofilterMoveMetaTagsFormState
{
	/** @var shopEditSeofilterPersonalRuleFieldsSelection */
	public $source_personal_rule_fields_selection;
	/** @var shopEditStorefrontSelection */
	public $destination_storefront_selection;
	public $drop_source_tags = false;
	public $overwrite_destination_tags = false;

	public function __construct($settings = null)
	{
		if (is_array($settings))
		{
			$this->source_personal_rule_fields_selection = new shopEditSeofilterPersonalRuleFieldsSelection($settings['source_personal_rule_fields_selection']);
			$this->destination_storefront_selection = new shopEditStorefrontSelection($settings['destination_storefront_selection']);
			$this->drop_source_tags = $settings['drop_source_tags'];
			$this->overwrite_destination_tags = $settings['overwrite_destination_tags'];

			if ($this->destination_storefront_selection->mode != shopEditStorefrontSelection::MODE_SELECTED)
			{
				throw new waException('Некорректный выбор витрины назначения - нельзя "для всех витрин"');
			}
		}
		else
		{
			$this->source_personal_rule_fields_selection = new shopEditSeofilterPersonalRuleFieldsSelection();

			$this->destination_storefront_selection = new shopEditStorefrontSelection();
			$this->destination_storefront_selection->mode = shopEditStorefrontSelection::MODE_SELECTED;
		}
	}

	public function assoc()
	{
		return array(
			'source_personal_rule_fields_selection' => $this->source_personal_rule_fields_selection->assoc(),
			'destination_storefront_selection' => $this->destination_storefront_selection->assoc(),
			'drop_source_tags' => $this->drop_source_tags,
			'overwrite_destination_tags' => $this->overwrite_destination_tags,
		);
	}
}