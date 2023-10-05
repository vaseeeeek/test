<?php
/**
 * /products/<id>/sku/
 * Product editor, sku tab.
 */
class shopProdSkuAction extends waViewAction
{
    public function execute()
    {
        $product = $this->getProduct();
        $features = $this->getFeaturesSettings($product);

        // Feature values saved for skus: sku_id => feature code => value
        $product_features_model = new shopProductFeaturesModel();
        $skus_features_values = $product_features_model->getValuesMultiple($features, $product['id'], array_keys($product['skus']));

        $type_model = new shopTypeModel();
        $product_types = $type_model->getTypes(true);

        $features_selectable_model = new shopProductFeaturesSelectableModel();
        $selected_selectable_feature_ids = $features_selectable_model->getProductFeatureIds($product['id']);

        $plugin_fields = $this->pluginFieldsEvent($product);

        //для тестирования добавления характеристики
        //unset($features["test_013"]);

        $formatted_features = $this->formatFeatures($features);
        $formatted_product = $this->formatProduct($product, array(
            "plugin_fields"                   => $plugin_fields,
            "features"                        => $formatted_features,
            "skus_features_values"            => $skus_features_values,
            "selected_selectable_feature_ids" => $selected_selectable_feature_ids
        ));
        $formatted_selectable_features = $this->formatSelectableFeatures($formatted_features, $selected_selectable_feature_ids);

        $frontend_urls = shopProdGeneralAction::getFrontendUrls($product)[0];

        $backend_prod_content_event = $this->throwEvent($product);
        shopHelper::setDefaultNewEditor();

        $this->view->assign([
            'product'                       => $product,
            'product_types'                 => $product_types,
            'currencies'                    => $this->getCurrencies(),
            'stocks'                        => $this->getStocks(),
            'frontend_urls'                 => $frontend_urls,

            'product_sku_types'             => $this->getProductSkuTypes(),
            'new_modification'              => $this->getEmptyModification($product, $formatted_features, array( "plugin_fields" => $plugin_fields)),
            'new_sku'                       => $this->getEmptySku(),

            'formatted_product'             => $formatted_product,
            'formatted_features'            => $formatted_features,
            'formatted_selectable_features' => $formatted_selectable_features,

            'backend_prod_content_event' => $backend_prod_content_event,
        ]);

        $this->setLayout(new shopBackendProductsEditSectionLayout([
            'product' => $product,
            'content_id' => 'sku',
        ]));
    }

    protected function getProduct()
    {
        $product_id = waRequest::param('id', '', waRequest::TYPE_STRING);
        shopProdGeneralAction::createEmptyProduct($product_id);
        if ($product_id) {
            $product_model = new shopProductModel();
            $product_data = $product_model->getById($product_id);
        }
        if (empty($product_data)) {
            throw new waException(_w("Unknown product"), 404);
        }
        $product_model = new shopProductModel();
        if (!$product_model->checkRights($product_id)) {
            throw new waException(_w('Access denied'));
        }

        return new shopProduct($product_data);
    }

    protected function getFeaturesSettings(shopProduct $product)
    {
        $feature_model = new shopFeatureModel();

        // Features attached to product type
        $features = $feature_model->getByType($product->type_id, 'code');
        foreach ($features as $code => $feature) {
            $features[$code]['internal'] = true;
        }

        // Features attached to product directly, but not its type
        $codes = array_diff_key($product->features, $features);
        if ($codes) {
            $features += $feature_model->getByField('code', array_keys($codes), 'code');
        }

        // Fetch values for selectable features
        $selectable_features = array();
        foreach ($features as $code => $feature) {
            $features[$code]['feature_id'] = intval($feature['id']);
            if (!empty($feature['selectable'])) {
                $selectable_features[$code] = $feature;
            }
        }
        $selectable_features = $feature_model->getValues($selectable_features);
        foreach ($selectable_features as $code => $feature) {
            if (isset($features[$code]) && isset($feature['values'])) {
                $features[$code]['values'] = $feature['values'];
            }
        }

        return $features;
    }

