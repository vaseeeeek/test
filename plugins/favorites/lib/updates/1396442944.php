<?php

$model = new waAppSettingsModel();
$key = array('shop', 'favorites');

$model->set($key, 'my', preg_replace('#<a\s(->|[^>])+>(.*?)</a>#uis', '$2', $model->get($key, 'my')));
$model->set($key, 'add', preg_replace('#<a\s(->|[^>])+>(.*?)</a>#uis', '$2', $model->get($key, 'link')));
$model->del($key, 'link');

$code = $model->get($key, 'link_remove');
$code = str_replace('<a href="{$wa->getUrl(\'/frontend/my\')}favorites/">В избранном</a>', '', $code);
$code = preg_replace('#<a\s(->|[^>])+>(.*?)</a>#uis', '$2', trim($code));
if ($code == 'Убрать') {
    $code = 'Удалить из избранного';
}
$model->set($key, 'del', $code);
$model->del($key, 'link_remove');
