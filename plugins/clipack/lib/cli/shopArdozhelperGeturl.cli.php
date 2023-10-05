<?php
class shopArdozhelperGeturlCli extends waCliController
{
    public function execute()
    {
        // Получаем XML из URL
        $url = 'https://gardengrove.ru/sitemap-iblock-4.xml';
        $xmlString = file_get_contents($url);

        // Создаем объект SimpleXMLElement из XML строки
        $xml = new SimpleXMLElement($xmlString);

        // Регулярное выражение для проверки соответствия урлов
        $regex = '/https:\/\/gardengrove\.ru\/catalog\/[0-9][a-z][0-9a-z]*\.html/';

        // Массив для хранения найденных урлов
        $urls = [];
        
        $model = new waModel();
        $arrayFeatLinks = $model->query("SELECT `value` FROM `shop_feature_values_varchar` WHERE `feature_id` = 36")->fetchAll("value");
        $arrayFeatLinks = array_keys($arrayFeatLinks);
        // Перебираем все узлы <url>
        foreach ($xml->url as $urlNode) {
            // Значение в узле <loc>
            $loc = (string) $urlNode->loc;
            
            // Проверяем соответствие регулярному выражению
            if (preg_match($regex, $loc)) {
                if (!in_array($loc, $arrayFeatLinks)) {
                    $urls[] = $loc;
                }
            }
        }

        // Генерируем HTML с найденными урлами
        $html = '';
        foreach ($urls as $url) {
            $html .= '<a href="' . $url . '">' . $url . '</a><br>';
        }

        // Записываем HTML в файл
        $file = fopen('./profflora.ru/public_html/wa-data/public/site/data/new/newgardengrovetoprofflora.html', 'w');
        fwrite($file, $html);
        fclose($file);

        echo 'Урлы записаны в файл!';
    }
}