    // also used in shopProdGeneralAction
    // and shopProdPricesAction
    public static function formatProduct($product, $options = [])
    {
        $features = (!empty($options["features"]) ? $options["features"] : []);
        $selected_selectable_feature_ids = (!empty($options["selected_selectable_feature_ids"]) ? $options["selected_selectable_feature_ids"] : []);
        $skus_features_values = (!empty($options["skus_features_values"]) ? $options["skus_features_values"] : []);
        $plugin_fields = ifset($options, "plugin_fields", ['price' => [], 'additional' => []]);

        $_product_params = [];
        if ($product->params) {
            foreach ($product->params as $k => $v) {
                if ($k != 'order' && $k != 'multiple_sku') {
                    $_product_params[] = $k. "=". $v;
                }
            }
        }

        $skus = [];

        $getPhotos = function($product) {
            $result = [];

            $_images = $product->getImages('thumb');

            foreach ($_images as $_image) {

                // Append file motification time to image URL
                // in order to avoid browser caching issues
                $last_modified = '';
                $path = shopImage::getPath($_image);
                if (file_exists($path)) {
                    $last_modified = '?'.filemtime($path);
                }

                $result[$_image["id"]] = [
                    "id" => $_image["id"],
                    "url" => $_image["url_thumb"].$last_modified,
                    "description" => $_image["description"]
                ];
            }

            return $result;
        };
        $photos = $getPhotos($product);

        // BADGES
        $badge_id = null;
        $badges = shopProductModel::badges();

        foreach($badges as $_badge_id => &$badge) {
            $badge["id"] = $_badge_id;
        }

        $badge_example_html = "<div class=\"badge\" style=\"background-color: #a1fcff;\"><span>" . _w("YOUR TEXT") . "</span></div>";

        $badges[""] = [
            "id" => "",
            "name" => _w("Custom badge"),
            "code" => $badge_example_html,
            "code_model" => $badge_example_html
        ];

        if ($product["badge"] === "") {
            $product["badge"] = null;
        }

        if (!empty($product["badge"])) {
            $badges[""]["code"] = $badges[""]["code_model"] = $product["badge"];
            $badge_id =  (empty($badges[$product["badge"]]) ? "" : $product["badge"]);
        }

        // Features that are rendered as checklists for product and allow multiple selection,
        // for SKUs must be rendered as a single select (no multiple selection).
        // This loop corrects for that.
        $_corrected_features = [];
        foreach ($features as $feature) {
            $_corrected_features[] = self::formatModificationFeature($feature);
        }

        foreach ($product['skus'] as $modification) {
            $modification["available"] = (boolean)$modification["available"];
            $modification["status"] = (boolean)$modification["status"];

            // Форматируем значение склада в нормальное число без 1.000
            if (!empty($modification["stock"])) {
                foreach($modification["stock"] as $_stock_id => $_stock_value) {
                    $modification["stock"][$_stock_id] = (int)$_stock_value;
                }
            }

            if (shopProdDownloadSkuFileController::checkSkuFile($modification['id'], $modification['product_id'])) {
                $modification["file"] = [
                    "id" => (!empty($modification["file_name"]) ? $modification["file_name"] : null),
                    "name" => (!empty($modification["file_name"]) ? $modification["file_name"] : null),
                    "size" => (!empty($modification["file_size"]) ? waCurrency::formatWithUnit($modification["file_size"]) : null),
                    "description" => (!empty($modification["file_description"]) ? $modification["file_description"] : ""),
                    "url" => shopProdDownloadSkuFileController::getSkuFileUrl($modification['id'], $modification['product_id'])
                ];
            } else {
                $modification["file"] = [
                    "id" => null,
                ];
            }


            // SKU photo
            $modification["photo"] = null;
            if ( !empty($modification["image_id"]) ) {
                if ($modification["id"] === $product["sku_id"]) {
                    $product["image_id"] = $modification["image_id"];
                }
                if ( !empty($photos[$modification["image_id"]]) ) {
                    $modification["photo"] = $photos[$modification["image_id"]];
                }
            }

            // SELECTABLE_FEATURES
            $_features = [];

            // The should be in the same order as in $selected_selectable_feature_ids
            $_selectable_features = array_fill_keys($selected_selectable_feature_ids, null);

            if (!empty($features)) {
                $_features_values = ifset($skus_features_values, $modification['id'], []);
                $_formatted_features = self::formatFeaturesValues($_corrected_features, $_features_values);
                foreach ($_formatted_features as $feature) {
                    if ( !empty($feature["available_for_sku"]) ) {
                        if (in_array($feature["id"], $selected_selectable_feature_ids)) {
                            $_selectable_features[$feature["id"]] = $feature;
                        } else {
                            $_features[] = $feature;
                        }
                    }
                }
            }

            // Make sure there are no NULLs left after array_fill_keys() above
            // also keys must start from 0
            $_selectable_features = array_values(array_filter($_selectable_features));

            // Figure out modification name, excluding feature names possibly attached at the end
            $modification_name = $modification['name'];
            $features_name = "";

            if ($modification_name && $_selectable_features) {
                // Loop over comma-delimeted parts of modification name, last to first
                // remove everything that looks like active selected feature name
                // break from loop as soon as we encounter anything that does not look like feature name
                $modification_name = array_filter(array_map('trim', explode(',', $modification_name)));
                $_features_name = [];
                while ($modification_name) {
                    $part = array_pop($modification_name);
                    if (!strlen($part)) {
                        continue;
                    }
                    foreach($_selectable_features as $f) {
                        $sku_feature_value = ifset($skus_features_values, $modification['id'], $f['code'], null);
                        if ($sku_feature_value) {
                            $active_feature_name = (string)$sku_feature_value;
                            $active_feature_name = mb_strtolower($active_feature_name);
                            if ($active_feature_name == mb_strtolower($part)) {
                                $_features_name[] = $part;
                                $part = '';
                                break;
                            }
                        }
                    }
                    if (strlen($part)) {
                        // stop as soon as part does not look like feature name
                        $modification_name[] = $part;
                        break;
                    }
                }
                $features_name = join(', ', array_reverse($_features_name));
                $modification_name = join(', ', $modification_name);
            }

            $modification["features"] = $_features;
            $modification["features_selectable"] = $_selectable_features;
            $modification["features_name"] = $features_name;
            $modification["original_name"] = $modification["name"];
            $modification["name"] = $modification_name;

            // Additional price fields from plugins
            $modification["additional_prices"] = [];
            foreach($plugin_fields['price'] as $additional_field) {
                $additional_field['value'] = ifset($additional_field, 'sku_values', $modification["id"], $additional_field['value']);
                unset($additional_field['sku_values']);
                $modification["additional_prices"][] = $additional_field;
            }

            // Other additional fields from plugins
            $modification["additional_fields"] = [];
            foreach($plugin_fields['additional'] as $additional_field) {
                $additional_field['value'] = ifset($additional_field, 'sku_values', $modification["id"], $additional_field['value']);
                unset($additional_field['sku_values']);

                if ($additional_field['render_type'] == 'select' && !empty($additional_field['options']) && is_scalar($additional_field['value'])) {
                    foreach($additional_field['options'] as $opt) {
                        if ((string)ifset($opt, 'value', '') === (string)$additional_field['value']) {
                            $additional_field['active_option'] = $opt;
                            break;
                        }
                    }
                }

                $modification["additional_fields"][] = $additional_field;
            }

            // MODIFICATIONS
            if ($modification['sku'] || $modification_name) {
                $sku_key = $modification['sku'].'###'.$modification_name;
                if (empty($skus[$sku_key])) {
                    $skus[$sku_key] = [
                        'sku' => $modification['sku'],
                        'name' => $modification_name,
                        'sku_id' => null,
                        'modifications' => [],
                    ];
                }
                $skus[$sku_key]['modifications'][] = $modification;

                if ($product["sku_id"] === $modification['id']) {
                    $skus[$sku_key]["sku_id"] = $modification['id'];
                }

            } else {
                $_id = uniqid($modification['id'], true);
                $skus[$_id] = [
                    'sku' => $modification['sku'],
                    'name' => $modification_name,
                    'sku_id' => ($product["sku_id"] === $modification['id'] ? $modification['id'] : null),
                    'modifications' => [$modification],
                ];
            }
        }

        $photo = ( !empty($photos) ? $photos[$product["image_id"]] : null );

        $_normal_mode = (count($product['skus']) > 1);
        $_normal_mode_switch = $_normal_mode || ifempty($product, 'params', 'multiple_sku', null) || $selected_selectable_feature_ids;

        // When product has a photo and only one modification with no photo,
        // use product image as photo for the modification.
        if ($photo && count($product['skus']) == 1 && $skus) {
            $sku_key = array_keys($skus)[0];
            if (empty($skus[$sku_key]['modifications'][0]['photo']) && !empty($skus[$sku_key]['modifications'][0])) {
                $skus[$sku_key]['modifications'][0]['image_id'] = $photo['id'];
                $skus[$sku_key]['modifications'][0]['photo'] = $photo;
            }
        }

        // Корректируем названия модификаций, потому что они отличаются (баг) от названий артикулов.
        foreach ($skus as &$sku) {
            foreach ($sku["modifications"] as &$sku_mod) {
                $sku_mod["sku"] = $sku["sku"];
                $sku_mod["name"] = $sku["name"];
            }
        }

        return [
            "id"                 => $product["id"],
            "name"               => $product["name"],
            "badges"             => array_values( $badges ),
            "badge_id"           => $badge_id,
            "sku_id"             => $product["sku_id"],
            "sku_type"           => $product["sku_type"],
            "currency"           => $product["currency"],
            "skus"               => array_values( $skus ),
            "image_id"           => $product["image_id"],
            "photo"              => $photo,
            "photos"             => array_values( $photos ),
            "params"             => implode(PHP_EOL, $_product_params),

            // Feature values saved for product: feature code => value (format depends on feature type)
            "features"           => self::formatFeaturesValues($features, $product['features']),

            // front-side options
            "normal_mode"        => $_normal_mode,
            "normal_mode_switch" => $_normal_mode_switch,
        ];
    }

