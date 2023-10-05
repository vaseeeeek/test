<?php

class shopFeaturestipsPlugin extends shopPlugin
{
    public function frontendHead() {
        $text_color = $this->getSettings('text_color');
        $bg_color = $this->getSettings('bg_color');
        $qmark_size = (int) $this->getSettings('qmark-size');
        $qmark_contrast = (int) $this->getSettings('qmark-contrast');

        $style = "<style>\n";
        if (!preg_match('/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $text_color)) { $text_color = ''; }
        if (!preg_match('/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $bg_color)) { $bg_color = ''; }
        $style .= " .featurestips_wrapper .featurestips_view .tip_view_in { \n color: " . $text_color . " !important;\n background: " . $bg_color . " !important; \n } \n";
        if($qmark_contrast >= 10 && $qmark_size <=100) {
            $qmark_contrast = $qmark_contrast / 100;
            $qmark_contrast = str_replace(',', '.', $qmark_contrast);
            $style .= " .featurestips_wrapper .featurestips_icon IMG { \n opacity: " . $qmark_contrast . " !important;\n } \n";
        }
        if($qmark_size >= 10 && $qmark_size <=20) {
            $style .= " .featurestips_wrapper .featurestips_icon IMG { \n width: " . $qmark_size . "px !important;\n height: " . $qmark_size . "px !important;\n } \n";
        }
        $style .= " \n</style>";

        return $style . "\n";
    }

    public static function frontendProduct(shopProduct $product, $returnkey = false)
    {
        $view = wa()->getView();

        $all_tips = $all_tips_n = $all_features= array();
        $view->assign('featurestips', $all_tips);

        $model = new shopFeaturestipsModel();
        $do = new shopFeaturestipsPluginDo();
        $type_id = (int) $product->type_id;

        if(wa('shop')->getPlugin('featurestips')->getSettings('true_mobile') == 0 && waRequest::isMobile()) { return; }
        if($type_id <= 0) { return; }

        $all_tips = $do->GetTipsArrayWithKeyTypeId($model->getTipsByTypeId($type_id));
        $all_features = $view->getVars('features');

        if(count($all_features) <= 0) { return; }

        wa()->getResponse()->addCss('plugins/featurestips/css/featurestips.css', 'shop');
        if(wa('shop')->getPlugin('featurestips')->getSettings('action') == "hover") {
            wa()->getResponse()->addJs('plugins/featurestips/js/featurestipsHover.js', 'shop');
        } elseif(wa('shop')->getPlugin('featurestips')->getSettings('action') == "click") {
            wa()->getResponse()->addJs('plugins/featurestips/js/featurestipsClick.js', 'shop');
        } else {
            return;
        }
        if(wa('shop')->getPlugin('featurestips')->getSettings('qmark') == "light") {
            $view->assign('featurestips_tip_icon', wa()->getAppStaticUrl('shop') . "plugins/featurestips/img/qmark-light.png");
        } else {
            $view->assign('featurestips_tip_icon', wa()->getAppStaticUrl('shop') . "plugins/featurestips/img/qmark-dark.png");
        }

        foreach($all_features as $value)
        {
            if(array_key_exists($value['id'], $all_tips)) {
                $view->assign('featurestips_tip_value', $all_tips[$value["id"]]["value"]);
                $all_tips_n[$value["code"]]["value"] = $view->fetch(wa('shop')->getConfig()->getPluginPath('featurestips') . '/templates/ShowTip.html');
                $view->clearAssign('featurestips_tip_value');
            } else {
                $all_tips_n[$value["code"]]["value"] = null;
            }
        }
        unset($all_tips);
        unset($all_features);

        if($returnkey == false) {
            $view->assign('featurestips', $all_tips_n);
            return;
        } else {
            return $all_tips_n;
        }
    }

    public static function ShowFeatureTip($all_tips, $feature_code)
    {
        if(array_key_exists($feature_code, $all_tips)) {
            return $all_tips[$feature_code]["value"];
        } else {
            return;
        }
    }

    public static function ReturnFeatureTipArray(shopProduct $product)  // !!!!!!!!!!!!!!!!!!!!!!!!!!!!
    {
        $all_tips = Array();
        $all_tips = wa('shop')->getPlugin('featurestips')->frontendProduct($product, true);
        return $all_tips;
    }

    public function getSettingsHTML($params = array())
    {
        $controls = array();
        $default = array(
            'instance'            => & $this,
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'translate'           => array(&$this, '_w'),
            'control_wrapper'     => '
<div class="field-group">
    <div class="field">
        <div class="name">%s</div>
        <div class="value">%s%s</div>
    </div>
</div>
',
        );
        $options = ifempty($params['options'], array());
        unset($params['options']);
        $params = array_merge($default, $params);

        foreach ($this->getSettingsConfig() as $name => $row) {
            $row = array_merge($row, $params);
            $row['value'] = $this->getSettings($name);
            if (isset($options[$name])) {
                $row['options'] = $options[$name];
            }
            if (isset($params['value']) && isset($params['value'][$name])) {
                $row['value'] = $params['value'][$name];
            }
            if (!empty($row['control_type'])) {
                $controls[$name] = waHtmlControl::getControl($row['control_type'], "shop_featurestips[$name]", $row);
            }
        }
        return implode("\n", $controls);
    }
}