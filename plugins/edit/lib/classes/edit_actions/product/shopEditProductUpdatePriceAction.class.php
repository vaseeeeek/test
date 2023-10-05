<?php

/**
 * Class shopEditProductUpdatePriceAction
 *
 * массовое изменение цен аотикулов
 * хуки product_presave, product_save не вызывается!
 *
 * todo по-любому нужен longController
 */
class shopEditProductUpdatePriceAction extends shopEditLoggedAction
{
	private $form_state;

	private $model;

	public function __construct(shopEditProductUpdatePriceFormState $form_state)
	{
		parent::__construct();

		$this->form_state = $form_state;

		$this->model = new waModel();
	}

	protected function execute()
	{
		$price_fields = $this->getPriceFields();

		$product_ids = $this->getProductIds();

		if (count($product_ids) == 0)
		{
			return array(
				'form_state' => $this->form_state->assoc(),
				'affected_product_ids' => array(),
				'affected_products_count' => 0,
			);
		}

		$query_params = $this->prepareQueryParams($product_ids);

		foreach ($price_fields as $price_field)
		{
			$price_evaluation_statement = $this->getPriceEvaluationStatement($price_field);

			$set_updated_prices_statement = "SET {$price_field} = " . $this->getRoundingStatement($price_evaluation_statement);

			$where_parts = array('product_id IN (:product_ids)');
			if ($this->form_state->skip_zero_price)
			{
				$where_parts[] = "{$price_field} > 0";
			}

			$where_statement = 'WHERE ' . implode(' AND ', $where_parts);

			$update_sql = "
UPDATE shop_product_skus
{$set_updated_prices_statement}
{$where_statement}";

			/** @var waDbResultUpdate $query_result */

			$this->model->exec($update_sql, $query_params);
		}

		if ($this->form_state->price_type_selection->hasPriceOrComparePrice())
		{
			$this->correctProductPrices($product_ids);
		}

		if ($this->form_state->price_type_selection->hasMainPrice())
		{
			$this->correctSkuPrimaryPrice($product_ids);
		}

		$this->form_state->price_type_selection->mode = shopEditPriceTypeSelection::MODE_SELECTED;
		$this->form_state->price_type_selection->selected_ids = $price_fields;

		return array(
			'form_state' => $this->form_state->assoc(),
			'affected_product_ids' => $product_ids,
			'affected_products_count' => count($product_ids),
		);
	}

	protected function getAction()
	{
		return $this->action_options->PRODUCT_UPDATE_PRICE;
	}

	private function getProductIds()
	{
		$collection = new shopEditProductUpdatePriceActionProductsCollection('all', array('form_state' => $this->form_state));
		$fields = 'id';
		$sql = $collection->getProductSQL($fields);

		$ids = array();
		foreach ($this->model->query($sql) as $row)
		{
			$ids[] = $row['id'];
		}

		return $ids;
	}

	private function prepareQueryParams(&$product_ids)
	{
		$query_params = array();

		$query_params['product_ids'] = &$product_ids;

		if ($this->form_state->round_mode != shopEditProductRoundModes::NONE)
		{
			$rounding = shopEditProductRoundModes::getRoundModeRounding($this->form_state->round_mode);
			list($round_unit, $shift, $precision) = shopRounding::getRoundingVars($rounding);

			$query_params['round_unit'] = $round_unit;
			$query_params['shift'] = $shift;
			$query_params['precision'] = $precision;
		}

		$query_params['change_price_amount_fixed'] = abs($this->form_state->change_price_amount);
		$query_params['change_price_amount_multiplier'] = 1 + $this->form_state->change_price_amount / 100;

		return $query_params;
	}

	private function getPriceFields()
	{
		$price_type_storage = new shopEditProductPriceTypeStorage();

		$price_fields = array();
		foreach ($price_type_storage->getSelectedPriceTypes($this->form_state->price_type_selection) as $price_type)
		{
			$price_fields[] = $price_type['id'];
		}

		return $price_fields;
	}