    // also used in shopProdPricesAction
    public static function formatFeatures($features)
    {
        $result = array();

        $setUnits = function(&$feature, $units) {
            if (!empty($units)) {
                $_is_first = true;
                foreach ($units as $unit) {
                    if ($_is_first) {
                        if (empty($feature["default_unit"])) {
                            $feature["default_unit"] = $unit["value"];
                        }
                        $_is_first = false;
                    }

                    $_unit = [
                        "name" => $unit["title"],
                        "value" => $unit["value"]
                    ];

                    if ($_unit["value"] === $feature["default_unit"]) {
                        $feature["active_unit"] = $_unit;
                    }

                    $feature["units"][] = $_unit;
                }
            }
        };

        foreach ($features as $feature) {
            $feature["available_for_sku"] = (bool)$feature["available_for_sku"];
            $feature["visible_in_frontend"] = ($feature["status"] === "public");
            $feature["selectable"] = (bool)$feature["selectable"];
            $feature["multiple"] = (bool)$feature["multiple"];

            // TODO
            $feature["render_type"] = null;
            $feature["units"] = [];
            $feature["active_option"] = null;
            $feature["default_unit"] = ifset($feature, "default_unit", null);
            $feature["options"] = [];

            if ($feature["selectable"]) {
                if ($feature["multiple"]) {
                    $units = shopDimension::getUnits($feature["type"]);
                    $setUnits($feature, $units);

                    $feature["render_type"] = "checkbox";
                    foreach (ifset($feature, "values", []) as $value_id => $value) {
                        $_option = [
                            "name" => (string)$value,
                            "value" => (string)$value
                        ];

                        if ($value instanceof shopColorValue) {
                            if ( !empty($value["code"]) ) {
                                $_option["code"] = $value['hex'];
                            } else {
                                $_option["code"] = "#000000";
                            }
                        }

                        $feature["options"][] = $_option;
                    }
                    $feature["can_add_value"] = true;

                } else {
                    $units = shopDimension::getUnits($feature["type"]);
                    $setUnits($feature, $units);

                    $feature["render_type"] = "select";
                    $feature["options"][] = [
                        "name" => _w("Not defined"),
                        "value" => ""
                    ];

                    foreach (ifset($feature, "values", []) as $value_id => $value) {
                        $_option = [
                            "name" => (string)$value,
                            "value" => (string)$value
                        ];

                        if ($value instanceof shopColorValue) {
                            if ( !empty($value["code"]) ) {
                                $_option["code"] = $value['hex'];
                            } else {
                                $_option["code"] = "#000000";
                            }
                        }

                        $feature["options"][] = $_option;
                    }
                    $feature["active_option"] = reset($feature["options"]);
                    $feature["can_add_value"] = true;
                }
            } else {
                if ((strpos($feature["type"],'2d') === 0) || (strpos($feature["type"],'3d') === 0)) {
                    $feature["render_type"] = "field";
                    $_type = substr($feature["type"],3);
                    if (strpos($_type,'dimension') === 0) {
                        $units = shopDimension::getUnits($_type);
                        $setUnits($feature, $units);

                        $d = intval($feature["type"]);
                        for ($i = 0; $i < $d; $i++) {
                            $feature["options"][] = [
                                "name"  => "",
                                "value" => ""
                            ];
                        }
                    } else {
                        for ($i=0; $i < intval($feature["type"]); $i++) {
                            $feature["options"][] = [
                                "name" => "",
                                "value" => ""
                            ];
                        }
                    }

                } elseif (strpos($feature["type"],'dimension') === 0) {
                    $feature["render_type"] = "field";
                    $units = shopDimension::getUnits($feature["type"]);
                    $setUnits($feature, $units);

                    $feature["options"] = [
                        [
                            "name" => "",
                            "value" => ""
                        ]
                    ];

                } elseif (strpos($feature["type"],'range') === 0) {
                    $units = shopDimension::getUnits($feature["type"]);
                    $setUnits($feature, $units);

                    if ($feature["type"] == 'range.date') {
                        $feature["render_type"] = "range.date";
                        $feature["options"]     = [
                            [
                                "name"  => "",
                                "value" => ""
                            ],
                            [
                                "name"  => "",
                                "value" => ""
                            ]
                        ];
                    } else {
                        $feature["render_type"] = "range";
                        $feature["options"] = [
                            [
                                "name"  => "",
                                "value" => ""
                            ],
                            [
                                "name"  => "",
                                "value" => ""
                            ]
                        ];
                    }

                } elseif (strpos($feature["type"],'text') === 0) {
                    $feature["render_type"] = "textarea";
                    $feature["options"] = [
                        [
                            "name" => "",
                            "value" => ""
                        ]
                    ];

                } elseif (strpos($feature["type"],'color') === 0) {
                    $feature["render_type"] = "color";
                    $feature["options"] = [
                        [
                            "name" => _w("color name"),
                            "value" => "",
                            "code" => ""
                        ]
                    ];

                } elseif (strpos($feature["type"],'boolean') === 0) {
                    $feature["render_type"] = "select";
                    $feature["options"] = [
                        [
                            "name" => _w("Not defined"),
                            "value" => ""
                        ],
                        [
                            "name" => _w("Yes"),
                            "value" => "1"
                        ],
                        [
                            "name" => _w("No"),
                            "value" => "0"
                        ]
                    ];
                    $feature["active_option"] = reset($feature["options"]);
                    $feature["can_add_value"] = false;

                } elseif (strpos($feature["type"],'divider') === 0) {
                    $feature["render_type"] = "divider";
                    $feature["options"] = [
                        [
                            "name" => $feature["code"],
                            "value" => "-"
                        ]
                    ];

                } elseif (strpos($feature["type"],'date') === 0) {
                    $feature["render_type"] = "field.date";
                    $feature["options"] = [
                        [
                            "name" => "",
                            "value" => ""
                        ]
                    ];

                } else {
                    $feature["render_type"] = "field";
                    $feature["options"] = [
                        [
                            "name" => "",
                            "value" => ""
                        ]
                    ];
                }
            }

            unset($feature["builtin"]);
            unset($feature["count"]);
            unset($feature["feature_id"]);
            unset($feature["parent_id"]);
            unset($feature["status"]);
            unset($feature["values"]);

            $result[] = $feature;
        }

        return $result;
    }

