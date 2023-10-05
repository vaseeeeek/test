<?php

/**
 * Базовый класс хеллпер настроек
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginSettings
{
    private static $_instance = null;

    private $constClass = 'shopClicklitePluginConst';
    private $namePlugin;
    private $settings = null;
    private $settingsDefault;
    private $fileForEditAndSave;
    private $appSettingsModel;

    public function __construct()
    {
        $constClass = new $this->constClass();
        $this->namePlugin = $constClass->getNamePlugin();
        $this->settingsDefault = $constClass->getSettingsDefault();
        $this->fileForEditAndSave = $constClass->getFileForEditAndSave();
        $this->appSettingsModel = new waAppSettingsModel();
    }

    private function __clone () {}
    private function __wakeup () {}

    public static function getInstance()
    {
        if (self::$_instance != null) {
            return self::$_instance;
        }

        return new self();
    }


    /**
     * Магик метод геттер php
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }


    /**
     * Получаем настройки.
     * @return array
     */
    public function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = $this->appSettingsModel->get(array('shop', $this->namePlugin));
            foreach($this->settings as $key=>$value) {
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


    /**
     * Получаем настройки и делаем проверку статуса, если удачно, то возвращаем массив настроек.
     * @throws waException
     * @return array
     */
    public function getSettingsCheckStatus()
    {
        return $this->checkStatusException($this->getSettings());
    }


    /**
     * Берем настройки и проверям, если не существует, то устнанавливаем по умолчанию
     * @param array $settings
     */
    public function getSettingsCheck(&$settings)
    {
        foreach($this->settingsDefault as $key=>$value)
        {
            if(empty($settings[$key]))
            {
                $settings[$key] = $value;
            }
            elseif(is_array($value))
            {
                foreach($settings[$key] as $k=>$v) {
                    if(empty($settings[$key][$k]))
                        $settings[$key][$k] = $value[$k];
                }
            }
        }
    }


    /**
     * Добавляем к настройкам файлы шаблонов, css, js
     * Пути файлов берем из констант и вызываем getDataPath()
     * который возвращает кеш или оригинал файла
     * @param array $settings
     */
    public function addFileSetting(&$settings)
    {
        foreach($this->fileForEditAndSave as $key=>$value)
        {
            $settings[$key] = $this->getDataPath($value);
        }
    }


    /**
     * Сохраняем файлы шаблонов, css, js пришедшие по POST запросу
     * @param array $file - array($key=>$value)
     */
    public function saveFileSettings($file)
    {
        foreach($this->fileForEditAndSave as $key=>$value)
        {
            if(!empty($file[$key]))
                $this->setDataPath($value, $file[$key]);
        }
    }


    /**
     * Возвращает сохранненй макет, если нет, то оригинал
     * @param string $name
     * @return string
     */
    protected function getDataPath($name) {
        $path = wa()->getDataPath('plugins/'.$this->namePlugin.'/'.$name, true, 'shop');
        if (!file_exists($path))
            $path = wa()->getAppPath('plugins/'.$this->namePlugin.'/'.$name, 'shop');

        return htmlspecialchars(file_get_contents($path));
    }


    /**
     * Сохраняем в файл
     * @param string $name
     * @param string $templ
     */
    protected function setDataPath($name, $templ) {
        $path = wa()->getDataPath('plugins/'.$this->namePlugin.'/'.$name, 'shop');
        file_put_contents($path, htmlspecialchars_decode($templ));
    }


    /**
     * Проверяем статус, если статус не активен то выбрасываем исключенеие и возваращаем страницу 404
     * @throws waException
     * @return array
     */
    private function checkStatusException($settings)
    {
        if(empty($settings['status']) || !$settings['status'])
        {
            throw new waException('Page not found', 404);
        }

        return $settings;
    }
}