<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginCouponsImexportController extends waLongActionController
{

    private $csv;
    private $coupon_model;
    private $done = false;

    public function execute()
    {
        try {
            parent::execute();
        } catch (waException $ex) {
            if ($ex->getCode() == '302') {
                echo json_encode(array('warning' => $ex->getMessage()));
            } else {
                echo json_encode(array('error' => $ex->getMessage()));
            }
        }
    }

    protected function finish($filename)
    {
        $this->info();
        if ($this->getRequest()->post('cleanup')) {
            return true;
        }
        return false;
    }

    protected function init()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException('Access denied');
        }

        $this->data['type'] = waRequest::post('type');

        if ($this->data['type'] == 'export') {
            $this->initExport();
        } else {
            $this->initImport();
        }

        $this->data['timestamp'] = time();
        $this->data['offset'] = 0;
    }

    private function initExport()
    {
        $encoding = 'utf-8';

        $params = waRequest::post('params', array());
        $all = !empty($params['all']) ? 1 : 0;
        $coupon_ids = !empty($params['coupon_ids']) ? $params['coupon_ids'] : array();
        $fl_id = (int) waRequest::post('fl_id', 0);

        if (!$fl_id && !$all) {
            throw new waException(_wp('Check discount rule or select all coupons.'));
        }
        if (!$all && !$coupon_ids) {
            throw new waException(_wp('Select coupons'));
        }

        $coupon_model = new shopFlexdiscountCouponPluginModel();
        $coupon_discount_model = new shopFlexdiscountCouponDiscountPluginModel();

        $where = $join = "";
        // Поиск по коду 
        if (!$all && !$coupon_ids && !empty($params['params']['search'])) {
            $q = $coupon_model->escape($params['params']['search'], 'like');
            $where .= " AND c.code LIKE '$q%'";
        }

        // Отображать все купоны
        if (!waRequest::post('show_all', 0) && $fl_id) {
            $where .= " AND cd.fl_id = '" . $fl_id . "'";
            $join .= " LEFT JOIN {$coupon_discount_model->getTableName()} cd ON cd.coupon_id = c.id ";
        }

        // Выборка определенных купонов
        if (!$all && $coupon_ids) {
            $where .= " AND c.id IN ('" . (implode("','", $coupon_model->escape($coupon_ids, 'int'))) . "')";
        }

        $where .= " AND c.type='coupon'";
        $sql = "SELECT COUNT(*) FROM {$coupon_model->getTableName()} c " . $join . " WHERE 1" . $where;

        // Общее количество записей
        $this->data['total_count'] = $coupon_model->query($sql)->fetchField();

        // Название файла
        $name = sprintf('export_coupons_%s_%s.csv', date('Y-m-d-H-i-s'), strtolower($encoding));
        $file = shopFlexdiscountApp::get('system')['wa']->getTempPath('flexdiscount/csv/export/' . $name);
        $this->csv = new shopFlexdiscountCsv($file, ';', $encoding);
        $map = $this->getMapFields();
        // Устанавливаем заголовки
        $this->csv->setMap($map);

        $this->data['file'] = serialize($this->csv);
        $this->data['filename'] = $this->csv->file();

        $this->data['join'] = $join;
        $this->data['where'] = $where;
    }

    private function initImport()
    {
        $fl_id = (int) waRequest::post('fl_id', 0);
        $file = waRequest::post('file');
        $delimiter = waRequest::post('delimiter', 'semicolon');

        $delimiters = array('semicolon' => ';', 'comma' => ',', 'tab' => "\t");
        if (!isset($delimiters[$delimiter])) {
            $delimiter = 'semicolon';
        }

        if (!$fl_id && $file) {
            throw new waException(_wp('Not enough parameters for import'));
        }

        $encoding = waRequest::post('encoding', 'UTF-8');
        $file_path = shopFlexdiscountApp::get('system')['wa']->getTempPath('flexdiscount/csv/import/' . $file);
        $this->csv = new shopFlexdiscountCsv($file_path, $delimiters[$delimiter], $encoding, 'read');

        $this->data['file'] = serialize($this->csv);
        $this->data['fl_id'] = $fl_id;
        $this->data['imported'] = 0;
        $this->data['file_path'] = $file_path;
        $this->data['total_count'] = $this->csv->getFileSize();
    }

    protected function isDone()
    {
        return ($this->data['offset'] >= $this->data['total_count'] || $this->done);
    }

    protected function restore()
    {
        $this->csv = unserialize($this->data['file']);
        $this->coupon_model = new shopFlexdiscountCouponPluginModel();
    }

    protected function step()
    {
        if ($this->data['type'] == 'export') {
            $this->stepExport();
        } else {
            $this->stepImport();
        }
    }

    private function stepExport()
    {
        try {
            $sql = "SELECT c.* FROM {$this->coupon_model->getTableName()} c " . $this->data['join'];
            $sql .= " WHERE 1" . $this->data['where'];
            $sql .= " GROUP BY c.id";
            $sql .= " ORDER BY c.code ASC";
            $sql .= " LIMIT " . $this->data['offset'] . "," . 50;
            $coupons = $this->coupon_model->query($sql)->fetchAll();

            if (!empty($coupons)) {
                foreach ($coupons as $coupon) {
                    $data = array(
                        'f_code' => $coupon['code'],
                        'f_start' => $coupon['start'],
                        'f_end' => $coupon['end'],
                        'f_comment' => $coupon['comment'],
                        'f_limit' => $coupon['limit'],
                        'f_used' => $coupon['used'],
                        'f_create_datetime' => $coupon['create_datetime'],
                    );
                    $this->csv->write($data);
                    $this->data['offset']++;
                }
                $this->data['file'] = serialize($this->csv);
            }
        } catch (Exception $ex) {
            sleep(5);
            $this->error($ex->getMessage() . "\n" . $ex->getTraceAsString());
        }
    }

    private function stepImport()
    {
        try {
            $data = $this->csv->read(200);
            if ($data) {
                // Отбираем все корректные купоны
                $coupons = array();
                foreach ($data as $d) {
                    if (is_array($d) && count($d) == 5) {
                        $d[0] = trim(preg_replace('/\s\s+/', ' ', $d[0]));
                        if ($d[0]) {
                            $coupons["" . $d[0]] = $d;
                        }
                    }
                }
                if ($coupons) {
                    // Получаем купоны, которых еще нет в базе
                    $import_coupons = $this->coupon_model->filterByNew($coupons);
                    if ($import_coupons) {
                        $coupon_discount = array();
                        foreach ($import_coupons as $code => $d) {
                            $new_coupon = array(
                                "code" => $code,
                                "start" => $d[1] ? (date('Y-m-d H:i:s', strtotime($d[1]))) : null,
                                "end" => $d[2] ? (date('Y-m-d H:i:s', strtotime($d[2]))) : null,
                                "comment" => $d[3],
                                "limit" => $d[4],
                                "create_datetime" => date("Y-m-d H:i:s"),
                            );
                            if ($coupon_id = $this->coupon_model->save($new_coupon)) {
                                $coupon_discount[] = array("coupon_id" => $coupon_id, "fl_id" => $this->data['fl_id']);
                            }
                            $this->data['imported']++;
                        }
                        if ($coupon_discount) {
                            (new shopFlexdiscountCouponDiscountPluginModel())->multipleInsert($coupon_discount);
                        }
                    }
                }

                $this->data['offset'] = $this->csv->current();
                $this->data['file'] = serialize($this->csv);
            } elseif ($data === false) {
                $this->done = true;
            }
        } catch (Exception $ex) {
            sleep(5);
            $this->error($ex->getMessage() . "\n" . $ex->getTraceAsString());
        }
    }

    protected function info()
    {
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time' => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId' => $this->processId,
            'progress' => 0.0,
            'ready' => $this->isDone(),
            'offset' => $this->data['offset'],
            'total_count' => $this->data['total_count'],
            'error_message' => isset($this->data['error_message']) ? $this->data['error_message'] : "",
        );
        $response['progress'] = ($this->data['offset'] / $this->data['total_count']) * 100;
        $response['progress'] = sprintf('%0.3f%%', $response['progress']);

        if ($this->getRequest()->post('cleanup')) {
            $response['report'] = $this->report();
        }

        echo json_encode($response);
    }

    protected function report()
    {
        $report = "";
        if ($this->data['type'] == 'export') {
            $report .= $this->reportExport();
        } else {
            $report .= $this->reportImport();
        }
        $report .= '<br><br><div class="align-center"><a href="javascript:void(0)" class="button yellow close" onclick=\'$("#longaction-dialog-clone").trigger("close")\'>' . _w('Close') . '</a></div>';
        return $report;
    }

    private function reportExport()
    {
        $file = $this->data['filename'];
        $report = '<div style="margin-top: 50px;" class="align-center export-report">' . _wp('Download:') . ' <a href="?plugin=flexdiscount&module=coupons&action=exportDownload&file=' . basename($file) . '"><i class="icon16 ss excel"></i> ' . basename($file) . '</a></div>';
        return $report;
    }

    private function reportImport()
    {
        waFiles::delete($this->data['file_path'], true);
        $report = '<div style="margin-top: 50px; font-size: 18px" class="align-center">' . _wp('Successfully imported coupons:') . ' <span class="coupon-item">' . $this->data['imported'] . '</span></div>';
        return $report;
    }

    private function getMapFields()
    {
        $mapped = array(
            'f_code' => _wp('Coupon code'),
            'f_start' => _wp('Start'),
            'f_end' => _wp('End'),
            'f_comment' => _wp('Comment'),
            'f_limit' => _wp('Limit'),
            'f_used' => _wp('Used'),
            'f_create_datetime' => _wp('Create datetime'),
        );

        if ($this->data['type'] == 'import') {
            unset($mapped['f_used'], $mapped['f_create_datetime']);
        }

        return $mapped;
    }

}
