<?php
/**
 * Class shopSaleskuPluginSettings
 */
class shopSaleskuPluginSettings extends shopSaleskuPluginSettingsAbstract {
    /**
     * Глобальные настройки плагина по умолчанию
     * @var array
     */
    protected $default_settings =  array(
        // global
        'status'         => 1,
        'status_mobile'  => 1,
        'hide_stocks'    => 0,
        'hide_services'  => 0,
        'frontend_products' => 1,
        'debug'  => 0,

        // smart sku
        'smart_sku'                         => 1, // Общая настройка
        'smart_sku_replace'                 => 1, // Менять ли артикул на доступный
        'smart_sku_hide_single_feature'     => 1, // Скрывать характеристику если всего один вариант выбора
        'smart_sku_hide_multi_feature'      => 1, // Скрывать характеристику если всего один вариант выбора при наличии нескольких характеристик
        'smart_sku_hide_not_available_type' => 1, // Тип скрытия характеристик недоступного артикула
        'smart_sku_hide_non_existent_type'  => 1, // Тип скрытия характеристик несуществующего артикула

        'smart_sku_hide_style'              => 0,  // Свои классы для скрытия
        'smart_sku_class_grey'              => '', // Класс частичного скрытия
        'smart_sku_class_hide'	            => '', // Класс полного скрытия


        'related_sku' => 1, // Смена характеристик товаров в категории
        'available_sku' => 1,// автоустановка доступного артикула по умолчанию
        // Product sku image
        'sku_image'      => '1', // Замена картинки артикула
        'sku_image_size' => '200x0', // Размер картинки

        // Настройки показа
        'skus_view_type'     => 'radio', // Вид показа простого многоартикульного товара
        'style_default'      => 1, // Подключение файла стилей плагина
        'template_type'      => 'theme', // Какие файлы использовать в качестве шаблонов
        'template_options'   => '', // Код файла показа опций и характеристик
        'template_stocks'    => '', // Код файла показа наличия нак складах товара
        'template_services'  => '', // Код файла показа сервисов товара
        'template_js' => '', // Код файла Дополнительных  изменений JS,
        'template_css' => '', // Код файла Дополнительных  изменений стилей CSS,

    );

    /**
     * Переменная не используется, она показывает структуру всех натсроек других объектов настроек
     * @var array
     */
    protected $objects_default_settings = array(
        'feature_settings' => array(
            'view_type'      => 'select', // Вид показа выбираемой характеристики
            'view_name'      => '',       // Альтернативное название характеристики
            'view_name_hide' => 0         // Скрыть имя характеристики
        ),
        'product_type_settings' => array(
            'related_sku'      => '1', // настройка связанных артикулов
            'status_off'       => '0', // выключение плагина
            'status_mobile_off'=> '0', // выключение плагина для мобильников
            'hide_stocks'      => '0', // скрыть склады
            'hide_services'    => '0', // скрыть сервисы
        ),
        'related_sku_settings' =>  array(
            'related_sku' => 1
        ),
        'smart_sku_settings' =>  array(
            'smart_sku'                         => 1, // Общая настройка
            'smart_sku_replace'                 => 0, // Менять ли артикул на доступный
            'smart_sku_hide_single_feature'     => 0, // Скрывать характеристику если всего один вариант выбора
            'smart_sku_hide_multi_feature'      => 0, // Скрывать характеристику если всего один вариант выбора при наличии нескольких характеристик
            'smart_sku_hide_not_available_type' => 1, // Тип скрытия характеристик недоступного артикула
            'smart_sku_hide_non_existent_type'  => 1, // Тип скрытия характеристик несуществующего артикула

            'smart_sku_hide_style'              => 0,  // Свои классы для  скрытия
            'smart_sku_class_grey'              => '', // Класс частичного скрытия
            'smart_sku_class_hide'	            => '', // Класс полного скрытия
        ),
    );
    /**
     * Название класса модели глобальных настроек
     * @var string
     */
    protected $model_class_name = 'shopSaleskuPluginSettingsModel';
    /**
     * Хранилище дочерн6их объектов настроек , все объекты от shopSaleskuPluginSettingsAbstract
     * @var array
     */
    protected $settings_objects = array(
        'feature_settings' => null,
        'product_type_settings' => null,
        'related_sku_settings' => null,
        'smart_sku_settings' => null
    );
    /**
     *  Объект Витрины
     * @var null | shopSaleskuPluginStorefront
     */
    protected $storefront = null;

    /**
     * shopSaleskuPluginSettings constructor.
     * @param null $storefront
     */
    public function __construct($storefront = null)  {
        $this->setStorefront($storefront);
        $this->setSettings();
        $this->init();
    }

    /**
     * Метод получения переменных с подменой $this->data
     * @param $name
     * @return array
     */
    public function __get($name) {
        if($name=='data') {
            return array_merge($this->data, $this->settings_objects);
        }
        return $this->$name;
    }

