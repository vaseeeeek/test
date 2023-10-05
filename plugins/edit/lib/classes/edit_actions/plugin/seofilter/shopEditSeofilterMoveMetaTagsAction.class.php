<?php

class shopEditSeofilterMoveMetaTagsAction extends shopEditLoggedAction
{
	private $settings;
	private $rule_ar;

	public function __construct(shopEditSeofilterMoveMetaTagsFormState $settings)
	{
		parent::__construct();

		$this->settings = $settings;
		$this->rule_ar = new shopSeofilterFilterPersonalRule();
	}

	protected function execute()
	{
		$log_params = new shopEditSeofilterMoveMetaTagsActionLogParams($this->settings);

		$source_rule_ids_query = $this->querySourceRules();

		foreach ($source_rule_ids_query as $row)
		{
			$source_rule = $this->rule_ar->getById($row['id']);

			if (!$source_rule)
			{
				continue;
			}

			// todo выбор: несколько правил с одной витриной, либо одно правило с несколькими
			foreach ($this->settings->destination_storefront_selection->storefronts as $destination_storefront)
			{
				$new_rules = $this->createNewRules($source_rule, $destination_storefront);

				$all_saved = $this->saveNewRules($new_rules, $source_rule, $log_params);


				if (!$all_saved)
				{
					$log_params->logSourceRuleHasError($source_rule->id);

					continue;
				}

				if ($this->settings->drop_source_tags)
				{
					$this->dropTagsFromSourceRule($source_rule, $log_params);
				}
			}
		}
		unset($source_rule);

		return $log_params->assoc();
	}

	protected function getAction()
	{
		return $this->action_options->SEOFILTER_MOVE_META_TAGS;
	}





	private function querySourceRules()
	{
		return $this->rule_ar->model()->select('id')
			->where("storefronts_use_mode = 'ALL'")
			->where("categories_use_mode = 'ALL'")
			->query();
	}

	private function getExistingRuleIds(shopSeofilterFilterPersonalRule $source_rule, $destination_storefront)
	{
		$existing_storefront_personal_rule_sql = "
SELECT rule.id AS id
FROM shop_seofilter_filter_personal_rule AS rule
	JOIN shop_seofilter_filter_personal_rule_storefront AS all_rule_storefronts
		ON all_rule_storefronts.rule_id = rule.id
	JOIN shop_seofilter_filter_personal_rule_storefront AS destination_rule_storefront
		ON destination_rule_storefront.rule_id = rule.id AND destination_rule_storefront.storefront = :destination_storefront
WHERE rule.filter_id = :filter_id AND rule.categories_use_mode = 'ALL' AND rule.storefronts_use_mode = 'LISTED'
GROUP BY rule.id
HAVING COUNT(all_rule_storefronts.storefront) = 1
";

		$existing_rule_query_params = array(
			'filter_id' => $source_rule->filter_id,
			'destination_storefront' => $destination_storefront,
		);
		$existing_rule_ids = $this->rule_ar->model()
			->query($existing_storefront_personal_rule_sql, $existing_rule_query_params)
			->fetchAll();

		return array_map(array($this, 'getId'), $existing_rule_ids);
	}

	/**
	 * @param shopSeofilterFilterPersonalRule $source_rule
	 * @param $destination_storefront
	 * @return shopSeofilterFilterPersonalRule[]
	 */
	private function createNewRules(shopSeofilterFilterPersonalRule $source_rule, $destination_storefront)
	{
		$fields_to_copy = $this->settings->source_personal_rule_fields_selection->getSelectedFields();

		$existing_rule_ids = $this->getExistingRuleIds($source_rule, $destination_storefront);

		/** @var shopSeofilterFilterPersonalRule[] $new_rules */
		$new_rules = count($existing_rule_ids) > 0
			? $this->rule_ar->getById($existing_rule_ids)
			: array(new shopSeofilterFilterPersonalRule(),);

		foreach ($new_rules as $new_rule)
		{
			$new_rule->filter_id = $source_rule->filter_id;
			$new_rule->is_enabled = $source_rule->is_enabled;
			$new_rule->categories_use_mode = shopSeofilterFilterPersonalRule::USE_MODE_ALL;
			$new_rule->storefronts_use_mode = shopSeofilterFilterPersonalRule::USE_MODE_LISTED;
			$new_rule->rule_storefronts = array($destination_storefront);

			foreach ($fields_to_copy as $field)
			{
				$new_rule->$field = $source_rule->$field;
			}
		}
		unset($new_rule);

		return $new_rules;
	}

