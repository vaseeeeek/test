<?php
class shopArdozhelperEditciasviCli extends waCliController
{
    public function execute()
    {   
        
        // Путь к CSV файлу
        $csvFile = './profflora.ru/public_html/wa-data/public/site/data/new/1/ss-gardengrove-auto.csv';

        // Читаем содержимое CSV файла
        $csvData = file_get_contents($csvFile);

        // Заменяем ячейки с "foto" и цифрами на "Изображения товаров"
        $updatedData = preg_replace('/foto\d+/', 'Изображения товаров', $csvData);

        // Записываем обновленные данные обратно в CSV файл
        file_put_contents($csvFile, $updatedData);

        echo 'CSV файл успешно обновлен.';
        
    }
}