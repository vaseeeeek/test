<?php

require_once __DIR__ . '/../vendors/autoload.php';

use WebPConvert\Loggers\BufferLogger;

class shopCwebpPluginConvert
{
    const WPC_API_KEY = '21232f297a57a5a743894a0e4a801fc3';
    const WPC_API_URL = 'https://europe-west2-wasyst-928da.cloudfunctions.net/convert';

    private $source;
    private $destination;
    private $logger;
    private $settings;
    private $options;
    private $mode;

    public function __construct($source, $destination, $mode = null)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->logger = new BufferLogger();
        $this->mode = $mode;
    }

    public function convert()
    {
        try {
            if (!$this->options) {
                $this->setOptions();
            }
            if (!class_exists('shopCwebpPluginStack')) {
                require_once __DIR__ . '/shopCwebpPluginStack.class.php';
            }
            shopCwebpPluginStack::convert($this->source, $this->destination, $this->options, $this->logger);
            if ($this->settings['log']) {
                $this->logger->log($this->mode . ': ' . $this->destination);
                $this->saveLog();
            }
            if (file_exists($this->destination)) {
                return true;
            }
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }
        return false;
    }

    public function convertAndRead()
    {
        if ($this->convert()) {
            header('Content-type: image/webp');
            header('Content-Length: ' . filesize($this->destination));
            header('Last-Modified: ' . filemtime($this->destination));
            @readfile($this->destination);
        } else {
            header("HTTP/1.0 404 Not Found");
        }
        exit;
    }

    /**
     * @param $custom array
     * @throws Exception
     */
    public function setOptions($custom = array())
    {
        if (class_exists('shopCwebpPlugin')) {
            $settings = shopCwebpPlugin::getPlugin()->getSettings();
        } elseif (file_exists(__DIR__ . '/../../../../../../wa-data/protected/shop/plugins/cwebp/settings.json')) {
            $settings = json_decode(file_get_contents(__DIR__ . '/../../../../../../wa-data/protected/shop/plugins/cwebp/settings.json'), true);
        } else {
            throw new Exception('Settings not found');
        }
        $this->settings = $settings;

        $settings['quality'] = $settings['quality'] ? (int)$settings['quality'] : 'auto';

        $options = [
            'converters' => empty($settings['converters']) ? [] : array_keys($settings['converters']),
            'quality' => $settings['quality'],
            'alpha-quality' => 100,
            'encoding' => 'lossy',
            'converter-options' => [
                'wpc' => [
                    'crypt-api-key-in-transfer' => false,
                    'api-key' => self::WPC_API_KEY,
                    'api-url' => self::WPC_API_URL,
                ],
            ],
            '_skip_input_check' => true
        ];

        foreach ($custom as $o => $v) {
            unset($options[$o]);
            $options[$o] = $v;
        }

        $this->options = $options;
    }

    private function saveLog()
    {
        $text = '';
        foreach ($this->logger->entries as $entry) {
            if ($entry == '') {  // empty string means new line
                if (substr($text, -2) != '.' . PHP_EOL) {
                    $text .= PHP_EOL;
                }
            } else {
                list($msg, $style) = $entry;
                $text .= $msg;
            }
        }
        $this->log($text);
    }

    public static function getAllConverters()
    {
        return [
            [
                'value' => 'vips',
                'title' => 'vips',
                'description' => _wp('PHP extension') . ' <a href="https://github.com/libvips/php-vips-ext">vips</a>',
            ],
            [
                'value' => 'imagick',
                'title' => 'imagick',
                'description' => _wp('PHP extension') . ' <a href="https://github.com/Imagick/imagick">imagick</a>, ' . _wp('if compiled with webp support'),
            ],
            [
                'value' => 'gmagick',
                'title' => 'gmagick',
                'description' => _wp('PHP extension') . ' <a href="https://www.php.net/manual/en/book.gmagick.php">gmagick</a>, ' . _wp('if compiled with webp support'),
            ],
            [
                'value' => 'gd',
                'title' => 'gd',
                'description' => _wp('PHP extension') . ' <a href="https://www.php.net/manual/en/book.image.php">GD</a>, ' . _wp('if compiled with webp support'),
            ],
            [
                'value' => 'cwebp',
                'title' => 'cwebp',
                'description' => _wp('Utility') . ' <a href="https://developers.google.com/speed/webp/docs/cwebp">cwebp</a> (apt-get install webp || yum install libwebp-tools)',
            ],
            [
                'value' => 'imagemagick',
                'title' => 'imagemagick',
                'description' => _wp('Utility') . ' <a href="https://imagemagick.org/index.php">imagemagick</a> ' . _wp('called from command line'),
            ],
            [
                'value' => 'graphicsmagick',
                'title' => 'graphicsmagick',
                'description' => _wp('Utility') . ' <a href="http://www.graphicsmagick.org/">graphicsmagick</a> ' . _wp('called from command line'),
            ],
            [
                'value' => 'ffmpeg',
                'title' => 'ffmpeg',
                'description' => _wp('Utility') . ' <a href="https://ffmpeg.org">ffmpeg</a> ' . _wp('called from command line'),
            ],
            [
                'value' => 'wpc',
                'title' => 'wpc',
                'description' => _wp('Remote webp conversion service, works anyway, slowest'),
            ],
        ];
    }

    public static function getAvailableConverters()
    {
        $source = wa()->getAppPath('plugins/cwebp/img/cwebp.jpg', 'shop');
        $destination = wa()->getTempPath('cwebp/cwebp.webp', 'shop');
        $converters = self::getAllConverters();
        foreach ($converters as $k => $converter) {
            $c = new shopCwebpPluginConvert($source, $destination);
            $c->setOptions(['converters' => [$converter['value']]]);
            if (!$c->convert()) {
                $converters[$k]['disabled'] = true;
            }
        }
        return $converters;
    }

    public function log($message)
    {
        $path = __DIR__ . '/../../../../../../wa-log/cwebp.log';
        file_put_contents($path, PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.$message.PHP_EOL, FILE_APPEND);
    }
}
