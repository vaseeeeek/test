<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginFrontendCouponAddController extends shopFlexdiscountPluginJsonController
{

    public function execute()
    {
        // Получаем код купона, введенный пользователем
        $post_coupon = waRequest::post("coupon", "", waRequest::TYPE_STRING_TRIM);
        // Осуществляем проверку купона
        $coupon = (new shopFlexdiscountCouponPluginModel())->getByField(array("code" => $post_coupon, "type" => "coupon"));
        if ($coupon && shopFlexdiscountHelper::getCouponStatus($coupon)) {
            // Используем купон только в том случае, если он принадлежит какому-либо правилу скидок, и данное правило активно
            $sm = new shopFlexdiscountPluginModel();
            $spm = new shopFlexdiscountParamsPluginModel();
            $scdm = new shopFlexdiscountCouponDiscountPluginModel();
            $sql = "SELECT scdm.fl_id FROM {$scdm->getTableName()} scdm "
                    . "LEFT JOIN {$spm->getTableName()} spm ON scdm.fl_id = spm.fl_id "
                    . "LEFT JOIN {$sm->getTableName()} sm ON scdm.fl_id = sm.id "
                    . "WHERE scdm.coupon_id = '" . (int) $coupon['id'] . "' AND sm.status = '1' AND ((spm.field = 'enable_coupon' AND spm.value = '1') OR (spm.field = 'rule_has_coupon' AND spm.value = '1'))";
            $discounts = $spm->query($sql)->fetchAll(null, true);
            if ($discounts) {
                // Записываем купон в сессию
                $this->getStorage()->set("flexdiscount-coupon", $coupon['code']);
            } else {
                $this->errors = 1;
            }
        } else {
            $this->errors = 1;
        }
    }

}
