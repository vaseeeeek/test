<?php

class shopArrivedPluginBackendReportAction extends waViewAction {

    public function execute()
    {
        $on_page = 25;
        $helper = new shopArrivedPluginReport();
		if(waRequest::get('rating')) {
			$count = count($helper->model->getStats($helper->where));
		} else {
			$count = $helper->model->select('COUNT(*)')->where($helper->where)->fetchField();
		}
		$pages_total = ceil($count / $on_page);
        $page = waRequest::get('page', 1, waRequest::TYPE_INT);
        if($page > $pages_total) $page = $pages_total;
        if($page < 1) $page = 1;
        $offset = ($page - 1) * $on_page;
		$items = $helper->getItems($offset.",".$on_page);

		$hash = $helper->getHashParams();
		if((int)$hash['pid']>0)
			$this->view->assign('product_info', $helper->product_model->getById((int)$hash['pid']));
		$settings = include shopArrivedPlugin::path('config.php');
        $this->view->assign('settings', $settings);
        $this->view->assign('hash', $hash);
        $this->view->assign('count', $count);
        $this->view->assign('items', $items);
        $this->view->assign('pages_total', $pages_total);
		$this->view->assign('sended', waRequest::get('sended', 0, waRequest::TYPE_INT));
		$this->view->assign('url_params', $this->getUrl());
        $this->view->assign('encoding', $this->getEncList());
    }

	private function getUrl()
	{
		$get_params = waRequest::get();
		if (isset($get_params['page'])) {
			unset($get_params['page']);
		}
		$get_params['module'] = "export";
		$get_params['action'] = "csv";
		return http_build_query($get_params);
	}

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
        $popular = array_intersect(array('UTF-8', 'Windows-1251', 'ISO-8859-1',), $encoding);
        asort($encoding);
        return array_unique(array_merge($popular, $encoding));
	}

}