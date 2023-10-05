<?php


// todo сделать confirmationFlow ???
class shopBuy1clickConfirmationChannelService
{
	private $env;

	public function __construct(shopBuy1clickEnv $env)
	{
		$this->env = $env;
	}

	/**
	 * @param string|null $email
	 * @param string|null $phone
	 * @return shopBuy1clickConfirmationChannel
	 */
	public function getConfirmationChannel($email, $phone)
	{
		$options = [
			'is_company' => 0,
			'address' => [
				'email' => $email,
				'phone' => $phone,
			],
		];

		$wa_confirmation_channel = $this->env->getConfirmationChannel($options);

		return new shopBuy1clickWaShopConfirmationChannel($wa_confirmation_channel);
	}

	/**
	 * @return shopBuy1clickConfirmationChannelValidator
	 */
	public function getConfirmationChannelValidator()
	{
		$wa_confirmation_channel = $this->env->getConfirmationChannel();

		return new shopBuy1clickConfirmationChannelValidator($this->env, $wa_confirmation_channel);
	}
}
