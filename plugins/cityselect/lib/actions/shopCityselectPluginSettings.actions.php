<?php

/**
 *
 * Настройки для плагина с поддержкой загрузки файлов и мултивитринностью
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectPluginSettingsActions extends waViewActions
{

    /**
     * @var shopCityselectPlugin
     */
    public $plugin;
    public $plugin_id;
    public $access;


    public function checkRegionAction()
    {
        $data = waRequest::post();
        $model = new shopCityselectRegionModel();


        $result = array();
        $result['status'] = 'ok';

        //Проверяем есть ли такой регион
        if ($model->checkRegion($data)) {
            $result['status'] = 'fail';
            $result['error'] = 'Данный регион уже существует';
        }
        $this->view->assign('result', $result);
    }

    public function saveRegionAction()
    {
        $data = waRequest::post();

        $model = new shopCityselectRegionModel();

        $result = array();
        $result['status'] = 'ok';

        //Проверяем есть ли такой регион
        if ($model->checkRegion($data)) {
            $result['status'] = 'fail';
            $result['error'] = 'Данный регион уже существует';
        } else {
            //Сохраняем или обновляем регион
            $id = $model->save($data);

            $result['id'] = $id;

            $region = new shopCityselectRegionModel($id);
            $this->view->assign('region', $region);

            $result['html'] = $this->view->fetch($this->plugin->getPath() . '/templates/actions/settings/element/regions__item.html');

        }

        $this->view->assign('result', $result);
    }

    public function saveVariablesTypeAction()
    {
        $data = waRequest::post();
        $id = (int)$data['id'];

        $data['code'] = trim(str_replace(array("'", '"'), '', strip_tags($data['code'])));

        $result = array();
        $result['status'] = 'ok';

        $model = new shopCityselectVariablesTypeModel();

        //Проверка на наличие кода
        if ($model->checkCode($data['code'], $id)) {
            $result['status'] = 'fail';
            $result['error'] = 'Данный код (' . $data['code'] . ') уже используется';
        } else {



            if (empty($id)) {
                $id = $model->insert($data);
            } else {
                $model->updateById($id, $data);
            }

            $result['id'] = $id;

            $this->view->assign('item', $model->getById($id));
            $result['html'] = $this->view->fetch($this->plugin->getPath() . '/templates/actions/settings/element/variables_type__item.html');

        }

        $this->view->assign('result', $result);
    }

    public function editRegionAction()
    {

        $id = waRequest::request('id', '0', waRequest::TYPE_INT);

        $region = new shopCityselectRegionModel();

        if (!empty($id)) {
            $region->load($id);
        }
        $this->view->assign('region', $region);
        $this->view->assign('storefronts', shopCityselectHelper::staticGetRoutes());

    }

    public function editVariablesTypeAction()
    {
        $id = waRequest::request('id', '0', waRequest::TYPE_INT);

        if (!empty($id)) {
            $model = new shopCityselectVariablesTypeModel();
            $this->view->assign('item', $model->getById($id));
        }
    }

    public function deleteVariablesTypeAction()
    {
        $id = waRequest::request('id', '0', waRequest::TYPE_INT);

        if (!empty($id)) {
            $model = new shopCityselectVariablesTypeModel();
            $model->deleteById($id);
        }

    }

    public function deleteRegionAction()
    {
        $id = waRequest::request('id', '0', waRequest::TYPE_INT);

        if (!empty($id)) {
            $model = new shopCityselectRegionModel();
            $model->deleteById($id);
        }
    }


    /**
     * Дополнительные данные необходимые для настроек
     */
    protected function additionalSettings()
    {

        //Загрузка типов переменных
        $variables_types = $this->plugin->getVariablesTypes();
        $this->view->assign('variables_types', $variables_types);

        //Загрузка переменных
        $model = new shopCityselectRegionModel();
        $this->view->assign('regions', $model->getAll());

    }

    /**
     * Дополнительные данные необходимые для настроек Витрины
     */
    protected function additionalRouteSettings()
    {
        $region_model = new waRegionModel();
        $this->view->assign('regions', $region_model->getByCountry('rus'));
    }


    public function defaultAction()
    {
        if ($this->access) {

            $settings = $this->plugin->getSettings();
            $this->view->assign('settings', $settings);

            //Дополнительные данные
            $this->additionalSettings();
        }
    }

    /**
     * Настройка витрины
     */
    public function routeAction()
    {
        $current_route = waRequest::get('current_route', 0, waRequest::TYPE_STRING);
        $settings = $this->plugin->getSettings();

        //Сброс настроек поселения
        $reset = waRequest::get('reset', 0, waRequest::TYPE_INT);
        $is_reset = false;
        if ((!empty($reset)) && (!empty($current_route))) {

            $routes = $settings['routes'];

            if (isset($routes[$current_route])) {
                unset($routes[$current_route]);
                $is_reset = true;
            }

            $settings['routes'] = $routes;
            $this->plugin->saveSettings($settings);
        }
        $this->view->assign('is_reset', $is_reset);


        //Сброс CSS
        $reset_css = waRequest::get('reset_css', 0, waRequest::TYPE_INT);
        $is_reset_css = false;
        if (!empty($reset_css)) {

            $routes = $settings['routes'];
            if (isset($routes[$current_route])) {
                $old = $routes[$current_route];
                if (!empty($old['css'])) {
                    $path = wa()->getDataPath($old['css'], true, 'shop');
                    waFiles::delete($path, true);
                    unset($routes[$current_route]['css']);

                    $is_reset_css = true;
                }

            }
            $settings['routes'] = $routes;
            $this->plugin->saveSettings($settings);
        }
        $this->view->assign('is_reset_css', $is_reset_css);

        //Сброс JS
        $reset_js = waRequest::get('reset_js', 0, waRequest::TYPE_INT);
        $is_reset_js = false;
        if (!empty($reset_js)) {

            $routes = $settings['routes'];
            if (isset($routes[$current_route])) {
                $old = $routes[$current_route];
                if (!empty($old['js'])) {
                    $path = wa()->getDataPath($old['js'], true, 'shop');
                    waFiles::delete($path, true);
                    unset($routes[$current_route]['js']);
                }
            }
            $settings['routes'] = $routes;
            $this->plugin->saveSettings($settings);
        }
        $this->view->assign('is_reset_js', $is_reset_js);


        //Сброс шаблонов
        $reset_templates = waRequest::get('reset_templates', '', waRequest::TYPE_STRING);
        $is_reset_templates = false;
        if (!empty($reset_templates)) {

            $routes = $settings['routes'];
            if (isset($routes[$current_route])) {

                $templates = explode(",", $reset_templates);

                foreach ($templates as $template) {
                    $template = trim(str_replace('..', '', $template));

                    $routes[$current_route][$template] = '';
                    $is_reset_templates = true;
                }
            }
            $settings['routes'] = $routes;
            $this->plugin->saveSettings($settings);
        }
        $this->view->assign('is_reset_templates', $is_reset_templates);


        $default = $settings['routes'][0];

        $this->view->assign('settings', $settings);

        //Настройки текущего поселения
        $current = (!empty($settings['routes'][$current_route])) ? $settings['routes'][$current_route] : $default;

        //Шаблоны для поселения
        if (!empty($settings['templates'])) {
            $templates = explode(',', $settings['templates']);
            foreach ($templates as $template) {

                //Стандартный шаблон
                if (!empty($current[$template])) {
                    $file = wa()->getDataPath($current[$template], false, 'shop');
                }

                if ((empty($current[$template])) || (!is_readable($file))) {
                    $file = wa()->getAppPath('plugins/' . $this->plugin_id . '/templates/actions/frontend/' . $template . '.html', 'shop');
                }

                $current[$template] = file_get_contents($file);
            }
        }


        $this->view->assign('route_changes', !empty($settings['routes'][$current_route]));

        //Подгрузка CSS JS
        if (empty($current['css'])) {
            $current['css'] = wa()->getAppPath('plugins/' . $this->plugin_id . '/css/frontend.css', 'shop');
        } else {
            $current['css'] = wa()->getDataPath($current['css'], true, 'shop');
        }

        if (is_file($current['css'])) {
            $current['css'] = file_get_contents($current['css']);
        } else {
            $current['css'] = '';
        }

        if (empty($current['js'])) {
            $current['js'] = wa()->getAppPath('plugins/' . $this->plugin_id . '/js/frontend.js', 'shop');
        } else {
            $current['js'] = wa()->getDataPath($current['js'], true, 'shop');
        }

        if (is_file($current['js'])) {
            $current['js'] = file_get_contents($current['js']);
        } else {
            $current['js'] = '';
        }

        $this->view->assign('current_route', $current_route);
        $this->view->assign('current', $current);

        //Дополнительные данные
        $this->additionalRouteSettings();
    }


    /**
     * Загрузка файлов
     */
    public function uploadAction()
    {

        $file = waRequest::file('file');

        $result = array();

        try {

            if (!$file->uploaded()) {
                throw  new Exception(_wp('Файл не выбран'));
            }

            //Для обеспечения безопасности (все равно пока будут только изображения)
            $file->waImage();

            $name = md5($file->size . $file->name) . '.' . $file->extension;

            $path = wa()->getDataPath('plugins/' . $this->plugin_id . '/upload/', true, 'shop');
            $url = wa()->getDataUrl('plugins/' . $this->plugin_id . '/upload/', true, 'shop');

            if (!$file->moveTo($path, $name)) {
                throw  new Exception(_wp('Не удалось сохранить файл, проверьте права доступа'));
            }

            $result['path'] = $path . $name;
            $result['url'] = $url . $name;
            $result['status'] = 'ok';

        } catch (Exception $e) {
            $result['status'] = 'fail';
            $result['error'] = $e->getMessage();
        }

        $this->view->assign('result', $result);
    }

    /**
     * Получаем плагин и прочие данные
     */
    protected function preExecute()
    {

        $active_plugin = wa('shop')->popActivePlugin();
        $this->plugin_id = end($active_plugin);
        $this->plugin = wa('shop')->getPlugin($this->plugin_id, true);
        $this->access = true;

        $this->view->assign('access', $this->access);
        $this->view->assign('plugin', $this->plugin);
        $this->view->assign('plugin_id', $this->plugin_id);

        $this->view->assign('routes', $this->plugin->helper->getRoutes());
    }

}