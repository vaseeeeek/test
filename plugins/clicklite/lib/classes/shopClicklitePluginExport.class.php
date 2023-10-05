<?php

/**
 * Класс экспорта в csv
 *
 * @author Steemy, created by 29.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginExport
{
    private $namePlugin;
    private $states;

    public function __construct()
    {
        $this->namePlugin = shopClicklitePluginSettings::getInstance()->namePlugin;

        $workflow = new shopWorkflow();
        $this->states = $workflow->getAllStates();
    }

    public function execute()
    {
        $this->exportCSV();

        $view = wa()->getView();
        $view->assign('states', $this->states);
        $view->assign('encoding', $this->getEncList());
    }

    /**
     * Экспорт заказов в CSV
     */
    private function exportCSV() {
        $pluginExport = waRequest::post("plugin_export");

        if(!$pluginExport)
            return;


        $encoding = waRequest::post('encoding', 'UTF-8', waRequest::TYPE_STRING_TRIM);
        $delimiter = waRequest::post('delimiter', ';', waRequest::TYPE_STRING_TRIM);
        $status = waRequest::post('status', 'all', waRequest::TYPE_STRING_TRIM);
        $count = waRequest::post('count', '1000', waRequest::TYPE_STRING_TRIM);

        if($delimiter == 'tab')
            $delimiter = '\t';

        /**
         * Получаем список заказов
         */
        $model = new shopClicklitePluginModel();
        $orderList = $model->getOrderListForCSV($status, $count);
        $orderListAdd = array();

        foreach($orderList as $val) {
            $orderListAdd[$val['order_id']][] = $val;
        }

        $path = wa()->getConfig()->getPath('cache').'/'.$this->namePlugin.'/export_'.$this->namePlugin.'.csv';
        waFiles::delete($path);

        $writer = new shopCsvWriter($path,$delimiter,$encoding);
        $writer->setMap($this->getMapFields());

        foreach($orderListAdd as $val) {
            $writeAdd = array();
            $products = '';

            foreach ($val as $v) {
                $products .= $v['name'] . '-' . $v['price'] . 'x' . $v['quantity'] . ' | ';
            }

            $writeAdd['id'] = shopHelper::encodeOrderId($val[0]['id']);
            $writeAdd['products'] = $products;
            $writeAdd['total'] = $val[0]['total'];
            $writeAdd['currency'] = $val[0]['currency'];
            $writeAdd['rate'] = $val[0]['rate'];
            $writeAdd['discount'] = $val[0]['discount'];
            $writeAdd['tax'] = $val[0]['tax'];
            $writeAdd['shipping'] = $val[0]['shipping'];
            $writeAdd['status'] = $this->states[$val[0]['state_id']]->name;
            $writeAdd['create_datetime'] = $val[0]['create_datetime'];
            $writeAdd['update_datetime'] = $val[0]['update_datetime'];
            $writeAdd['name'] = $val[0]['contact_name'];
            $writeAdd['last_datetime'] = $val[0]['contact_create_datetime'];
            $writeAdd['contact_create_datetime'] = $val[0]['last_datetime'];
            $writeAdd['comment'] = $val[0]['comment'];

            $writer->write($writeAdd);
        }

        waFiles::readFile($path, "export_".$this->namePlugin."-".date('d.m.Y').".csv");
    }

    /**
     * Возвращает массив полей для CSV
     *
     * @return array
     */
    private function getMapFields()
    {
        $fields = array(
            "id" => "Номер заказа",
            "products" => "Товары",
            "total" => "Итого",
            "currency" => "Валюта",
            "rate" => "Курс",
            "discount" => "Скидка",
            "tax" => "Налог",
            "shipping" => "Цена доставки",
            "status" => "Статус заказа",
            "create_datetime" => "Дата создания заказа",
            "update_datetime" => "Дата обновления заказа",
            "name" => "Имя покупателя",
            "last_datetime" => "Последнее посещение контакта",
            "contact_create_datetime" => "Дата создания контакта",
            "comment" => "Комментарий",
        );

        return $fields;
    }

    /**
     * Возваращает массив кодировок
     *
     * @return array
     */
    private function getEncList()
    {
        $encoding = array_diff(mb_list_encodings(), array(
            'pass',
            'wchar',
            'byte2be',
            'byte2le',
            'byte4be',
            'byte4le',
            'BASE64',
            'UUENCODE',
            'HTML-ENTITIES',
            'Quoted-Printable',
            '7bit',
            '8bit',
            'auto',
        ));

        $popular = array_intersect(array('UTF-8', 'Windows-1251', 'ISO-8859-1'), $encoding);
        asort($encoding);

        return array_unique(array_merge($popular, $encoding));
    }
}