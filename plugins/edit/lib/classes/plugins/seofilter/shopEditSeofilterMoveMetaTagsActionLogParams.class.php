<?php

class shopEditSeofilterMoveMetaTagsActionLogParams
{
	/** @var shopEditSeofilterMoveMetaTagsFormState */
	public $settings;
	public $source_rule_statistics = array();

	public function __construct($action_settings)
	{
		$this->settings = $action_settings;
	}

	public function logNewRuleSaveSuccess($source_rule_id, $new_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['new_rule_ids'][] = $new_rule_id;
	}

	public function logNewRuleSaveError($source_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['new_rule_save_errors_count'] += 1;
	}

	public function logExistingRuleUpdateSuccess($source_rule_id, $existing_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['updated_rule_ids'][] = $existing_rule_id;
	}

	public function logExistingRuleUpdateError($source_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['updated_rule_update_errors_count'] += 1;
	}

	public function logExistingRuleUpdateSkip($source_rule_id, $existing_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['skipped_existing_rule_ids'][] = $existing_rule_id;
	}

	public function logSourceRuleHasError($source_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['has_save_errors'] = true;
	}

	public function logSourceRuleDeletion($source_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['was_deleted'] = true;
	}

	public function logSourceRuleDeletionError($source_rule_id)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['has_deletion_error'] = true;
	}

	public function logSourceRuleFieldsEmptied($source_rule_id, $emptied_fields)
	{
		$this->initializeSourceRuleRecord($source_rule_id);

		$this->source_rule_statistics[$source_rule_id]['source_emptied_fields'] = $emptied_fields;
	}

	public function assoc()
	{
		$source_rules_count = count($this->source_rule_statistics);

		$new_rules_count = 0;
		$new_rule_save_errors_count = 0;

		$updated_rules_count = 0;
		$updated_rule_update_errors_count = 0;

		$skipped_rules_count = 0;

		$updated_source_rules_count = 0;
		$deleted_source_rules_count = 0;

		foreach ($this->source_rule_statistics as $source_rule_id => &$source_rule_statistic)
		{
			$new_rules_count += count($source_rule_statistic['new_rule_ids']);
			$new_rule_save_errors_count += $source_rule_statistic['new_rule_save_errors_count'];

			$updated_rules_count += count($source_rule_statistic['updated_rule_ids']);
			$updated_rule_update_errors_count += $source_rule_statistic['updated_rule_update_errors_count'];

			$skipped_rules_count += count($source_rule_statistic['skipped_existing_rule_ids']);

			if (count($source_rule_statistic['source_emptied_fields']) > 0)
			{
				$updated_source_rules_count += 1;
			}

			if ($source_rule_statistic['was_deleted'])
			{
				$deleted_source_rules_count += 1;
			}
		}

		return array(
			'settings' => $this->settings->assoc(),
			'source_rule_statistics' => $this->source_rule_statistics,

			'source_rules_count' => $source_rules_count,

			'new_rules_count' => $new_rules_count,
			'new_rule_save_errors_count' => $new_rule_save_errors_count,

			'updated_rules_count' => $updated_rules_count,
			'updated_rule_update_errors_count' => $updated_rule_update_errors_count,

			'skipped_rules_count' => $skipped_rules_count,

			'updated_source_rules_count' => $updated_source_rules_count,
			'deleted_source_rules_count' => $deleted_source_rules_count,
		);
	}

	private function initializeSourceRuleRecord($source_rule_id)
	{
		if (!array_key_exists($source_rule_id, $this->source_rule_statistics))
		{
			$this->source_rule_statistics[$source_rule_id] = array(
				'new_rule_ids' => array(),
				'new_rule_save_errors_count' => 0,

				'updated_rule_ids' => array(),
				'updated_rule_update_errors_count' => 0,

				'skipped_existing_rule_ids' => array(),

				'has_save_errors' => false,
				'was_deleted' => false,
				'has_deletion_error' => false,

				'source_emptied_fields' => array(),
			);
		}
	}
}