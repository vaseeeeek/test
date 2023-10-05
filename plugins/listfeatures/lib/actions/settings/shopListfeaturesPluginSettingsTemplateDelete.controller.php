<?php

class shopListfeaturesPluginSettingsTemplateDeleteController extends waJsonController
{
    public function execute()
    {
        $template = waRequest::post('template');
        $config = shopListfeaturesPluginHelper::getAllSettlementsConfig();
        $hash_settlements = shopListfeaturesPluginHelper::getHashSettlements();

        //change punycode to UTF
        foreach ($hash_settlements as &$settlement) {
            $settlement =  shopListfeaturesPluginHelper::settlementPunycodeToUtf($settlement);
        }
        unset($settlement);

        $used_sets = array();
        foreach ($config as $settlement_hash => $sets) {
            foreach ($sets as $set => $options) {
                if (ifset($options['options']['template']) == $template) {
                    $used_sets[$hash_settlements[$settlement_hash]][] = $set;
                }
            }
        }
        if ($used_sets) {
            $message = _wp('Cannot delete this template, it is selected for the following feature sets:');
            $message .= '<ul>';
            foreach ($used_sets as $settlement => $sets) {
                $message .= '<li>'.implode(', ', $sets).' ('.$settlement.')'.'</li>';
            }
            $message .= '</ul>';
            $this->errors[] = $message;
        } else {
             shopListfeaturesPluginHelper::deleteTemplate($template);
        }
    }
}