    /**
     * Метод подготавливает дополнительные данные и настройки
     * @see parent init()
     */
    protected function init() {
        // Если у витрины нет своих настроек используем общие
        if(empty($this->data) &&  wa()->getEnv() == 'frontend') {
            $this->setStorefront(shopSaleskuPlugin::GENERAL_STOREFRONT);
            $this->setSettings();
        }
        // Если включен режим отладки используем шаблоны плагина
        if(isset($this->data['debug']) && $this->data['debug']=='1') {
            $this->data['template_type'] = 'plugin';
        }
        $this->setSettingsObjects();
    }

    /**
     * Инициализация дополнительных объектов настроек
     */
    protected function setSettingsObjects()  {
        $this->settings_objects['feature_settings'] = new shopSaleskuPluginFeaturesSettings($this);
        $this->settings_objects['product_type_settings'] = new shopSaleskuPluginProductTypesSettings($this);

        $this->settings_objects['related_sku_settings'] = new shopSaleskuPluginRelatedSkuSettings($this);
        $this->settings_objects['smart_sku_settings'] = new shopSaleskuPluginSmartSkuSettings($this);
    }

    /**
     * Возвращает объект настроек по его ключу
     * @param $name
     * @return mixed|null| shopSaleskuPluginSettingsAbstract
     */
    protected function getSettingsObject($name) {
        if(array_key_exists($name, $this->settings_objects)) {
            if($this->settings_objects[$name] == null) {
                $this->setSettingsObjects();
            }
            return $this->settings_objects[$name];
        }
        return null;
    }
    /**
     * Настройки характеристик на витрине
     * @return mixed|null
     */
    public function getFeaturesSettings() {
        return $this->getSettingsObject('feature_settings');
    }

    /**
     * Настройки по типу продукта
     * @return mixed|null
     */
    public function getProductTypeSettings() {
        return $this->getSettingsObject('product_type_settings');
    }

    /**
     * Настройки связанных артикуклов
     * @return mixed|null
     */
    public function getRelatedSkuSettings() {
        return $this->getSettingsObject('related_sku_settings');
    }

    /**
     * Настройки скрытия артикулов (Умные артикулы)
     * @return mixed|null
     */
    public function getSmartSkuSettings() {
        return $this->getSettingsObject('smart_sku_settings');
    }

    /**
     * Возвращает массив всех размеров эскизов изображений
     * @return mixed
     */
    public function getImageSizes() {
       return self::getAppConfig()->getImageSizes();
    }

    /**
     * @return SystemConfig|waAppConfig
     */
    public static function getAppConfig()
    {
        return  shopSaleskuPlugin::getAppConfig();
    }
    /**
     * Возвращает все поселения магазина
     * @return array
     */
    public static function getStorefronts()  {
        $routing = wa()->getRouting();
        $storefronts = array(shopSaleskuPlugin::GENERAL_STOREFRONT);
        $domains = $routing->getByApp('shop');
        // Пробегаем по доменам
        foreach ($domains as $domain => $domain_routes) {
            // Забираем все отдельные поселения
            foreach ($domain_routes as $route)  {
                $storefronts[] = $domain.'/'.$route['url'];
            }
        }
        return $storefronts;
    }

    /**
     * Устанавливает объект витрины по названию
     * @param null $storefront
     */
    protected function setStorefront($storefront = null) {
        if($storefront!==null) {
            $this->storefront = new shopSaleskuPluginStorefront($storefront);
        } elseif($this->storefront==null) {
            $routing = wa()->getRouting();
            $domain = $routing->getDomain();
            $route = $routing->getRoute();
            $storefronts = self::getStorefronts();
            $currentRouteUrl = $domain.'/'.$route['url'];
            $storefront = in_array($currentRouteUrl, $storefronts) ? $currentRouteUrl : shopSaleskuPlugin::GENERAL_STOREFRONT;
            $this->storefront = new shopSaleskuPluginStorefront($storefront);
        }
    }

    /**
     * Возвращает объект текущей витрины
     * @return shopSaleskuPluginStorefront
     */
    public function getStorefront() {
        if($this->storefront === null) {
            $this->setStorefront();
        }
        return $this->storefront;
    }

    /**
     * ВОзвращает объект шаблонов плагина
     * @return shopSaleskuPluginTemplates
     */
    public function getTemplates() {
        return new shopSaleskuPluginTemplates($this);
    }

    /**
     * Сохраняет все настройки
     * @param $data
     *  @return null
     */
    public function save($data) {
        if(is_array($data)) {
            if(isset($data['settings'])) {
                $settings_model = new shopSaleskuPluginSettingsModel();
                foreach($data['settings'] as $k => $v) {
                    $s_data = array(
                        'key' => $k,
                        'value' => $v,
                        'storefront_id' => $this->getStorefront()->getId()
                    );
                    $settings_model->insert($s_data,1);
                }
            }
            unset($data['settings']);
            // Сохраняем другие данные
            if(!empty($data)) {
                foreach ($data as $k => $v) {
                    if(isset($this->settings_objects[$k]) && is_object($this->settings_objects[$k])) {
                        $this->settings_objects[$k]->save($v);
                    }
                }
            }
        }
    }
}