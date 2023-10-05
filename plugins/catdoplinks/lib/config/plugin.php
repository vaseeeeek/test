<?php
return array (
    'name' => 'Дополнительные ссылки для категории',
    'img' => 'img/catdoplinks.png',
    'version' => '1.0.0',
    'vendor' => '1094421',
    'custom_settings' => true,
    'handlers' =>
        array (
            'backend_category_dialog' => 'backendCategoryDialog',
            'category_delete' => 'deleteCategory'
        ),
);
