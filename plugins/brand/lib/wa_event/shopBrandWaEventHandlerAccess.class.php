<?php

class shopBrandWaEventHandlerAccess extends waEvent
{
	const BLANK_EVENT_NAME = 'brand_blank_event_name';

	private static $saved_handlers = array();

	public static function removePluginHandler($app_id, $plugin_id, $event_name)
	{
		$event = new waEvent('shop', self::BLANK_EVENT_NAME);
		$event->setStaticData();

		if (!isset(self::$handlers[$app_id]) || !is_array(self::$handlers[$app_id]))
		{
			return;
		}

		if (!isset(self::$handlers[$app_id][$event_name]) || !is_array(self::$handlers[$app_id][$event_name]))
		{
			return;
		}

		$remaining_handlers = array();
		foreach (self::$handlers[$app_id][$event_name] as $handler_params)
		{
			if (isset($handler_params['plugin_id']) && $handler_params['plugin_id'] === $plugin_id)
			{
				self::rememberEventHandlers($app_id, $event_name, self::$handlers[$app_id][$event_name]);

				continue;
			}

			$remaining_handlers[] = $handler_params;
		}

		if (count($remaining_handlers) === 0)
		{
			unset(self::$handlers[$app_id][$event_name]);
		}
		else
		{
			self::$handlers[$app_id][$event_name] = $remaining_handlers;
		}
	}

	public static function restoreRemovedHandlers()
	{
		return;

		if (count(self::$saved_handlers) === 0)
		{
			return;
		}

		foreach (self::$saved_handlers as $app_id => $events)
		{
			foreach ($events as $event_name => $handlers)
			{
				self::$handlers[$app_id][$event_name] = $handlers;
			}
		}

		self::$saved_handlers = array();
	}

	private static function rememberEventHandlers($app_id, $event_name, $handlers)
	{
		if (!isset(self::$saved_handlers[$app_id]))
		{
			self::$saved_handlers[$app_id] = array();
		}

		if (isset(self::$saved_handlers[$app_id][$event_name]))
		{
			return;
		}

		self::$saved_handlers[$app_id][$event_name] = $handlers;
	}
}