	private function getPriceEvaluationStatement($price_statement)
	{
		if ($this->form_state->change_price_mode == shopEditProductUpdatePriceFormState::CHANGE_PRICE_MODE_FIXED)
		{
			return $this->form_state->change_price_amount > 0
				? "{$price_statement} + :change_price_amount_fixed"
				: "{$price_statement} - :change_price_amount_fixed";
		}

		if ($this->form_state->change_price_mode == shopEditProductUpdatePriceFormState::CHANGE_PRICE_MODE_PERCENT)
		{
			return "{$price_statement} * :change_price_amount_multiplier";
		}

		throw new waException("Некорректный режим модификации цены [{$this->form_state->change_price_mode}]");
	}

	private function getRoundingStatement($price_statement)
	{
		if ($this->form_state->round_mode == shopEditProductRoundModes::NONE)
		{
			return $price_statement;
		}

		return $this->form_state->round_up_only
			? "CEIL(({$price_statement} + :shift) / :round_unit) * :round_unit - :shift"
			: "ROUND({$price_statement} + :shift, :precision) - :shift";
	}

	private function correctProductPrices(&$affected_product_ids)
	{
		$product_model = new shopProductModel();

		foreach ($affected_product_ids as $product_id)
		{
			$product_model->correct($product_id);
		}
	}

	private function correctSkuPrimaryPrice(&$affected_product_ids)
	{
		/** @var shopConfig $config */
		$config = wa('shop')->getConfig();
		$primary_currency = $config->getCurrency();

		$query_params = array(
			'primary_currency' => $primary_currency,
			'product_ids' => &$affected_product_ids,
		);

		$primary_price_in_shop_currency_update = '
UPDATE shop_product_skus AS skus, shop_product AS product
SET skus.primary_price = skus.price
WHERE
	product.id = skus.product_id
	AND (product.currency = :primary_currency OR skus.price = 0)
	AND skus.product_id IN (:product_ids)
';

		$this->model->exec($primary_price_in_shop_currency_update, $query_params);




		$currency_model = new shopCurrencyModel();

		$skus_of_different_currency_select_sql = '
SELECT sku.id AS sku_id,
	sku.price AS sku_price,
	product.currency AS product_currency
FROM shop_product_skus AS sku
	JOIN shop_product AS product
		ON product.id = sku.product_id
WHERE product.currency != :primary_currency AND product.id IN (:product_ids) AND sku.price > 0
';

		$UPDATE_SIZE = 50;
		$sku_update_statement_parts = array();
		$sku_updates_params = array();

		foreach ($this->model->query($skus_of_different_currency_select_sql, $query_params) as $row)
		{
			$sku_id = $row['sku_id'];

			$price_param = 'price_param_' . $sku_id;

			$sku_update_statement_parts[] = "({$sku_id}, :{$price_param})";
			$sku_updates_params[$price_param] = $currency_model->convert($row['sku_price'], $row['product_currency'], $primary_currency);

			if (count($sku_update_statement_parts) >= $UPDATE_SIZE)
			{
				$this->updateSkuPrimaryPrice($sku_update_statement_parts, $sku_updates_params);

				$sku_update_statement_parts = array();
				$sku_updates_params = array();
			}
		}

		$this->updateSkuPrimaryPrice($sku_update_statement_parts, $sku_updates_params);
	}

	private function updateSkuPrimaryPrice(&$sku_update_statement_parts, &$sku_updates_params)
	{
		if (count($sku_update_statement_parts) == 0)
		{
			return;
		}

		$updates = implode(',', $sku_update_statement_parts);

		$sql = "
INSERT INTO shop_product_skus
(id, primary_price)
VALUES {$updates}
ON DUPLICATE KEY UPDATE primary_price = VALUES(primary_price)
";

		$this->model->exec($sql, $sku_updates_params);
	}
}