    // Features that are rendered as checklists for product and allow multiple selection,
    // for SKUs must be rendered as a single select (no multiple selection).
    // This loop corrects for that.
    public static function formatModificationFeature($feature)
    {
        if ($feature["render_type"] === "checkbox") {
            $feature["render_type"] = "select";

            array_unshift($feature["options"],  [
                "name" => _w("Not defined"),
                "value" => ""
            ]);

            $feature["active_option"] = reset($feature["options"]);
        }

        /*
        // Когда-то добавлять новые значения в "выбранных характеристиках" было нельзя, потом можно. Оставлю на случай если вдруг снова станет нельзя :)
        // Ты знал, ты знал.
        */
        if ($feature["render_type"] === "select") {
            $feature["can_add_value"] = false;
        }

        return $feature;
    }

    protected static function formatFeaturesValues($features, $values)
    {
        $result = [];

        foreach ($features as $feature) {
            switch ($feature["render_type"]) {
                case "select":
                    if (isset($values[$feature["code"]])) {
                        $_feature_value = $values[$feature["code"]];

                        $_active_value = null;
                        if ($_feature_value instanceof shopBooleanValue) {
                            $_active_value = (string)$values[$feature["code"]]['value'];
                        } else {
                            $_active_value = (string)$_feature_value;
                        }

                        foreach ($feature["options"] as $_option) {
                            if ($_option["value"] === $_active_value) {
                                $feature["active_option"] = $_option;
                                break;
                            }
                        }
                    }
                    break;

                case "checkbox":
                    $_active_array = [];
                    $_is_array = false;
                    if (!empty($values[$feature["code"]])) {
                        $_feature_value = $values[$feature["code"]];
                        if (is_array($_feature_value)) {
                            foreach ($_feature_value as $_value) {
                                $_active_array[] = (string)$_value;
                            }
                            $_is_array = true;
                        } else {
                            $_active_array[] = (string)$_feature_value;
                        }
                    }

                    $_active_option = null;

                    foreach ($feature["options"] as &$option) {
                        $_is_active = in_array($option["value"], $_active_array);
                        $option["active"] = $_is_active;
                        if (!$_is_array && $_is_active) {
                            $_active_option = $option;
                        }
                    }

                    if (!$_is_array) {
                        $feature["active_option"] = ($_active_option ? $_active_option : reset( $feature["options"] ) );
                    }
                    break;

                case "textarea":
                    $_feature_value = ifset($values, $feature["code"], "");
                    $feature["value"] = $_feature_value;
                    break;

                case "field":
                    if (isset($values[$feature["code"]])) {
                        $_feature_value = $values[$feature["code"]];

                        if ($_feature_value instanceof shopDimensionValue) {
                            // dimension: one value with measurement unit
                            $feature["options"][0]["value"] = (string)$_feature_value['value'];
                            $_unit_value = (string)$_feature_value['unit'];
                            foreach ($feature["units"] as $_unit) {
                                if ($_unit["value"] === $_unit_value) {
                                    $feature["active_unit"] = $_unit;
                                    break;
                                }
                            }

                        } else if ($_feature_value instanceof shopCompositeValue) {
                            // composite dimension (N x N x N): several values with measurement unit
                            $fields_count = 3;
                            if ('2d' === substr($feature["type"], 0, 2)) {
                                $fields_count = 2;
                            }
                            for ($i = 0; $i < $fields_count; $i++) {
                                if (isset($_feature_value[$i])) {
                                    $_subvalue = $_feature_value[$i];
                                    if ($_subvalue instanceof shopDimensionValue) {
                                        $feature["options"][$i]["value"] = (string)$_subvalue['value'];
                                    } else {
                                        $feature["options"][$i]["value"] = (string)$_subvalue;
                                    }
                                }
                            }

                            if (!empty($_feature_value['0']['unit'])) {
                                $_unit_value = (string)$_feature_value[0]['unit'];
                                foreach ($feature["units"] as $_unit) {
                                    if ($_unit["value"] === $_unit_value) {
                                        $feature["active_unit"] = $_unit;
                                        break;
                                    }
                                }
                            }

                        } else {
                            // single value without measurement unit
                            $feature["options"][0]["value"] = (string)$_feature_value;
                        }
                    }
                    break;

                case "field.date":
                    if (!empty($values[$feature["code"]])) {
                        $_feature_value = $values[ $feature["code"] ];
                        if ( $_feature_value instanceof shopDateValue ) {
                            if ( !empty($_feature_value["timestamp"]) ) {
                                $_date = date( "Y-m-d", $_feature_value["timestamp"] );
                                $feature["options"][0]["value"] = (string) $_date;
                            }
                        }
                    }
                    break;

                case "color":
                    if (!empty($values[$feature["code"]])) {
                        $_feature_value = $values[ $feature["code"] ];
                        if ($_feature_value instanceof shopColorValue) {
                            if ( !empty($_feature_value["value"]) ) {
                                $feature["options"][0]["value"] = (string)$_feature_value["value"];
                            }
                            if ( !empty($_feature_value["code"]) ) {
                                $feature["options"][0]["code"] = $_feature_value['hex'];
                            } else {
                                $feature["options"][0]["code"] = "#000000";
                            }
                        }
                    }
                    break;

                case "range":
                    if (!empty($values[$feature["code"]])) {
                        $_feature_value = $values[$feature["code"]];
                        $_unit_value = null;

                        if ($_feature_value instanceof shopRangeValue) {
                            if ( !empty($_feature_value["begin"]) ) {
                                if ($_feature_value["begin"] instanceof shopDimensionValue) {
                                    $feature["options"][0]["value"] = (string)$_feature_value["begin"]["value"];
                                    $_unit_value = (string)$_feature_value["begin"]['unit'];
                                } else {
                                    $feature["options"][0]["value"] = (string)$_feature_value["begin"];
                                }
                            }

                            if ( !empty($_feature_value["end"]) ) {
                                if ($_feature_value["end"] instanceof shopDimensionValue) {
                                    $feature["options"][1]["value"] = (string)$_feature_value["end"]["value"];
                                    $_unit_value = (string)$_feature_value["end"]['unit'];
                                } else {
                                    $feature["options"][1]["value"] = (string)$_feature_value["end"];
                                }
                            }
                        }

                        if (!empty($_unit_value)) {
                            foreach ($feature["units"] as $_unit) {
                                if ($_unit["value"] === $_unit_value) {
                                    $feature["active_unit"] = $_unit;
                                    break;
                                }
                            }
                        }
                    }
                    break;

                case "range.date":
                    if (!empty($values[$feature["code"]])) {
                        $_feature_value = $values[$feature["code"]];
                        if ($_feature_value instanceof shopRangeValue) {
                            if (!empty($_feature_value["begin"]["timestamp"])) {
                                $_start_date = date("Y-m-d", $_feature_value["begin"]["timestamp"]);
                                $feature["options"][0]["value"] = (string)$_start_date;
                            }
                            if (!empty($_feature_value["end"]["timestamp"])) {
                                $_end_date = date( "Y-m-d", $_feature_value["end"]["timestamp"] );
                                $feature["options"][1]["value"] = (string) $_end_date;
                            }
                        }
                    }
                    break;

                default:
                    break;
            }

            $result[] = $feature;
        }

        return $result;
    }

