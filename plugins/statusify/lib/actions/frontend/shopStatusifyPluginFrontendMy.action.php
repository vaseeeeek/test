<?php
class shopStatusifyPluginFrontendMyAction extends waViewAction
{
    public function execute()
    {   
        $user_id = wa()->getUser()->getId();
        $model = new waModel();

        $sql = "SELECT p.id as product_id, p.name as product_name, s.status_name, t.name as type_name
            FROM shop_statusify_user_product_status ups
            JOIN shop_product p ON ups.product_id = p.id
            JOIN shop_statusify_statuses s ON ups.status_id = s.status_id
            JOIN shop_type t ON p.type_id = t.id
            WHERE ups.user_id = :user_id";


        
        $statuses = $model->query($sql, ['user_id' => $user_id])->fetchAll();
        $grouped_statuses = [];
        foreach ($statuses as $status) {
            $grouped_statuses[$status['type_name']][] = [
                'product_id' => $status['product_id'],
                'product_name' => $status['product_name'],
                'status_name' => $status['status_name']
            ];
        }

        $title = 'Статусы';
        $template = 'file:'.wa()->getAppPath('plugins/statusify/templates/actions/frontend/', 'shop').'FrontendMy.html';

        
        $this->view->assign('statuses', $statuses);
        $this->view->assign('grouped_statuses', $grouped_statuses);
        $this->setThemeTemplate('index.html');
        $this->view->assign('content', $this->view->fetch($template));
    }
}
