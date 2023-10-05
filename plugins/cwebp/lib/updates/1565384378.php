<?php

/** @var shopCwebpPlugin $this */

$files = [
    'lib/vendors/rosell-dk/webp-convert/src-build/',
    'lib/vendors/rosell-dk/webp-convert/build-scripts/',
    'lib/vendors/rosell-dk/webp-convert/build-tests-webp-convert/',
    'lib/vendors/rosell-dk/webp-convert/build-tests-wod/',
    'lib/vendors/rosell-dk/webp-convert/docs/',
    'lib/vendors/rosell-dk/webp-convert/.php_cs.dist',
    'lib/vendors/rosell-dk/webp-convert/BACKERS.md',
    'lib/vendors/rosell-dk/webp-convert/install-gmagick-with-webp.sh',
    'lib/vendors/rosell-dk/webp-convert/install-imagemagick-with-webp.sh',
    'lib/vendors/rosell-dk/webp-convert/install-vips.sh',
    'lib/vendors/rosell-dk/webp-convert/phpdox.xml',
    'lib/vendors/rosell-dk/webp-convert/phpstan.neon',
    'lib/vendors/rosell-dk/webp-convert/phpunit.xml.dist',
    'lib/vendors/rosell-dk/webp-convert/README.md',
    'lib/vendors/rosell-dk/webp-convert/composer.json',
    'lib/vendors/rosell-dk/webp-convert/src/Helpers/Sanitize.txt',
    'lib/vendors/rosell-dk/image-mime-type-guesser/.php_cs.dist',
    'lib/vendors/rosell-dk/image-mime-type-guesser/phpunit.xml.dist',
    'lib/vendors/rosell-dk/image-mime-type-guesser/README.md',
    'lib/vendors/rosell-dk/image-mime-type-guesser/composer.json',
];

foreach ($files as $file) {
    try {
        waFiles::delete($this->path . '/' . $file);
    } catch (Exception $e) {
    }
}

$settings = $this->getSettings();
if (file_exists(__DIR__ . '/../../../../../hosting')) {
    if (!isset($settings['converters']['gd'])) {
        $settings['converters']['gd'] = ['gd'];
    }
}
if ($settings['ondemand']) {
    $settings['type'] = 'ondemand';
} else {
    $settings['type'] = 'cron';
}
$this->saveSettings($settings);