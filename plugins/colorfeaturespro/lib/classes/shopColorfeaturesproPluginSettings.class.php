<?php
class shopColorfeaturesproPluginSettings
{
    private static $_instance = null;

    private $constClass = 'shopColorfeaturesproPluginConst';
    private $namePlugin;
    private $settings = null;
    private $settingsDefault;
    private $appSettingsModel;

    public function __construct()
    {
        $constClass = new $this->constClass();
        $this->namePlugin = $constClass->getNamePlugin();
        $this->settingsDefault = $constClass->getSettingsDefault();
        $this->appSettingsModel = new waAppSettingsModel();
    }

    public static function  getInstance()
    {
        if (self::$_instance != null) {
            return self::$_instance;
        }

        return new self();
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = $this->appSettingsModel->get(array('shop', $this->namePlugin));
            foreach ($this->settings as $key => $value) {
                if (!is_numeric($value)) {
                    $json = json_decode($value, true);
                    if (is_array($json)) {
                        $this->settings[$key] = $json;
                    }
                }
            }
        }

        return $this->settings;
    }


    public function getSettingsCheck(&$settings)
    {
        foreach ($this->settingsDefault as $key => $value) {
            if (empty($settings[$key])) {
                $settings[$key] = $value;
            } elseif (is_array($value)) {
                foreach ($settings[$key] as $k => $v) {
                    if (empty($settings[$key][$k]))
                        $settings[$key][$k] = $value[$k];
                }
            }
        }
    }
}
