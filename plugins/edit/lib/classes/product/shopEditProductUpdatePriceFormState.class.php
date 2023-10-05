<?php

class shopEditProductUpdatePriceFormState
{
	const CHANGE_PRICE_MODE_PERCENT = 'PERCENT';
	const CHANGE_PRICE_MODE_FIXED = 'FIXED';

	/** @var shopEditProductsSelection */
	public $products_selection;
	/** @var bool */
	public $skip_zero_price;
	/** @var string */
	public $only_with_currency;
	/** @var shopEditPriceTypeSelection */
	public $price_type_selection;

	public $change_price_mode;
	public $change_price_amount;

	public $round_mode;
	/** @var bool */
	public $round_up_only;

	public function __construct($params)
	{
		if (!is_array($params))
		{
			throw new waException();
		}

		$this->products_selection = new shopEditProductsSelection($params['products_selection']);
		$this->skip_zero_price = !!$params['skip_zero_price'];
		$this->only_with_currency = $params['only_with_currency'];
		$this->price_type_selection = new shopEditPriceTypeSelection($params['price_type_selection']);

		$this->change_price_mode = $params['change_price_mode'];
		$this->change_price_amount = $params['change_price_amount'];

		$this->round_mode = $params['round_mode'];
		$this->round_up_only = !!$params['round_up_only'];
	}

	/**
	 * @return array|null
	 */
	public function getOnlyWithCurrencyCurrency()
	{
		$currency_model = new shopCurrencyModel();

		return $currency_model->getById($this->only_with_currency);
	}

	public function assoc()
	{
		return array(
			'products_selection' => $this->products_selection->assoc(),
			'skip_zero_price' => $this->skip_zero_price,
			'only_with_currency' => $this->only_with_currency,
			'price_type_selection' => $this->price_type_selection->assoc(),
			'change_price_mode' => $this->change_price_mode,
			'change_price_amount' => $this->change_price_amount,
			'round_mode' => $this->round_mode,
			'round_up_only' => $this->round_up_only,
		);
	}
}