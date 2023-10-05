<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Сбрасываем кеш в приложении Ускорение сайта

waFiles::delete(shopFlexdiscountApp::get('system')['wa']->getDataPath('', true, 'mini'));
