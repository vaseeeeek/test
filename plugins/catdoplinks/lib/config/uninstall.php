<?php
/**
 * Created by PhpStorm
 * User: rmjv
 * Date: 20.12.2019
 * Time: 15:56
 */

$path = wa()->getDataPath("catdoplinks/", true, 'shop');

waFiles::delete($path, true);