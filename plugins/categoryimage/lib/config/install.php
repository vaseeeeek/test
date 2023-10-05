<?php

$model = new waModel();
try {
    $model->query("SELECT image FROM shop_category WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_category ADD image VARCHAR(5) NULL DEFAULT NULL");
}

$path = wa()->getDataPath('categories', true, 'shop');
waFiles::write($path.'/thumb.php', '<?php
$file = realpath(dirname(__FILE__)."/../../../../")."/wa-apps/shop/plugins/categoryimage/lib/thumb.php";

if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
');

waFiles::write($path.'/.htaccess', '
<ifModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)$ thumb.php [L,QSA]
</ifModule>
');