    protected function formatSelectableFeatures($features, $selected_selectable_feature_ids)
    {
        $active = [];
        $inactive = [];

        foreach ($features as $_feature) {
            if (!empty($_feature['available_for_sku'])) {
                // range, 2d and 3d features are not supported as selectable
                $is_composite = preg_match('~^(2d|3d|range)\.~', $_feature['type']);
                if (!$is_composite) {
                    $disabled = !in_array($_feature["render_type"], ["select", "checkbox", "field", "field.date", "textarea", "color"]);
                    $data = [
                        "id"          => $_feature["id"],
                        "name"        => $_feature["name"],
                        "render_type" => $_feature["render_type"],
                        "disabled"    => $disabled,
                        "active"      => in_array( $_feature["id"], $selected_selectable_feature_ids ),
                    ];
                    if ($data['active']) {
                        $active[$_feature["id"]] = $data;
                    } else {
                        $inactive[] = $data;
                    }
                } else {
                    $inactive[] = [
                        "id"          => $_feature["id"],
                        "name"        => $_feature["name"],
                        "render_type" => $_feature["render_type"],
                        "disabled"    => true,
                        "active"      => false,
                    ];
                }
            }
        }

        // Active features in the result has to be sorted
        // in the same order as in $selected_selectable_feature_ids
        $result = [];
        foreach($selected_selectable_feature_ids as $id) {
            if (isset($active[$id])) {
                $result[] = $active[$id];
            }
        }

        return array_merge($result, $inactive);
    }

