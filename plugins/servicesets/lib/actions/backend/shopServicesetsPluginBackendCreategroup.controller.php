<?php
class shopServicesetsPluginBackendCreategroupController extends waController
{
    public function execute()
    {
        $groupName = waRequest::post('groupname');
        $model = new shopServicesetsPluginGroupsModel();
        $nameIsUsed = (bool) $model->countByField('groupname', $groupName);

        if (!$nameIsUsed) {
            $data = array(
                'groupname' => $groupName,
                'description' => '',
                'image_one' => '',
                'format_one' => '',
                'image_two' => '',
                'format_two' => '',
                'ids' => ''
            );
            $model->insert($data);
        } else {
            echo "Имя используется";
        }
        header('Location: '.stristr($_SERVER['REQUEST_URI'], '?', true) . '?action=plugins#/servicesets');
    }
}