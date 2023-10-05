<?php

/**
 * Удаление
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

$public = wa()->getDataPath('plugins/clicklite/', 'shop');
waFiles::delete($public, true);