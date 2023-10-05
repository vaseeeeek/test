<?php
class shopColorfeaturesproPlugin extends shopPlugin
{
    public function getRandomGradient()
    {
        $degree = mt_rand(0, 360);
        $colorArray = [
            'rgba(0,31,255,1)',
            'rgba(255,82,82,1)',
            'rgba(106,255,82,1)',
            'rgba(247,82,255,1)',
            'rgba(255,230,82,1)',
        ];

        shuffle($colorArray);
        $colorArray = array_slice($colorArray, 0, mt_rand(2, sizeof($colorArray)));
        $countColor = sizeof($colorArray);

        for ($i = 0; $i < $countColor; $i++) {
            $dg = 100 / $countColor;
            $colorArray[$i] = $colorArray[$i] . ' ' . ($dg * $i) . '%';
        }
        $style = [];
        $style[] = 'background: linear-gradient(' . $degree . 'deg, ' . implode(',', $colorArray) . ');';
        return $style[mt_rand(0, sizeof($style) - 1)];
    }

    public function frontendProducts(&$params)
    {
        $colors = new shopColorfeaturesproPluginColorsModel();
        $colors = $colors->getAll('color_id');
        foreach ($params['products'] as $product_id => $product) {
            @$features = $product->features_selectable;
            if (empty($features))
                continue;
            foreach ($features as $feature_id => $feature) {
                if ($feature['type'] != 'color') continue;
                foreach ($feature['values'] as $value_id => $value) {
                    if (empty($colors[$value_id]) || empty($colors[$value_id]['style']))
                        continue;
                    if ($colors[$value_id]['style'] == 'random!') {
                        $value->style = $this->getRandomGradient();
                    } else {

                        $value->style = $colors[$value_id]['style'];
                    }
                    @$params['products'][$product_id][$feature_id][$value_id] = $value;
                }
            }
        }
    }

    public function getAllColors() 
    {
        $model = new shopColorfeaturesproPluginColorsModel();
        $query = "SELECT color_id, name, style FROM shop_colorfeaturespro";
        $result = $model->query($query)->fetchAll();
        return $result;
    }
}
