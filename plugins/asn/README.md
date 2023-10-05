# Простой автоматический генератор имен артикулов для товаров Shop-script 6

Плагин генерирует артикул из id записи в БД при сохранении товара. Артикул генерируется, если не указан вручную

## Установка

1. Скопируйте в каталог *wa-apps/shop/plugins/* в папку **asn/**
2. Добавьте запись в конфигурационный файл фреймворка wa-config/apps/shop/plugins.php: `'asn' => true`

Пример содержимого файла plugins.php:

    return array (
        'asn' => true
    );

После установки очистите кэш в приложении "Инсталлер"