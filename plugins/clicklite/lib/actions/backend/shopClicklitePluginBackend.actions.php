<?php

/**
 * Класс бекенд, показывающий списко заявок
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginBackendActions extends waViewActions
{
    /**
     * @var $_STATUS_TITLE_BACKEND - статусы в бекенде
     */
    static $_STATUS_TITLE_BACKEND = array(
        'active' => 'Все заказы',
        'export' => 'Экспорт заказов в CSV полученные через плагин',
    );

    public function defaultAction()
    {
        if(!$this->getRights('clicklite_list'))
            throw new waRightsException();

        $this->setLayout(new shopClicklitePluginBackendLayout());

        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $pluginSetting->getSettingsCheckStatus();

        $this->setTemplate('plugins/clicklite/templates/actions/backend/Backendlist.html');


        /*
         * - Проверка активной вкладки и задание title
         */
        $status = waRequest::get("status", "active");

        $statusTitle = self::$_STATUS_TITLE_BACKEND;

        if(!empty($statusTitle[$status]))
            $title = $statusTitle[$status];


        $model = new shopClicklitePluginModel();

        $count = $model->countAll();
        $list = '';

        if($status == 'active')
        {
            /*
             * - Получения списка заказов
             */
            $limit = 15;

            $page = waRequest::get('page', 1, 'int');
            $page = $page > 1 ? $page : 1;

            $offset = ($page - 1) * $limit;

            $list = $model->getOrderList($offset, $limit, $status);
            $pagesCount = ceil((float) $count / $limit);

            $this->view->assign('pagesCount', $pagesCount);
        }

        $diagramm = array(
            'Стандартная корзина - ' => $model->getCountNotLite(),
            '1 клик (lite) удаленные - ' => $model->getCountLite('delete'),
            '1 клик (lite) выполненные - ' => $model->getCountLite('completed'),
            '1 клик (lite) активные - ' => $model->getCountLite(),
        );

        foreach($diagramm as $n=>$v)
        {
            $pie_data[] = array(
                'label' => $n . $v,
                'value' => $v,
            );
        }

        $workflow = new shopWorkflow();
        $states = $workflow->getAllStates();

        if(!empty($list)) {
            foreach ($list as $k => $l) {
                $list[$k]['order_id'] = shopHelper::encodeOrderId($l['order_id']);
                $list[$k]['state_id'] = $states[$l['state_id']]->name;
            };
        }

        $this->view->assign('list', $list);
        $this->view->assign('pie_data', $pie_data);
        $this->view->assign('count', $count);
        $this->view->assign('app_url', wa()->getAppUrl('shop'));
        $this->view->assign('status', $status);
        $this->view->assign('title', $title);


        /**
         * Экспорт в csv
         */
        $shopClicklitePluginExport = new shopClicklitePluginExport();
        $shopClicklitePluginExport->execute();

        $this->getResponse()->setTitle($title . '. Купить в один клик (lite)');
    }

}