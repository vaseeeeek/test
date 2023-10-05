<?php

/**
 * Объект витрины
 * Class shopSaleskuPluginStorefront
 */
class shopSaleskuPluginStorefront {
    /**
     * Название(URL) витрины
     * @var string
     */
    protected $name = 'general';
    /**
     * Идентификатор витрины в таблице витрин плагина
     * @var int
     */
    protected $id = 0;

    /**
     * shopSaleskuPluginStorefront constructor.
     * @param null $storefront - Название(URL) витрины
     */
    public function __construct($storefront = null)  {
        $this->setStorefront($storefront);
    }

    /**
     * Проверка и установка данных витрины
     * @param null $storefront
     */
    protected function setStorefront($storefront = null) {
        $model = new shopSaleskuPluginStorefrontModel();
        $storefront_data = $model->getByStorefrontName($storefront);
        if(!empty($storefront_data)) {
            $this->name = $storefront_data['name'];
            $this->id = $storefront_data['id'];
        } else {
            $id = $model->insert(array('name' => $storefront));
            if(!empty($id)) {
                $this->name = $storefront;
                $this->id = $id;
            }
        }
    }

    /**
     * Возвращает все темы дизайна, используемые на втирине
     * @return array
     */
    public function getThemes() {
            $storefront_data = self::splitUrl($this->getName());
            if($storefront_data) {
                $routing = wa()->getRouting()->getRoutes($storefront_data['domain']);
                foreach ($routing as $route) {
                    if($route['app'] == shopSaleskuPlugin::APP && $route['url'] == ltrim($storefront_data['url'], '/\\'))  {
                        $theme = new waTheme($route['theme'], shopSaleskuPlugin::APP);
                        $theme_mobile = ($route['theme'] == $route['theme_mobile'])? false : new waTheme($route['theme_mobile'], shopSaleskuPlugin::APP);
                        return array(
                            'theme' => $theme,
                            'theme_mobile' => $theme_mobile,
                        );
                    }
                }
            }
        return array();
    }

    /**
     * Разбирает адрес названия витрины на составляющие
     * @param $url
     * @return array|bool
     */
    public static function splitUrl($url)
    {
        if(preg_match('@^(?:http://|https://)?([^/]+)([\/].*)?@i', mb_strtolower($url), $url_arr)) {
            if(count($url_arr)==3) {
                return  array(
                    'domain' => $url_arr[1],
                    'url' => $url_arr[2]
                );
            }
        }
        return false;
    }

    /**
     *  ВОзвращает идентификатор витрины
     * @return int
     */
    public function getId(){
        return $this->id;
    }

    /**
     * Возвращает md5 код названия витрины
     * @return string
     */
    public function getCode() {
        return md5($this->name);
    }

    /**
     * Возвращает название витрины
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * При приведении класса к строке будет выведено название витрины
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }
}