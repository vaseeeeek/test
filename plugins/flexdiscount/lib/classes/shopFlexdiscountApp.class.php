<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

use Igaponov\flexdiscount\Helper;
use Igaponov\flexdiscount\Functions;
use Igaponov\flexdiscount\Order;
use Igaponov\flexdiscount\Contact;

class shopFlexdiscountApp
{
    private static $global_store = [];

    public static function get($block, $default = null)
    {
        $instance = self::getInstance();

        if (strpos($block, '.') !== false) {
            $parts = explode('.', $block);
            $block = $parts[0];
            $value = $parts[1];
            if (isset($parts[2])) {
                $value2 = $parts[2];
            }
        }

        if (!isset(self::$global_store[$block])) {
            self::$global_store[$block] = $instance->register($block, $default);
        }

        if (isset($value2)) {
            return ifset(self::$global_store[$block], $value, $value2, $default);
        } else if (isset($value)) {
            return ifset(self::$global_store[$block], $value, $default);
        }

        return self::$global_store[$block];
    }

    public static function getHelper()
    {
        static $helper;
        require_once self::get('system')['wa']->getAppPath('plugins/flexdiscount/lib/classes/Helper.php');
        if ($helper === null) {
            $helper = new Helper();
        }
        return $helper;
    }

    public static function getOrder()
    {
        static $order;
        require_once self::get('system')['wa']->getAppPath('plugins/flexdiscount/lib/classes/Order.php');
        if ($order === null) {
            $order = new Order();
        }
        return $order;
    }

    public static function getFunction()
    {
        static $functions;
        require_once self::get('system')['wa']->getAppPath('plugins/flexdiscount/lib/classes/Functions.php');
        if ($functions === null) {
            $functions = new Functions();
        }
        return $functions;
    }

    public static function getContact()
    {
        static $contact;
        require_once self::get('system')['wa']->getAppPath('plugins/flexdiscount/lib/classes/Contact.php');
        if ($contact === null) {
            $contact = new Contact();
        }
        return $contact;
    }

    /**
     * Set value to global store
     *
     * @param string $block
     * @param mixed $value
     * @param bool $ignore_register_initialization
     * @return mixed|null
     */
    public function set($block, $value, $ignore_register_initialization = false)
    {
        if (strpos($block, '.') !== false) {
            $parts = explode('.', $block);
            $block = $parts[0];
            $index = $parts[1];
            if (isset($parts[2])) {
                $index2 = $parts[2];
            }
        }
        if (!isset(self::$global_store[$block])) {
            self::$global_store[$block] = $this->register($block, null, $ignore_register_initialization);
        }
        if (isset($index) && is_array(self::$global_store[$block])) {
            if (isset($index2)) {
                if (!isset(self::$global_store[$block][$index])) {
                    self::$global_store[$block][$index] = [];
                }
                self::$global_store[$block][$index][$index2] = $value;
            } else {
                self::$global_store[$block][$index] = $value;
            }

            return $value;
        } elseif (!isset($index)) {
            self::$global_store[$block] = $value;
            return self::$global_store[$block];
        } else {
            return null;
        }
    }

    private function register($block, $default = null, $ignore_register_initialization = false)
    {
        $method_name = 'register' . ucfirst($block);
        if (method_exists($this, $method_name)) {
            return $this->$method_name($ignore_register_initialization);
        } else {
            return $default;
        }
    }

    private function registerSystem()
    {
        $data = [
            'wa' => wa('shop', true),
        ];
        $data['config'] = $data['wa']->getConfig();
        $data['current_currency'] = $data['config']->getCurrency(false);
        $data['primary_currency'] = $data['config']->getCurrency(true);
        return $data;
    }

    private function registerOrder($ignore_register_initialization = false)
    {
        return [
            'full' => !$ignore_register_initialization ? self::getOrder()->updateOrder() : [],
            'info' => [],
            'currency' => ''
        ];
    }

    private function registerDebug()
    {
        return [
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
    }

    private function registerSettings()
    {
        return (new \shopFlexdiscountSettingsPluginModel())->getSettings();
    }

    private function registerEnv()
    {
        return [
            'is_frontend' => self::get('system')['wa']->getEnv() == 'frontend',
            'is_cli' => PHP_SAPI === 'cli',
            'is_importexport' => self::getHelper()->isImportExport(),
            'is_onestep_checkout' => self::getHelper()->isOnestepCheckout(),
        ];
    }

    private function registerCore()
    {
        return [
            'discounts' => self::getHelper()->getFrontendDiscounts(),
            'calculate-active-sku-catalog' => (new waAppSettingsModel())->get(array('shop', 'flexdiscount'), shopFlexdiscountProfile::SETTINGS['CALCULATE_ONLY_ACTIVE_SKU_IN_CATALOG'], shopFlexdiscountProfile::DEFAULT_SETTINGS['CALCULATE_ONLY_ACTIVE_SKU_IN_CATALOG']),
            'workflow' => []
        ];
    }

    private function registerRuntime()
    {
        return [];
    }

    private static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

}