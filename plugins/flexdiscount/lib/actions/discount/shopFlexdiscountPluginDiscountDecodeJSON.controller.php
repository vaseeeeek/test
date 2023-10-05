<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDiscountDecodeJSONController extends waJsonController
{

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        $conditions = waRequest::post('conditions');
        $conditions = $conditions ? shopFlexdiscountConditions::decodeToArray($conditions) : array();
        $target = waRequest::post('target');
        $target = $target ? shopFlexdiscountConditions::decodeToArray($target) : array();
        $deny = (int) waRequest::post('deny', 0);
        waRequest::setParam('flexdiscount_deny', $deny);

        shopFlexdiscountConditions::init($conditions, $target);

        $this->response['conditions'] = shopFlexdiscountTypeHTMLBuilder::buildConditionHTMLTree($conditions);
        $this->response['target'] = shopFlexdiscountTypeHTMLBuilder::buildTargetHTML($target);
    }

}