    public static function getCurrencies()
    {
        $result = [];

        $model = new shopCurrencyModel();
        $currencies = $model->getCurrencies();

        foreach ($currencies as $_currency) {
            $result[$_currency["code"]] = [
                "code" => $_currency["code"],
                "title" => $_currency["title"]
            ];
        }

        return $result;
    }

    public static function getStocks()
    {
        $stocks = shopHelper::getStocks(false);

        foreach ($stocks as $key => &$_stock) {
            $_is_virtual = isset($_stock["substocks"]);
            $_stock["id"] = $key;
            $_stock["is_virtual"] = $_is_virtual;
        }

        return $stocks;
    }

    protected function getEmptySku()
    {
        return [
            "name"          => "",
            "sku"           => mb_strtolower(sprintf('%s_', _w("SKU"))), // Это поле является основой для группировки модификаций, для новодобавленных артикулов оно генерируется на стороне JS (добавляется индекс)
            "sku_id"        => null,
            "modifications" => [],
            "expanded"      => true,
            "render_skus"   => true
        ];
    }

    protected function getEmptyModification($product, $features, $options)
    {
        $plugin_fields = ifset($options, "plugin_fields", ['price' => [], 'additional' => []]);

        $result = [
            "id"                  => null,
            "product_id"          => $product["id"],
            "sku"                 => null,
            "name"                => null,
            "image_id"            => null,
            "price"               => 0,
            "purchase_price"      => 0,
            "compare_price"       => 0,
            "count"               => null,

            "available"           => true,
            "status"              => true,

            "features"            => [],

            'additional_fields' => $plugin_fields['additional'],
            'additional_prices' => $plugin_fields['price'],

            // will be set at front
            "stock"               => [],
            "features_selectable" => []
        ];

        if ( !empty($features) ) {
            foreach ($features as $feature) {
                if (!empty($feature["available_for_sku"])) {
                    $result["features"][] = self::formatModificationFeature($feature);
                }
            }
        }

        return $result;
    }

