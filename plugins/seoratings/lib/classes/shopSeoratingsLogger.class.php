<?php

class shopSeoratingsLogger extends waLog
{
  const LOG_ERROR_FILENAME = 'seoratings.log';

  /**
   * Log a string message to the logfile.
   *
   * @param mixed $message
   * @param string $file
   *
   * @return bool|void
   */
  public static function log($message, $file = self::LOG_ERROR_FILENAME)
  {
    parent::log(__FILE__ . ':' . __LINE__ . ' ' . $message, $file);
  }

  /**
   * Dump variable to the logfile. Kind of print_r() function.
   *
   * @param mixed $var
   * @param string $file
   *
   * @return bool|void
   */
  public static function dump($var, $file = self::LOG_ERROR_FILENAME)
  {
    parent::dump($var, $file);
  }
}