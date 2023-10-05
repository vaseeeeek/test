<?php
$plugin_path = wa('shop')->getConfig()->getPluginPath('syrattach');
waFiles::delete( $plugin_path . '/css');
waFiles::delete( $plugin_path . '/README.md');
waFiles::delete( $plugin_path . '/templates/actions/backend');
