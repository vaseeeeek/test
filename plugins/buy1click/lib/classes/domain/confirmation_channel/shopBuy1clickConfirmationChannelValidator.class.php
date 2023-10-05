<?php

class shopBuy1clickConfirmationChannelValidator
{
	private $env;
	/** @var shopConfirmationChannel|null */
	private $wa_confirmation_channel;

	public function __construct(shopBuy1clickEnv $env, $wa_confirmation_channel)
	{
		$this->env = $env;
		$this->wa_confirmation_channel = $wa_confirmation_channel;
	}

	public function getConfirmationSourceErrors($source)
	{
		if (!$this->wa_confirmation_channel)
		{
			return [];
		}

		$active_channel = $this->wa_confirmation_channel->getActiveType();
		if ($active_channel === 'phone')
		{
			$source = $this->env->transformPhone($source);
		}

		$source = $this->wa_confirmation_channel->cleanSource($source, $active_channel);

		$this->wa_confirmation_channel->validateSource($source);

		$invalid_transport = $this->wa_confirmation_channel->getTransportError();
		if ($invalid_transport)
		{
			return [$invalid_transport];
		}

		$timeout_left = $this->wa_confirmation_channel->getSendTimeout();
		if ($timeout_left > 0)
		{
			return [[
				'id' => 'timeout_error',
				'text' => _w('Wait for %d second', 'Wait for %d seconds', $timeout_left),
			]];
		}

		if (!$source || !$this->wa_confirmation_channel->isValidateSource($source))
		{
			return [[
				'id' => 'source_error',
				'text' => _w('Incorrect data specified to send a code'),
			]];
		}

		return [];
	}

	public function getConfirmationCodeErrors($code, &$source)
	{
		if (!$this->wa_confirmation_channel)
		{
			return [];
		}

		$invalid_transport = $this->wa_confirmation_channel->getTransportError();
		if ($invalid_transport)
		{
			return [$invalid_transport];
		}

		$verification = $this->wa_confirmation_channel->getStorage('verification');
		if (!$verification)
		{
			return [[
				'id'   => 'storage_error',
				'text' => _w('Code has not been sent')
			]];
		}

		$validation_result = $this->wa_confirmation_channel->validateCode($code);
		if (!$validation_result['status'])
		{
			if (is_null($validation_result['details']['rest_tries']) || $validation_result['details']['rest_tries'] == 0)
			{
				$this->wa_confirmation_channel->delStorage('verification');
				return [[
					'id'   => 'code_attempts_error',
					'text' => _w('You have run out of available attempts. Please send a new SMS.')
				]];
			}
			else
			{
				return [[
					'id'   => 'code_error',
					'text' => _w('You have entered an incorrect code. %d more attempt is available.', 'You have entered an incorrect code. %d more attempts are available.', $validation_result['details']['rest_tries']),
				]];
			}
		}

		// todo (((
		$source = $verification['source'];

		return [];
	}

	/*
	 * todo методов ниже ну вот вообще не тут это должно быть
	 *
	 */

	public function sendConfirmationCode($source)
	{
		if (!$this->wa_confirmation_channel)
		{
			return false;
		}

		if (!$this->wa_confirmation_channel->sendCode($source))
		{
			return false;
		}

		$verification = [
			'source' => $source,
			//'attempts' => shopConfirmationChannel::ATTEMPTS_TO_VERIFY_CODE,
			'attempts' => 3,
		];

		$this->wa_confirmation_channel->setStorage($verification, 'verification');
		$this->wa_confirmation_channel->setStorage(time(), 'send_time');

		return true;
	}

	public function setConfirmed()
	{
		if (!$this->wa_confirmation_channel)
		{
			return;
		}

		$this->wa_confirmation_channel->setConfirmed();
	}

	public function getActiveType()
	{
		return $this->wa_confirmation_channel
			? $this->wa_confirmation_channel->getActiveType()
			: '';
	}
}
