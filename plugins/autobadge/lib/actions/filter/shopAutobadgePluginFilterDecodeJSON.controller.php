<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginFilterDecodeJSONController extends waJsonController
{

    public function execute()
    {
        $conditions = waRequest::post('conditions');
        $conditions = $conditions ? shopAutobadgeConditions::decodeToArray($conditions) : array();
        $target = waRequest::post('target');
        $target = $target ? shopAutobadgeConditions::decodeToArray($target) : array();

        shopAutobadgeConditions::init($conditions);

        $this->response['conditions'] = shopAutobadgeTypeHTMLBuilder::buildConditionHTMLTree($conditions);
        $this->response['target'] = shopAutobadgeTypeHTMLBuilder::buildTargetHTML($target);
    }

}
