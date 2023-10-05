<?php

/**
 * Class shopEditLogExtended
 *
 * @property array $params
 * @property array $actor
 * @property string $datetime_humandatetime
 */
class shopEditLogExtended extends shopEditLog
{
	public function assoc()
	{
		$assoc = parent::assoc();

		$assoc['params'] = $this->params;
		$assoc['actor'] = $this->actor;
		$assoc['datetime_humandatetime'] = $this->datetime_humandatetime;

		return $assoc;
	}
}