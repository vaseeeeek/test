<?php

/**
 * Class shopSaleskuPluginTemplates
 */
class shopSaleskuPluginTemplates {

    /**
     * Массив ключей и названий файлов плагина в темах дизайна
     * @var array
     */
    protected static $theme_templates = array(
        'options' => 'plugin.salesku.options.html',
        'stocks' => 'plugin.salesku.stocks.html',
        'services' => 'plugin.salesku.services.html',
        'js' => 'plugin.salesku.js',
        'css' => 'plugin.salesku.css'
    );
    /**
     *  Массив ключей и названий файлов плагина для показа на витрине
     * @var array
     */
    protected static $plugin_templates = array(
        'options' => 'product.options.html',
        'stocks' => 'product.stocks.html',
        'services' => 'product.services.html',
        'js' => 'custom.js',
        'css' => 'custom.css'
    );
    /**
     * РАспределение файлов на обработку через смарти или прямого вывода
     * @var array
     */
    protected static $templates_access = array(
        'options' => false,
        'stocks' =>  false,
        'services' => false,
        'js' => true,
        'css' => true
    );
    /**
     * Пул подготовленных шаблонов плагина
     * @var array
     */
    protected static $templates = array();
    /**
     * Объект Темы дизайна
     * @var null| waTheme
     */
    protected static $theme = null;
    /**
     * Объект глобальных настроек плагина для витрины
     * @var null| shopSaleskuPluginSettings
     */
    protected $settings = null;

    /**
     * shopSaleskuPluginTemplates constructor.
     * @param null $settings
     */
    public function __construct($settings = null)
    {
        // Пишем настройки витрины
        $this->settings = $settings;
    }

    /**
     * Возвращает подготовленый шаблон по ключу для дальнейшей обработки смарти
     * @param $name
     * @return mixed
     */
    public function getTemplate($name) {
        if(!isset(self::$templates[$name])) {
            self::$templates[$name] = '';
            if(!self::$templates_access[$name]) {
                $template_theme = self::getThemeTemplatePath(self::getTemplateFileName($name, true));
                $template_plugin = self::getPluginTemplatePath('templates/'.self::getTemplateFileName($name));
                if($this->settings['template_type'] == 'theme') {
                    if(!$template_theme) {
                        $template_theme = $template_plugin;
                    }
                    if ($template_theme) {
                        self::$templates[$name] = 'file:'.$template_theme;
                    }
                } elseif ($this->settings['template_type'] == 'custom') {
                    self::$templates[$name] =  'string: '.(string)$this->settings['template_'.$name];
                } else {
                    if ($template_plugin) {
                        self::$templates[$name] = 'file:'.$template_plugin;
                    }
                }
                if(!isset( self::$templates[$name])) {
                    self::$templates[$name] = 'string: ';
                }
            } else {
                if($this->settings['template_type'] == 'theme') {
                    $template_theme = self::getThemeTemplatePath(self::getTemplateFileName($name, true));
                    if ($template_theme) {
                        $theme = self::getTheme();
                       if($name == 'js') {
                         
                           self::$templates[$name] =  '<script type="text/javascript" src="'. $theme->getUrl().''.self::getTemplateFileName($name, true).'"></script>';
                       } elseif($name == 'css') {
                           self::$templates[$name] =  '<link href="'. $theme->getUrl().''.self::getTemplateFileName($name, true).'" type="text/css" rel="stylesheet">';
                       }
                    }
                } elseif ($this->settings['template_type'] == 'custom' && isset($this->settings['template_'.$name])) {
                    $content = trim((string)$this->settings['template_'.$name]);
                    if(!empty($content)) {
                        if($name == 'js') {
                            self::$templates[$name] =  '<script type="text/javascript"> '.(string)$this->settings['template_'.$name].' </script>';
                        } elseif($name == 'css') {
                            self::$templates[$name] =  '<style type="text/css">'.(string)$this->settings['template_'.$name].'</style>';
                        } 
                    }
                } else {
                    self::$templates[$name] = '';
                }
            }
        }
        return  self::$templates[$name];
    }

