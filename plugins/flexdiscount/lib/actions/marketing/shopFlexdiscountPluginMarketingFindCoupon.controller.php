<?php

class shopFlexdiscountPluginMarketingFindCouponController extends waController
{
    public function execute()
    {
        $data = $this->getCoupons();

        echo(json_encode($data));
    }

    /**
     * @return array
     * @throws waException
     */
    protected function getCoupons()
    {
        $q = $this->getTerm();
        $cm = new shopFlexdiscountCouponPluginModel();
        $query = $cm->escape($q);

        $where = "WHERE (code LIKE '%{$query}%' OR comment LIKE '%{$query}%')";

        $ignore_ids = $this->getIgnoreIds();
        if (!empty($ignore_ids)) {
            $where .= " AND id NOT IN (?)";
        }

        $sql = "SELECT *
                FROM {$cm->getTableName()}
                {$where}
                ORDER BY create_datetime DESC
                LIMIT 10";

        $coupons = $cm->query($sql, [$ignore_ids])->fetchAll();
        if ($coupons) {
            $rule_ids = (new shopFlexdiscountCouponDiscountPluginModel())->getCouponDiscountIds(waUtils::getFieldValues($coupons, 'id'));

            $helper = new shopFlexdiscountHelper();
            foreach ($coupons as &$coupon) {
                if (isset($rule_ids[$coupon['id']])) {
                    $coupon['fl_id'] = $rule_ids[$coupon['id']];
                }

                $coupon = $helper->prepareCouponForJS($coupon);

                $coupon = [
                    'value' => $coupon['id'],
                    'label' => $coupon['code'],
                    'data' => $coupon,
                ];
            }
            unset($coupon);
        }

        return $coupons;
    }

    /**
     * @return string
     */
    protected function getTerm()
    {
        return trim((string) waRequest::request('term', null, waRequest::TYPE_STRING_TRIM));
    }

    protected function getIgnoreIds()
    {
        return waRequest::request('coupon_id', [], waRequest::TYPE_ARRAY_TRIM);
    }
}