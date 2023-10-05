<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountMarketing
{
    const TYPE_RULES = 'flexdiscount-rules';
    const TYPE_COUPONS = 'flexdiscount-coupons';
    private $plugin;
    private $path;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->path = shopFlexdiscountApp::get('system')['wa']->getAppPath('plugins/' . $this->plugin->getId(), 'shop');
    }

    /**
     * Helper for shopFlexdiscountPlugin::promoRuleTypes()
     *
     * @return array
     */
    public function getPromoRuleTypes()
    {
        return [
            self::TYPE_RULES => [
                'name' => _wp('Flexdiscount plugin rules'),
                'type' => self::TYPE_RULES,
                'css_class' => 'flexdiscount',
                'max_count' => 1
            ],
            self::TYPE_COUPONS => [
                'name' => _wp('Flexdiscount plugin coupons'),
                'type' => self::TYPE_COUPONS,
                'css_class' => 'flexdiscount-coupon',
                'max_count' => 1
            ],
        ];
    }

    /**
     * Helper for shopFlexdiscountPlugin::backendMarketingSidebar()
     *
     * @return array
     */
    public function getBackendMarketingSidebar()
    {
        $output = ['custom_html' => '<link rel="stylesheet" href="' . $this->plugin->getPluginStaticUrl() . 'css/marketing.css?v=' . $this->plugin->getVersion() . '" />'];

        return $output;
    }

    /**
     * Helper for shopFlexdiscountPlugin::backendMarketingPromo()
     *
     * @return array
     */
    public function getBackendMarketingPromo()
    {
        $output = ['bottom' => '<script src="' . $this->plugin->getPluginStaticUrl() . 'js/marketing.js?v=' . $this->plugin->getVersion() . '"></script>'];

        return $output;
    }

    /**
     * Helper for shopFlexdiscountPlugin::promoRuleEditor()
     *
     * @param array $params
     */
    public function getPromoRuleEditor(&$params)
    {
        if ($this->validateType($params['rule_type'])) {

            $view = new waSmarty3View(shopFlexdiscountApp::get('system')['wa']);
            $view->assign('plugin_url', $this->plugin->getPluginStaticUrl());
            $view->assign('_rule_type', $params['rule_type']);
            $view->assign('options', $params['options']);
            $view->assign('rule', ifset($params, 'rule', []));
            $view->assign('js_locale_strings', (new shopFlexdiscountHelper())->getJsLocaleStrings());

            $template = 'coupons.html';
            if ($params['rule_type'] == self::TYPE_RULES) {
                $view->assign('discounts', (new shopFlexdiscountPluginModel())->getDiscounts(['deny' => 0]));
                $template = 'discount_rules.html';
            } else {
                $cm = new shopFlexdiscountCouponPluginModel();
                // Сохраненные купоны
                $ids = ifempty($params, 'rule', 'rule_params', []);
                if ($ids) {
                    $coupons = $cm->getCouponsByFilter(['type' => 'coupon', 'id' => $ids]);
                    if ($coupons) {
                        foreach ($coupons as &$coupon) {
                            $coupon['status'] = shopFlexdiscountHelper::getCouponStatus($coupon);
                        }
                    }
                    $view->assign('coupons', $coupons);
                }

                $view->assign('isset_coupons', $cm->countByField('type', 'coupon'));
            }

            $params['html'] = $view->fetch($this->path . '/templates/actions/marketing/rules/' . $template);
        }
    }

    /**
     * Helper for shopFlexdiscountPlugin::promoRuleValidate()
     *
     * @param array $params
     */
    public function getPromoRuleValidate(&$params)
    {
        if ($this->validateType($params['rule']['rule_type'])) {
            if (empty($params['rule']['rule_params'])) {
                $text = _wp('Select at least one coupon');
                if ($params['rule']['rule_type'] == self::TYPE_RULES) {
                    $text = _wp('Select at least one discount rule or group');
                }
                $params['errors'][] = [
                    'id' => 'rule_error',
                    'text' => $text,
                ];
            }
        }
    }

    /**
     * Helper for shopFlexdiscountPlugin::promoWorkflowRun()
     *
     * @param array $params
     * @return array
     */
    public function getPromoWorkflowRun(&$params)
    {
        $return = [];
        foreach ($params['active_promos'] as $promo) {
            if (empty($promo['rules'])) {
                continue;
            }
            foreach ($promo['rules'] as $rule) {
                $method = "workup" . $this->camelCase($rule['rule_type']);
                if (method_exists($this, $method)) {
                    $result = $this->$method($promo, $rule);
                    if ($result) {
                        $return[$promo['id']][] = (int) $rule['id'];
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Workup rule type self::TYPE_RULES.
     * Check active rules and groups.
     *
     * @param array $promo
     * @param array $rule
     * @return bool
     */
    private function workupFlexdiscountRules($promo, $rule)
    {
        $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
        $active_rules = array_keys($workflow['active_rules']);
        if ($active_rules) {
            // Проверяем совпадение правил
            if (!empty($rule['rule_params']['rules']) && array_intersect($rule['rule_params']['rules'], $active_rules)) {
                return true;
            }
            // Проверяем совпадение правил
            if (!empty($rule['rule_params']['groups'])) {
                $rule_ids = (new shopFlexdiscountGroupDiscountPluginModel())->select('fl_id')->where('group_id IN (?)', [$rule['rule_params']['groups']])->fetchAll(null, true);
                if (array_intersect($rule_ids, $active_rules)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Workup rule type self::TYPE_COUPONS.
     * Check active rules and groups.
     *
     * @param array $promo
     * @param array $rule
     * @return bool
     */
    private function workupFlexdiscountCoupons($promo, $rule)
    {
        $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
        $active_rules = $workflow['active_rules'];
        if ($active_rules) {
            foreach ($active_rules as $active_rule) {
                if (!empty($active_rule['coupon_id']) && in_array($active_rule['coupon_id'], $rule['rule_params'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *
     * @param string $str
     * @param bool $ucfirst
     * @return string
     */
    private function camelCase($str, $ucfirst = true)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $str));
        $value = str_replace(' ', '', $value);
        return $ucfirst ? ucfirst($value) : lcfirst($value);
    }

    /**
     * Check, if request type belongs to plugin
     *
     * @param $type
     * @return bool
     */
    private function validateType($type)
    {
        if (!in_array($type, [self::TYPE_RULES, self::TYPE_COUPONS])) {
            return false;
        }
        return true;
    }
}