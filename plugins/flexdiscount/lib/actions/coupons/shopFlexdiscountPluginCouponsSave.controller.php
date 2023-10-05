<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginCouponsSaveController extends waJsonController
{

    // Массив существующих купонов
    private $coupons = array();
    private $coupon_model;

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        $this->coupon_model = new shopFlexdiscountCouponPluginModel();

        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $f_id = waRequest::post("f_id", 0, waRequest::TYPE_INT);
        $type = waRequest::post("type", 'coupon');
        $coupon = waRequest::post("coupon", array());

        // Расписание
        if (waRequest::post('schedule_activity')) {
            if (waRequest::post('lifetime', 'schedule') == 'schedule') {
                $schedule = waRequest::post('schedule');
                $start = $schedule['start'];
                $end = $schedule['end'];
                $start_timestamp = strtotime((int) $start['year'] . '-' . (int) $start['month'] . '-' . (int) $start['day'] . " " . (int) $start['hour'] . ":" . (int) $start['minute']);
                $end_timestamp = strtotime((int) $end['year'] . '-' . (int) $end['month'] . '-' . (int) $end['day'] . " " . (int) $end['hour'] . ":" . (int) $end['minute']);

                // Если начало пустое
                $coupon['start'] = $start_timestamp !== 943909200 ? date("Y-m-d H:i:s", $start_timestamp) : null;

                // Если конечный срок публикации меньше начального, то удаляем конечный срок
                if ($end_timestamp <= $start_timestamp) {
                    $coupon['end'] = null;
                } else {
                    $coupon['end'] = date("Y-m-d H:i:s", $end_timestamp);
                }
                $coupon['lifetime'] = '';
            } else {
                // Время жизни купона
                if (waRequest::post('days')) {
                    $days = waRequest::post('days');
                    $day = !empty($days['day']) ? (int) $days['day'] * 86400 : 0;
                    $hour = !empty($days['hour']) ? (int) $days['hour'] * 60 * 60 : 0;
                    $minute = !empty($days['minute']) ? (int) $days['minute'] * 60 : 0;
                    $coupon['lifetime'] = $day + $hour + $minute;
                    $coupon['start'] = $coupon['end'] = null;
                }
            }
        } else {
            $coupon['start'] = $coupon['end'] = $coupon['lifetime'] = '';
        }

        // Максимальное кол-во использований купонов
        $coupon['limit'] = (int) $coupon['limit'];
        $coupon['user_limit'] = (int) $coupon['user_limit'];

        // Не позволяем подменить количество использованных купонов
        if (isset($coupon['used'])) {
            unset($coupon['used']);
        }

        // Количество купонов, которые нужно создать
        $quantity = waRequest::post('quantity', 1, waRequest::TYPE_INT);
        $quantity = $quantity <= 0 ? 1 : $quantity;

        // Проверка обязательных полей
        $required = 0;
        if ($type == 'generator') {
            // Если обрабатывается генератор купонов
            if (empty($coupon['name'])) {
                $this->errors['messages']['required'] = _wp("Fill in required fields");
                $this->errors['fields'][] = 's-coupon-name';
                $required = 1;
            }
        } elseif ($type == 'coupon' && $quantity == 1) {
            // Если обрабатываются купоны
            if (empty($coupon['code'])) {
                $this->errors['messages']['required'] = _wp("Fill in required fields");
                $this->errors['fields'][] = 's-coupon-code';
                $required = 1;
            }
        }
        if (($type == 'generator' || ($type == 'coupon' && $quantity > 1)) && empty($coupon['symbols'])) {
            $this->errors['messages']['required'] = _wp("Fill in required fields");
            $this->errors['fields'][] = 's-coupon-symbols';
            $required = 1;
        }
        if ($required) {
            return;
        }

        // Обновляем данные
        if ($id) {
            if ($type == 'coupon') {
                $coupon_info = $this->coupon_model->getById($id);
                // Проверяем уникальность купона
                if ($coupon_info && isset($coupon_info['code'])) {
                    if ($coupon_info['code'] !== $coupon['code'] && $this->coupon_model->issetCoupon($coupon['code'])) {
                        $this->errors['messages']['duplicate'] = _wp("Coupon code must be unique");
                        $this->errors['fields'][] = 's-coupon-code';
                        return;
                    }
                } else {
                    $this->errors['messages']['exist'] = _wp("Coupon doesn't exist");
                    return;
                }
            }
            $this->coupon_model->updateById($id, $coupon);
        } // Создаем купоны или генератор купонов
        else {
            // Если нет ID скидки, для которой создаем купон, прерываем обработку
            if (!$f_id) {
                $this->errors['messages'][] = _wp("What the hell is going on?! Where is discount ID???");
                return;
            }
            // Префикс 
            if (isset($coupon['prefix'])) {
                $coupon['prefix'] = mb_strlen($coupon['prefix'], 'UTF-8') > 20 ? mb_substr($coupon['prefix'], 0, 20, "UTF-8") : $coupon['prefix'];
            }

            // Количество символов
            if (isset($coupon['length'])) {
                $coupon['length'] = (int) $coupon['length'];
                $coupon['length'] = $coupon['length'] <= 1 ? 2 : $coupon['length'];
            }

            // Отношение купонов к скидкам
            $coupon_discount = array();

            $coupon['create_datetime'] = date("Y-m-d H:i:s");
            if ($type == 'coupon') {
                // Создаем массив купонов
                if ($quantity > 1) {
                    for ($i = 0; $i < $quantity; $i++) {
                        $coupon['code'] = mb_substr($this->generateCoupon($coupon['symbols'], $coupon['prefix'], $coupon['length']), 0, 50, "UTF-8");
                        if (!$coupon['code']) {
                            $this->errors['messages']['quantity'] = _wp("Not enough symbols to create coupons");
                            return;
                        }
                        if ($coupon_id = $this->saveCoupon($coupon, $type)) {
                            $coupon_discount[] = array("coupon_id" => $coupon_id, "fl_id" => $f_id);
                        }
                    }
                } else {
                    // Код
                    $coupon['code'] = mb_strlen($coupon['code'], 'UTF-8') > 50 ? mb_substr($coupon['code'], 0, 50, "UTF-8") : $coupon['code'];
                    if ($this->coupon_model->issetCoupon($coupon['code'])) {
                        $this->errors['messages']['duplicate'] = _wp("Coupon code must be unique");
                        $this->errors['fields'][] = 's-coupon-code';
                        return;
                    }
                    if ($coupon_id = $this->saveCoupon($coupon, $type)) {
                        $coupon_discount[] = array("coupon_id" => $coupon_id, "fl_id" => $f_id);
                    }
                }
            } else {
                if ($coupon_id = $this->saveCoupon($coupon, $type)) {
                    $coupon_discount[] = array("coupon_id" => $coupon_id, "fl_id" => $f_id);
                }
            }
            if ($coupon_discount) {
                (new shopFlexdiscountCouponDiscountPluginModel())->multipleInsert($coupon_discount);
            }

            // Получаем данные о созданных купонах
            $discount_coupons = $this->coupon_model->getCoupons($f_id);
            $this->response = array("coupons" => $discount_coupons['coupons'], "generators" => $discount_coupons['generators']);
        }
    }

    /**
     * Generate coupon string
     *
     * @param string $symbols
     * @param string $prefix
     * @param int $length
     * @return string
     */
    private function generateCoupon($symbols, $prefix, $length)
    {
        $repeat = 0;
        do {
            if ($repeat > 30) {
                $symbols .= '!@#$%^&*(){}[]:;<>,./?';
            } elseif ($repeat > 50) {
                $symbols = false;
                break;
            }

            $symbols = $prefix . shopFlexdiscountCouponPluginModel::generateCode($symbols, $length);
            $repeat++;
        } while ($this->coupon_model->issetCoupon($symbols));

        if ($symbols === false) {
            return '';
        }

        return $symbols;
    }

    /**
     * Save coupon or coupon generator
     *
     * @param array $coupon
     * @param string $type - coupon or enerator
     * @return int
     */
    private function saveCoupon($coupon, $type)
    {
        if ($type == 'coupon') {
            if (isset($coupon['length'])) {
                unset($coupon['length']);
            }
            if (isset($coupon['prefix'])) {
                unset($coupon['prefix']);
            }
            if (isset($coupon['symbols'])) {
                unset($coupon['symbols']);
            }
        }
        $coupon['type'] = $type;
        return $this->coupon_model->save($coupon);
    }

}
