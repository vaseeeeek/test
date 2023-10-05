<?php

/** @var shopPlugin $this */
try {
    $converters = shopCwebpPluginStack::check();
    $settings = $this->getSettings();
    $settings['converters'] = $converters;
    $this->saveSettings($settings);
} catch (Exception $e) {
}