	/**
	 * @param shopSeofilterFilterPersonalRule[] $new_rules
	 * @param shopSeofilterFilterPersonalRule $source_rule
	 * @param shopEditSeofilterMoveMetaTagsActionLogParams $log_params
	 * @return bool
	 */
	private function saveNewRules($new_rules, $source_rule, $log_params)
	{
		$all_saved = true;
		foreach ($new_rules as $new_rule)
		{
			if ($new_rule->getIsNewRecord())
			{
				if ($new_rule->save())
				{
					$log_params->logNewRuleSaveSuccess($source_rule->id, $new_rule->id);
				}
				else
				{
					$log_params->logNewRuleSaveError($source_rule->id);

					$all_saved = false;
				}
			}
			else
			{
				if ($this->settings->overwrite_destination_tags)
				{
					$save_success = $new_rule->save();

					if ($save_success)
					{
						$log_params->logExistingRuleUpdateSuccess($source_rule->id, $new_rule->id);
					}
					else
					{
						$log_params->logExistingRuleUpdateError($source_rule->id);
						$all_saved = false;
					}
				}
				else
				{
					$log_params->logExistingRuleUpdateSkip($source_rule->id, $new_rule->id);
				}
			}
		}

		return $all_saved;
	}

	private function dropTagsFromSourceRule(shopSeofilterFilterPersonalRule $source_rule, shopEditSeofilterMoveMetaTagsActionLogParams $log_params)
	{
		$source_rule_id = $source_rule->id;
		$fields_to_copy = $this->settings->source_personal_rule_fields_selection->getSelectedFields();

		if ($this->settings->source_personal_rule_fields_selection->areAllSelected())
		{
			if ($source_rule->delete())
			{
				$log_params->logSourceRuleDeletion($source_rule_id);
			}
			else
			{
				$log_params->logSourceRuleDeletionError($source_rule_id);
			}
		}
		else
		{
			$emptied_fields = array();

			foreach ($fields_to_copy as $field)
			{
				$was_emptied = $this->emptyRuleFieldValue($source_rule, $field); // todo log source updated
				if ($was_emptied)
				{
					$emptied_fields[] = $field;
				}
			}

			if ($this->ruleIsEmpty($source_rule))
			{
				if ($source_rule->delete())
				{
					$log_params->logSourceRuleDeletion($source_rule_id);
				}
				else
				{
					$log_params->logSourceRuleDeletionError($source_rule_id);
				}
			}
			else
			{
				if ($source_rule->save())
				{
					$log_params->logSourceRuleFieldsEmptied($source_rule_id, $emptied_fields);
				}
			}
		}
	}

	private function emptyRuleFieldValue(shopSeofilterFilterPersonalRule $rule, $field)
	{
		$empty_value = $field == shopEditSeofilterPersonalRuleFieldsSelection::FIELD_IS_PAGINATION_TEMPLATES_ENABLED
			? false
			: '';

		if ($rule->$field === $empty_value)
		{
			return false;
		}

		$rule->$field = $empty_value;

		return true;
	}

	private function ruleIsEmpty(shopSeofilterFilterPersonalRule $rule)
	{
		$fields_not_modified = $this->settings->source_personal_rule_fields_selection->getFieldsExceptSelected();

		foreach ($fields_not_modified as $field)
		{
			if ($field == shopEditSeofilterPersonalRuleFieldsSelection::FIELD_IS_PAGINATION_TEMPLATES_ENABLED)
			{
				if ($rule->$field)
				{
					return false;
				}
			}
			else
			{
				if ($rule->$field !== '')
				{
					return false;
				}
			}
		}

		return true;
	}



	private function getId($array)
	{
		return $array['id'];
	}
}