    /**
     * Возвращает HTML код ссылок на файлы коррекции работы плагина для отдельной темы дизайна
     * @return string
     */
    public function getThemeCorrection() {
        $theme = self::getTheme();
        $html = '';
        $plugin_path =  realpath(dirname(__FILE__) .'/../../').DIRECTORY_SEPARATOR;
        $js_path = 'js/themes/'.$theme['id'].'.js';
        $css_path = 'css/themes/'.$theme['id'].'.css';
        if(file_exists($plugin_path.$js_path)) {
            $html .=  '<script type="text/javascript" src="'.shopSaleskuPlugin::getUrlStatic().$js_path.'"></script>';
        }
        if(file_exists($plugin_path.$css_path)) {
            $html .= '<link href="'.shopSaleskuPlugin::getUrlStatic().$css_path.'" rel="stylesheet" type="text/css">';
        }
        return $html;

    }

    /**
     * Проверяет существуют ли файлы шаблонов плагина в темах дизайна витрины
     * @return bool
     */
    public function themesTemplatesExists() {
        $themes = $this->settings->getStorefront()->getThemes();
        if(!empty($themes)) {
            foreach ($themes as $type => $theme) {
                if ($theme) {
                    foreach ($this->getThemeTemplates() as $t_key => $filename){
                        if (!self::getThemeTemplatePath($filename,$theme)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Возвращает полный файловый путь к файлу шаблона плагина по его ключу, либо из плагина, либо из темы
     * @param $name
     * @return bool|string
     */
    public static function getTemplatePath($name) {
        $template = self::getThemeTemplatePath(self::getTemplateFileName($name, true));
        if ($template) {
           return $template;
        } else {
            $template = self::getPluginTemplatePath('templates/'.self::getTemplateFileName($name));
            if(file_exists($template)) {
                return $template;
            } else {
               return false;
            }
        }
    }

    /**
     * Возвращает абсолютный путь к файлам шаблонов плагина
     * @param string $path
     * @return string
     */
    public static function getPluginTemplatePath($path = '') {
        $path = ltrim($path,'/\\');
        return  realpath(dirname(__FILE__) .'/../../templates/').DIRECTORY_SEPARATOR.$path;
    }

    /**
     * Возвращает абсолютный путь к файлам шаблонов темы дизана
     * @param string $path
     * @param null $theme
     * @return bool|string
     */
    public static function getThemeTemplatePath($path = '', $theme = null) {
        if(!is_object($theme) || !($theme instanceof waTheme)) {
            $theme = self::getTheme();
        }
        $theme_file = $theme->getPath().DIRECTORY_SEPARATOR.$path;
        if (file_exists($theme_file)) {
            return $theme_file;
        }
        return false;
    }

    /**
     * Возвпращает объект темы дизайна для текущей витрины
     * @return null|waTheme
     */
    public static function getTheme()
    {
        if (self::$theme == null) {
            self::$theme = new waTheme(waRequest::getTheme());
        }
        return self::$theme;
    }

    /**
     * Возвращает название файла шаблона по его идентифиткатору (ключу), либо для файлов темы дизайна, либо для файлов плагина
     * @param string $name
     * @param bool $is_theme
     * @return bool|mixed
     */
    public static function getTemplateFileName($name = '', $is_theme = false) {
        $templates = $is_theme? self::getThemeTemplates() : self::$plugin_templates;
        if(isset($templates[$name])) {
            return $templates[$name];
        }
        return false;
    }

    /**
     * Возвращает все назвыания файлов шаблонов для темы дизайна
     * @return array
     */
    public static function getThemeTemplates() {
        return self::$theme_templates;
    }

    /**
     * Возвращает код файла щаблона плагина по его ключу
     * @param $name
     * @return bool|string
     */
    public static function getTemplatePluginContent($name) {
        $template_plugin = self::getPluginTemplatePath('templates/'.self::getTemplateFileName($name));
        if ($template_plugin) {
            return file_get_contents($template_plugin);
        }
        return '';
    }
}