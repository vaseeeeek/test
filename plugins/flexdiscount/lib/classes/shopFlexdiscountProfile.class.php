<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountProfile
{

    const PLUGIN_ID = 'flexdiscount';
    const PLUGIN_ID_SHORT = 'fl';
    const PROFILE_FILE = self::PLUGIN_ID . '.profile.log';
    const DEFAULT_COOKIE = 'fl-profiling-';
    const TAB = '-';
    const SETTINGS = [
        'STATUS' => self::PLUGIN_ID . '-profile',
        'DEPTH' => self::PLUGIN_ID_SHORT . '-profile-depth',
        'REVERSE' => self::PLUGIN_ID_SHORT . '-profile-reverse',
        'PROFILE_PLUGINS' => self::PLUGIN_ID_SHORT . '-profile-plugins-ex',
        'PROFILE_TEMPLATE' => self::PLUGIN_ID_SHORT . '-profile-template-ex',
        'PROFILE_METHODS' => self::PLUGIN_ID_SHORT . '-profile-methods-ex',
        'ADD_FILENAME' => self::PLUGIN_ID_SHORT . '-profile-add-filename',
        'COOKIE' => self::PLUGIN_ID_SHORT . '-profile-cookie',
        'CACHED_PRODUCTS' => self::PLUGIN_ID_SHORT . '-cache-products',
        'SHIPPING_CONVERTDIMENSIONS' => self::PLUGIN_ID_SHORT . '-shipping-convertItemsDimensions',
        'CACHE_BACKEND_CALCULATIONS' => self::PLUGIN_ID_SHORT . '-cache-backend-calculations',
        'CALCULATE_ONLY_ACTIVE_SKU_IN_CATALOG' => self::PLUGIN_ID_SHORT . '-calculate-active-sku-catalog'
    ];
    const DEFAULT_SETTINGS = [
        'STATUS' => 0,
        'DEPTH' => 5,
        'REVERSE' => 0,
        'PROFILE_PLUGINS' => 1,
        'PROFILE_TEMPLATE' => 1,
        'PROFILE_METHODS' => 1,
        'ADD_FILENAME' => 1,
        'COOKIE' => 'igaponv-profile-cookie',
        'CACHED_PRODUCTS' => 200,
        'SHIPPING_CONVERTDIMENSIONS' => true,
        'CACHE_BACKEND_CALCULATIONS' => true,
        'CALCULATE_ONLY_ACTIVE_SKU_IN_CATALOG' => false,
    ];

    private static $hook_statistics = [];

    private $plugins;
    private $settings;
    private $backtrace;

    /**
     * Check if profiling is enabled
     *
     * @return bool
     */
    public static function isEnabled()
    {
        $app_model = new waAppSettingsModel();
        $is_enabled = $app_model->get(array('shop', self::PLUGIN_ID), self::PLUGIN_ID . '-profile');
        $profile_cookie = $app_model->get(array('shop', self::PLUGIN_ID), self::PLUGIN_ID_SHORT . '-profile-cookie');
        $cookie_enabled = $profile_cookie && waRequest::cookie($profile_cookie, '');
        return $is_enabled && $cookie_enabled;
    }

    public function __construct()
    {
        register_shutdown_function(array($this, 'createProfile'));
        $this->plugins = shopFlexdiscountApp::get('system')['config']->getPlugins();

        $app_model = new waAppSettingsModel();
        foreach (self::SETTINGS as $option_id => $key) {
            $this->settings[$key] = $app_model->get(array('shop', self::PLUGIN_ID), $key, self::DEFAULT_SETTINGS[$option_id]);
        }
    }

    /**
     * Log hook to profile. Use $point to set breakpoints inside one hook
     *
     * @param string $hook_name
     * @param string $point
     * @return array
     */
    public function log($hook_name, $point = '')
    {
        $this->backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        unset($this->backtrace[0], $this->backtrace[1]);
        $caller = $this->registerHook($hook_name, $point);
        return ['hook_name' => $hook_name, 'point' => $point, 'caller' => $caller, 'time' => microtime(true)];
    }

    /**
     * Stop executing time and log it for the call
     *
     * @param array $hook
     */
    public function stop($hook)
    {
        $time = microtime(true) - $hook['time'];

        // Общее время выполнения хука
        self::$hook_statistics[$hook['hook_name']]['time'] += $time;

        // Время выполнения точки
        if (!empty(self::$hook_statistics[$hook['hook_name']]['points'])) {
            $key = array_search($hook['point'], self::$hook_statistics[$hook['hook_name']]['points']);
            if ($key !== false) {
                self::$hook_statistics[$hook['hook_name']]['points_count'][$key]['time'] += $time;
            }
        }

        // Время выполнения вызова
        $caller_id = $hook['caller']['type'] . '-' . $hook['caller']['value'];
        if (!empty(self::$hook_statistics[$hook['hook_name']]['caller'][$caller_id])) {
            self::$hook_statistics[$hook['hook_name']]['caller'][$caller_id]['time'] += $time;
            self::$hook_statistics[$hook['hook_name']]['caller'][$caller_id]['calls'][$hook['point']][$hook['caller']['call_id']]['time'] += $time;
        }
    }

    /**
     * Register hook caller, save number of calling, save points
     *
     * @param string $hook_name
     * @param string $point
     * @return array
     */
    private function registerHook($hook_name, $point)
    {
        if (!isset(self::$hook_statistics[$hook_name])) {
            self::$hook_statistics[$hook_name] = [
                'count' => 0,
                'time' => 0,
                'caller' => [],
                'points' => [],
                'points_count' => []
            ];
        }
        // Общее количество вызовов хука
        self::$hook_statistics[$hook_name]['count']++;

        // Источник, вызвавший хук
        $caller = $this->getCaller();
        $caller['call_id'] = $this->registerCaller($caller, $hook_name, $point);

        // Вызовы хука для некоторой точки в коде
        $this->registerPoint($point, self::$hook_statistics[$hook_name]);

        return $caller;
    }

    /**
     * Register hook caller
     *
     * @param array $caller
     * @param string $hook_name
     * @param string $point
     * @return string
     */
    private function registerCaller($caller, $hook_name, $point)
    {
        $caller_id = $caller['type'] . '-' . $caller['value'];
        if (!isset(self::$hook_statistics[$hook_name]['caller'][$caller_id])) {
            self::$hook_statistics[$hook_name]['caller'][$caller_id] = [
                'count' => 0,
                'data' => $caller,
                'time' => 0,
                'calls' => [],
                'points' => [],
                'points_count' => []
            ];
        }

        // Количество вызовов
        self::$hook_statistics[$hook_name]['caller'][$caller_id]['count']++;

        // Методы, вызвавшие хук/точку
        self::$hook_statistics[$hook_name]['caller'][$caller_id]['calls'][$point][] = [
            'time' => 0,
            'methods' => $caller['methods']
        ];
        end(self::$hook_statistics[$hook_name]['caller'][$caller_id]['calls'][$point]);
        $key = key(self::$hook_statistics[$hook_name]['caller'][$caller_id]['calls'][$point]);

        // Сохраняем информацию для точки
        $this->registerPoint($point, self::$hook_statistics[$hook_name]['caller'][$caller_id]);

        return $key;
    }

    /**
     * Register point. Save number of calling for points
     *
     * @param string $point
     * @param array $destination
     */
    private function registerPoint($point, &$destination)
    {
        // Вызовы хука для некоторой точки в коде
        if ($point) {
            $key = array_search($point, $destination['points']);
            if ($key === false) {
                $destination['points'][] = $point;
                end($destination['points']);
                $key = key($destination['points']);
                $destination['points_count'][$key] = [
                    'count' => 0,
                    'time' => 0
                ];
            }
            $destination['points_count'][$key]['count']++;
        }
    }

    /**
     * Get current caller for hook
     *
     * @return array
     */
    private function getCaller()
    {
        $caller = [
            'type' => '',
            'value' => '',
            'methods' => []
        ];
        if ($this->backtrace) {
            $first_plugin = $called_method = '';
            $called_method_key = 0;
            $methods = [];
            $plugins = array_keys($this->plugins);

            // Перебираем и анализируем бектрейс
            foreach ($this->backtrace as $k => $b) {

                // Ищем плагин, который самый первый инициировал вызов хука
                $found_plugin = $this->strposa(array(ifset($b, 'file', ''), ifset($b, 'class', '')), $plugins);
                if ($found_plugin !== false) {
                    $first_plugin = ['plugin_id' => $found_plugin, 'key' => $k];
                }

                // Проверяем ближайший метод, вызвавший хук
                if ($called_method_key == $k) {
                    $called_method = $this->getBacktraceRowAsString($b);
                    $methods = $this->getBacktraceMethods('methods', $k);
                }

                if (!$called_method) {
                    $called_method_key = ifset($b, 'class', '') === 'waSystem' && ifset($b, 'function', '') === 'event' ? $k + 1 : 0;
                }
            }
            if ($first_plugin) {
                $caller['type'] = 'plugins';
                $caller['value'] = $first_plugin['plugin_id'];
                $caller['methods'] = $this->getBacktraceMethods('plugins', $first_plugin['key']);
            } else {
                $caller_template = $this->getCallerTemplate();
                if (waConfig::get('is_template') || $caller_template['value']) {
                    $caller['type'] = 'template';
                    $caller['value'] = $caller_template['value'];
                    $caller['methods'] = $this->getBacktraceMethods('template', $caller_template['key']);
                } elseif ($called_method) {
                    $caller['type'] = 'methods';
                    $caller['value'] = $called_method;
                    $caller['methods'] = $methods;
                }
            }
        }
        return $caller;
    }

    /**
     * Get caller template, if caller type is 'template'
     *
     * @return array
     */
    private function getCallerTemplate()
    {
        $template = ['value' => '', 'key' => 0];
        foreach ($this->backtrace as $k => $b) {
            $function = ifset($b, 'function', '');
            if (strpos($function, 'content_') !== false) {
                $file = basename($this->backtrace[$k - 1]['file']);
                $parts = explode('.', $file);
                if (ifset($parts, 1, '') == 'file') {
                    unset($parts[0], $parts[1]);
                } else {
                    unset($parts[0]);
                }
                $template_name = implode('.', $parts);
                $template_name = rtrim($template_name, '.php');
                $this->backtrace[$k - 1]['file'] = $template_name;
                $template['value'] = $template_name;
                $template['key'] = $k - 1;
                break;
            }
        }
        return $template;
    }

    /**
     * Get backtrace methods starts from $row_index for caller of certain $type.
     * This is extended settings
     *
     * @param string $type
     * @param int $row_index
     * @return array
     */
    private function getBacktraceMethods($type, $row_index)
    {
        $methods = [];
        // Расширенные настройки
        $type = strtoupper($type);
        if (isset($this->settings[self::SETTINGS['PROFILE_' . $type]]) && $this->settings[self::SETTINGS['PROFILE_' . $type]] && $this->settings[self::SETTINGS['DEPTH']] >= 1) {
            // Вызовы для плагинов и шаблонов выбираются к концу,
            // для методов - к началу 
            if ($type !== 'methods') {
                $limit = $row_index - $this->settings[self::SETTINGS['DEPTH']] + 1;
                $limit = $limit < 2 ? 2 : $limit;
                for ($i = $row_index; $i >= $limit; $i--) {
                    if (isset($this->backtrace[$i])) {
                        $methods[] = $this->getBacktraceRowAsString($this->backtrace[$i]);
                    }
                }
                $methods = array_reverse($methods);
            } else {
                for ($i = $row_index; $i < $row_index + $this->settings[self::SETTINGS['DEPTH']]; $i++) {
                    if (isset($this->backtrace[$i])) {
                        $methods[] = $this->getBacktraceRowAsString($this->backtrace[$i]);
                    }
                }
            }
        }
        return $methods;
    }

    /**
     * Get trace string
     *
     * @param array $row
     * @return string
     */
    private function getBacktraceRowAsString($row)
    {
        if ($this->settings[self::SETTINGS['ADD_FILENAME']]) {
            $file = basename(ifset($row, 'file', ''));
        } else {
            $file = '';
        }
        $class = ifset($row, 'class', '');
        return ($file ? $file . ':' . $row['line'] . ', ' : '') . $class . '->' . ifset($row, 'function', '') . (!$file ? ':' . $row['line'] : '()');
    }

    /**
     * Log profile. Calls at the end of all scripts
     */
    public function createProfile()
    {
        if (self::$hook_statistics) {
            $profile = '';
            $profile .= 'Page' . ': ' . shopFlexdiscountApp::get('system')['config']->getCurrentUrl() . "\r\n";
            $profile .= "----------------\r\n";

            foreach (self::$hook_statistics as $hook_name => $hook) {
                $profile .= "\r\n";

                // Общие данные по хуку
                $profile .= $this->addLogCount('Hook', $hook_name, $hook['count'], $hook['time']);

                // Список точек наблюдения
                if (!empty($hook['points'])) {
                    $profile .= "Points:\r\n";
                    $profile .= $this->addLogPoints($hook) . "\r\n";
                }

                // Данные о вызовах
                if (!empty($hook['caller'])) {
                    ksort($hook['caller']);
                    $profile .= "Callers:\r\n";
                    foreach ($hook['caller'] as $caller_id => $caller) {
                        $profile .= self::TAB;
                        $caller_name = $this->getCallerName($caller);
                        $profile .= $this->addLogCount($caller_name['title'], $caller_name['name'], $caller['count'], $caller['time']);

                        // Методы
                        if (!empty($caller['calls'])) {
                            $profile .= $this->addLogCallerCalls($caller);
                        }

                        // Точки
                        if (!empty($hook['points'])) {
                            $profile .= $this->addLogPoints($caller, self::TAB);
                        }
                    }
                }
            }

            waLog::log($profile, self::PROFILE_FILE);
        }
    }

    /**
     * Check, if extended options are enabled for caller type
     *
     * @param array $caller
     * @return bool
     */
    private function isExtendedOptionEnabled($caller)
    {
        if (!empty($caller['data']['type'])) {
            $type = strtoupper($caller['data']['type']);
            return isset($this->settings[self::SETTINGS['PROFILE_' . $type]]) && $this->settings[self::SETTINGS['PROFILE_' . $type]];
        }
        return false;
    }

    /**
     * Log caller calls
     *
     * @param array $caller
     * @param string $point
     * @return string
     */
    private function addLogCallerCalls($caller, $point = '')
    {
        $log = '';
        if ($this->isExtendedOptionEnabled($caller) && !empty($caller['calls'][$point])) {
            $log .= self::TAB . self::TAB . self::TAB;
            $log .= "Calls statistics:\r\n";
            $log .= $this->addLogCallerCallsStatistics($caller['calls'][$point]);
            $log .= "Calls:\r\n";
            foreach ($caller['calls'][$point] as $k => $call) {
                $log .= self::TAB . self::TAB . self::TAB . self::TAB;
                $log .= "Call №" . ($k + 1) . " Elapsed {$call['time']}\r\n";
                $log .= $this->addLogCallerMethods($call['methods'], self::TAB . self::TAB . self::TAB);
            }
        }
        return $log;
    }

    /**
     * Log caller calls statistics
     *
     * @param array $calls
     * @return string
     */
    private function addLogCallerCallsStatistics($calls)
    {
        $log = '';
        $statistics = [];

        if (!empty($calls['methods'])) {
            foreach ($calls['methods'] as $call) {
                foreach ($call as $method) {
                    if (!isset($statistics[$method])) {
                        $statistics[$method] = 0;
                    }
                    $statistics[$method]++;
                }
            }

            arsort($statistics);
            foreach ($statistics as $method => $count) {
                $log .= self::TAB . self::TAB . self::TAB . self::TAB . self::TAB;
                $log .= $method . " (" . _wp('%d time', '%d times', $count) . ")\r\n";
            }
        }

        return $log;
    }

    /**
     * Log caller methods
     *
     * @param array $methods
     * @param string $prefix
     * @return string
     */
    private function addLogCallerMethods($methods, $prefix = '')
    {
        $log = '';
        foreach ($methods as $m) {
            $log .= $prefix . self::TAB . self::TAB . $m . "\r\n";
        }
        return $log;
    }

    /**
     * Get title and name for caller type
     *
     * @param array $caller
     * @return array
     */
    private function getCallerName($caller)
    {
        $title = 'Unknown';
        $name = '';
        $type = $caller['data']['type'];

        switch ($type) {
            // Вызов из шаблона
            case 'template':
                $title = 'Template';
                $name = $caller['data']['value'];
                break;
            // Вызов из плагина
            case 'plugins':
                $title = 'Plugin';
                $name = $plugin_id = $caller['data']['value'];
                if (isset($this->plugins[$plugin_id])) {
                    $name = $this->plugins[$plugin_id]['name'];
                }
                break;
            // Вызов методом
            case 'methods':
                $title = 'Method';
                $name = $caller['data']['value'];
                break;
        }

        return ['title' => $title, 'name' => $name];
    }

    /**
     * Log count of calls
     *
     * @param string $title
     * @param string $name
     * @param int $count
     * @param int $time
     * @return string
     */
    private function addLogCount($title, $name, $count, $time = 0)
    {
        return $title . ' "' . $name . '" (' . _wp('%d call', '%d calls', $count) . ")" . ($time ? ' Elapsed: ' . $time : '') . "\r\n";
    }

    /**
     * Log points
     *
     * @param array $hook
     * @param string $prefix
     * @return string
     */
    private function addLogPoints($hook, $prefix = '')
    {
        $text = "";
        if (!empty($hook['points'])) {
            foreach ($hook['points'] as $point_key => $point_name) {
                $text .= $prefix . self::TAB;
                $text .= $this->addLogCount('Point', $point_name, $hook['points_count'][$point_key]['count'], $hook['points_count'][$point_key]['time']);
                $text .= $this->addLogCallerCalls($hook, $point_name);
            }
        }
        return $text;
    }

    /**
     * Works lke strpos but $needle can be an array. Return $needle
     *
     * @param array $string
     * @param array $needle
     * @param int $offset
     * @return string
     */
    private function strposa($string, $needle, $offset = 0)
    {
        if (!is_array($needle)) $needle = array($needle);
        if (!is_array($string)) $string = array($string);
        foreach ($string as $str) {
            if ($str) {
                foreach ($needle as $query) {
                    if ($query) {
                        if (strpos(strtolower($str), '/' . strtolower($query) . '/', $offset) !== false) return strtolower($query);
                    }
                }
            }
        }
        return false;
    }

}