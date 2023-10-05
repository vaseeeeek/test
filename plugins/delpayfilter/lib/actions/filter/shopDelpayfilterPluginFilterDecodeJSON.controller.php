<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPluginFilterDecodeJSONController extends waJsonController
{

    public function execute()
    {
        $conditions = waRequest::post('conditions');
        $conditions = $conditions ? shopDelpayfilterConditions::decode($conditions) : array();
        $target = waRequest::post('target');
        $target = $target ? shopDelpayfilterConditions::decode($target) : array();

        shopDelpayfilterConditions::init($conditions, $target);

        $this->response['conditions'] = shopDelpayfilterTypeHTMLBuilder::buildConditionHTMLTree($conditions);
        $this->response['target'] = shopDelpayfilterTypeHTMLBuilder::buildTargetHTML($target);
    }

}