    protected function getProductSkuTypes()
    {
        return [
            shopProductModel::SKU_TYPE_FLAT => [
                "id" => shopProductModel::SKU_TYPE_FLAT,
                "name" => _w("By SKU name")
            ],
            shopProductModel::SKU_TYPE_SELECTABLE => [
                "id" => shopProductModel::SKU_TYPE_SELECTABLE,
                "name" => _w("By features such as size or color")
            ],
        ];
    }

    /**
     * Throw 'backend_prod_content' event
     * @param shopProduct $product
     * @return array
     * @throws waException
     */
    protected function throwEvent($product)
    {
        /**
         * @event backend_prod_content
         * @since 8.18.0
         *
         * @param shopProduct $product
         * @param string $content_id
         *       Which page (tab) is shown
         */
        $params = [
            'product' => $product,
            'content_id' => 'sku',
        ];
        return wa('shop')->event('backend_prod_content', $params);
    }

    protected function pluginFieldsEvent($product)
    {
        /*

        Field description expected from plugin via backend_prod_sku_fields event:

        [
            'type' => 'price',// |input|textarea|select
            'id' => 'zzzz',
            'name' => '',
            'default_value' => '', // used for new sku
            'tooltip' => '',
            'css_class' => '',
            'validate' => [
                'required' => false,
                'numbers' => false, // price only
            ],
            'placement' => 'top', // |bottom; ignored for price
            'options' => [ // select only
                [ 'name' => '', 'value' => '' ],
            ],
            'sku_values' => [
                sku_id => value
            ],
        ]

        */

        /**
         * @event backend_prod_sku_fields
         * @since 8.18.0
         *
         * @param shopProduct $product
         */
        $params = [
            'product' => $product,
        ];
        $result = [
            'price' => [],
            'additional' => [],
        ];
        $raw_plugin_fields = wa('shop')->event('backend_prod_sku_fields', $params);

        $sku_default_values = array_fill_keys(array_keys($product['skus']), null);

        foreach($raw_plugin_fields as $fields)
        {
            if (!is_array($fields)) {
                continue;
            }
            foreach($fields as $raw_field) {
                if (empty($raw_field['type']) || !is_string($raw_field['type']) || empty($raw_field['id']) || !is_string($raw_field['id'])) {
                    continue;
                }
                $field = [
                    'render_type' => 'field',
                    'id' => $raw_field['id'],
                    'name' => ifset($raw_field, 'name', null),
                    'value' => ifset($raw_field, 'default_value', null),
                    'tooltip' => ifset($raw_field, 'tooltip', null),
                    'css_class'   => ifset($raw_field, 'css_class', null),
                    'validate'    => [
                        'required' => ifset($raw_field, 'validate', 'required', false),
                    ],
                    'sku_values' => ifset($raw_field, 'sku_values', []),
                ];

                if ($raw_field['type'] == 'select') {
                    // check select options, gather available values
                    $available_values = [];
                    $field['options'] = ifset($raw_field, 'options', []);
                    if (!is_array($field['options'])) {
                        $field['options'] = [];
                    }
                    foreach($field['options'] as $i => $o) {
                        if (!is_array($o) || !isset($o['name']) || !isset($o['value'])) {
                            unset($field['options'][$i]);
                            continue;
                        }
                        $available_values[$o['value']] = $o['value'];
                    }
                    $field['options'] = array_values($field['options']);

                    // check default value
                    if (!isset($available_values[$field['value']])) {
                        $field['value'] = reset($available_values);
                    }
                }

                if (!is_array($field['sku_values'])) {
                    $field['sku_values'] = [];
                } else {
                    $field['sku_values'] = array_intersect_key($field['sku_values'], $sku_default_values);
                    foreach(array_keys($sku_default_values) as $sku_id) {
                        if (!isset($field['sku_values'][$sku_id])) {
                            $field['sku_values'][$sku_id] = $field['value'];
                        }
                    }
                }

                if ($raw_field['type'] == 'price') {
                    $field['validate']['numbers'] = ifset($raw_field, 'validate', 'numbers', false);
                    $result['price'][] = $field;
                } else {
                    $field['placement'] = ifset($raw_field, 'placement', 'top') == 'top' ? 'top' : 'bottom';
                    switch ($raw_field['type']) {
                        case 'textarea':
                            $field['render_type'] = 'textarea';
                            break;
                        case 'select':
                            $field['render_type'] = 'select';

                            // check sku values
                            foreach($field['sku_values'] as $sku_id => $value) {
                                if (isset($value) && !isset($available_values[$value])) {
                                    $field['sku_values'][$sku_id] = null;
                                }
                            }
                            break;
                        case 'input':
                        default:
                            // nothing to do
                            break;
                    }
                    $result['additional'][] = $field;
                }
            }
        }

        return $result;
    }

