<?php

$asm = new waAppSettingsModel();
$old_setting_value = $asm->get('shop.tageditor', 'sitemap_use_products_edit_time');
$asm->del('shop.tageditor', 'sitemap_use_products_edit_time');
$new_setting_value = (bool) (int) $old_setting_value ? 'products_update_time' : 'all';
$asm->set('shop.tageditor', 'sitemap_tag_selection', $new_setting_value);

waFiles::delete(wa()->getAppPath('plugins/tageditor/lib/config/routing.php', 'shop'));
