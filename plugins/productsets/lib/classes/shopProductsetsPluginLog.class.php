<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginLog
{
    private $is_debug;
    private $is_extended_log;
    private $log = '';
    private $filename = '';

    public function __construct($filename = 'productsets_debug.log')
    {
        $this->is_debug = waSystemConfig::isDebug() && waRequest::cookie('productsets_debug', waRequest::cookie('productsets_debug_extended', 0));
        $this->is_extended_log = waSystemConfig::isDebug() && waRequest::cookie('productsets_debug_extended', 0);
        $this->filename = $filename;
    }

    public function add($message)
    {
        if ($this->is_debug) {
            $this->log .= '* ' . $message . "\r\n";
        }
    }

    /**
     * @param array $result_to_save
     */
    public function save($result_to_save = [])
    {
        if ($this->is_extended_log && $result_to_save) {
           $this->add(var_export($result_to_save, true));
        }
        if ($this->log) {
            waLog::log('***************************' . "\r\n". $this->log, $this->filename);
        }
    }
}