    /**
     * @param int $product_id
     * @param int $sku_type
     */
    public static function isSkuCorrect($product_id, $sku_type)
    {
        $product_skus_model = new shopProductSkusModel();
        $skus = $product_skus_model->getDataByProductId($product_id);
        $empty_feature = false;
        $same_names = false;
        $same_features = false;
        if (!empty($skus)) {
            $product_features_model = new shopProductFeaturesModel();
            $skus_count = count($skus);
            if ($skus_count > 1) {
                if (empty($sku_type)) {
                    $sku_count_names = array_count_values(array_column($skus, 'name'));
                    foreach ($sku_count_names as $count) {
                        if ($count > 1) {
                            $same_names = true;
                            break;
                        }
                    }
                } else {
                    $skus_features = $product_features_model->select('sku_id, feature_id, feature_value_id')
                                           ->where('!ISNULL(sku_id) AND product_id = ' . $product_id)->fetchAll();
                    $combined_features = [];
                    foreach ($skus_features as $feature) {
                        if (!isset($combined_features[$feature['sku_id']])) {
                            $combined_features[$feature['sku_id']] = '';
                        }
                        $combined_features[$feature['sku_id']] .= $feature['feature_id'] . ','
                            . $feature['feature_value_id'] . ',';
                    }
                    $same_features_count = array_count_values($combined_features);
                    foreach ($same_features_count as $count) {
                        if ($count > 1) {
                            $same_features = true;
                            break;
                        }
                    }
                }
            }
            if (!($same_names || $same_features)) {
                $product_features_selectable_model = new shopProductFeaturesSelectableModel();
                $features_selectable = $product_features_selectable_model->select('feature_id')
                                            ->where('product_id = ' . $product_id)->fetchAll('feature_id');
                if ($features_selectable) {
                    $count_filled_features = $product_features_model->select('count(feature_id) as count')
                        ->where('!ISNULL(sku_id) AND product_id = ' . $product_id
                            . ' AND feature_id IN (' . implode(',', array_keys($features_selectable)) . ')')
                        ->fetchField('count');
                    $empty_feature = (count($features_selectable) * $skus_count) != $count_filled_features;
                }
            }
        }

        return $empty_feature || $same_names || $same_features;
    }

}
