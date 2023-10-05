


function var_dump() {
    var output = '', pad_char = ' ', pad_val = 4, lgth = 0, i = 0, d = this.window.document;
    var getFuncName = function (fn) {
        var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
        if (!name) {
            return '(Anonymous)'
        }
        return name[1]
    };
    var repeat_char = function (len, pad_char) {
        var str = '';
        for (var i = 0; i < len; i++) {
            str += pad_char
        }
        return str
    };
    var getScalarVal = function (val) {
        var ret = '';
        if (val === null) {
            ret = 'NULL'
        } else if (typeof val === 'boolean') {
            ret = 'bool(' + val + ')'
        } else if (typeof val === 'string') {
            ret = 'string(' + val.length + ') "' + val + '"'
        } else if (typeof val === 'number') {
            if (parseFloat(val) == parseInt(val, 10)) {
                ret = 'int(' + val + ')'
            } else {
                ret = 'float(' + val + ')'
            }
        } else if (val === undefined) {
            ret = 'UNDEFINED';
        } else if (typeof val === 'function') {
            ret = 'FUNCTION';
            ret = val.toString().split("\n");
            var txt = '';
            for (var j in ret) {
                if(ret.hasOwnProperty(j)) {
                    txt += (j != 0 ? '' : '') + ret[j] + "\n"
                }
            }
            ret = txt
        } else if (val instanceof Date) {
            val = val.toString();
            ret = 'string(' + val.length + ') "' + val + '"'
        } else if (val.nodeName) {
            ret = 'HTMLElement("' + val.nodeName.toLowerCase() + '")'
        }
        return ret
    };
    var formatArray = function (obj, cur_depth, pad_val, pad_char) {
        var someProp = '';
        if (cur_depth > 0) {
            cur_depth++
        }
        var  base_pad = repeat_char(pad_val * (cur_depth - 1), pad_char);
        var thick_pad = repeat_char(pad_val * (cur_depth + 1), pad_char);
        var str = '';
        var val = '';
        if (typeof obj === 'object' && obj !== null) {
            if (obj.constructor && getFuncName(obj.constructor) === 'PHPJS_Resource') {
                return obj.var_dump();
            }
            lgth = 0;
            for (someProp in obj) {
                lgth++
            }
            str += "array(" + lgth + ") {\n";
            for (var key in obj) {
                if(obj.hasOwnProperty(key)) {
                    if (typeof obj[key] === 'object' && obj[key] !== null && !(obj[key] instanceof Date) && !obj[key].nodeName) {
                        str += thick_pad + "[" + key + "] =>\n" + thick_pad + formatArray(obj[key], cur_depth + 1, pad_val, pad_char)
                    } else {
                        val = getScalarVal(obj[key]);
                        str += thick_pad + "[" + key + "] =>\n" + thick_pad + val + "\n"
                    }
                }
            }
            str += base_pad + "}\n"
        } else {
            str = getScalarVal(obj)
        }
        return str
    };
    output = formatArray(arguments[0], 0, pad_val, pad_char);
    for (i = 1; i < arguments.length; i++) {
        output += '\n' + formatArray(arguments[i], 0, pad_val, pad_char)
    }
    return output
}
function av(data) {
    alert(var_dump(data))
}

(function($) {
    'use strict';
    // Метод ищет общего предка (product-list)
    // допустим задаешь кнопку купить, он сравнивает все родительские елементы продуктов пока не найдет общий
    $.fn.commonParent = function(max_parent_level) {
        // Будем искать сравиния сами элемены
        if(!max_parent_level) {
            max_parent_level = 10;
        }
        var common_Parent = false;
        var _parent_elements = {};
        this.each(function () {
            var level = 0;
            var find_el_flag = false;
            var current_element = $(this);
            for(var i = 0; i < max_parent_level; i++) {
                current_element = current_element.parent();
                var current_elementElem = current_element.get(0);
                // перепроверяем для каждого, мало ли уровень вложенности разный
                for(var ez in _parent_elements) {
                    if(_parent_elements.hasOwnProperty(ez)) {
                        var _parent_elementsElem = _parent_elements[ez].get(0);
                        // Сравниваем исходные элементы
                        if(current_elementElem == _parent_elementsElem) {
                            common_Parent = current_element;
                            find_el_flag = true;
                            break;
                        }
                    }
                }
                if(find_el_flag) {
                    break;
                }
                _parent_elements[level] = current_element;
                level++;
            }
            if(find_el_flag) {
                return true;
            }
        });
        if(common_Parent) {
            return this.pushStack(common_Parent);
        }
        return false;

    };
})(jQuery);// объект выборки элементов для работы плагина, может быть определен в теме дизайна для изменения выборки нужных элементов


if(typeof $.saleskuPluginProductElements != 'object') {
    $.saleskuPluginProductElements = {};
}
$.saleskuPluginProductElements.exists = function (name) {
    if(this.hasOwnProperty(name)) {
        return true;
    }
    return false;
};
$.saleskuPluginProductElements.set = function (name, data, force) {
    if(force || !this.exists(name)) {
        this[name] = data;
    }
};
$.saleskuPluginProductElements.get = function (name) {
    if(this.exists(name)) {
        return this[name];
    }
    return null;
};
///Основные селекторы
$.saleskuPluginProductElements.set('_Selectors', {
    salesku_product_id: '.salesku-id',
    salesku_product_root: '.salesku_plugin-product',
    input_product_id: '[name="product_id"]',
    input_product_id_name: 'product_id',
    div_offers: '.offers',
    price: '.price',
    compare_price:'.compare-at-price',
    compare_price_html: '<span class="compare-at-price nowrap"></span>',
    image: '.image',
    skus: '.skus',
    skus_button: '.skus input[type=radio]',
    skus_button_container: 'label',
    services: '.services',
    services_variants: '.service-variants',
    stocks:'.stocks',
    sku_feature: '.sku-feature',
    sku_feature_value_class_active : 'selected',
    sku_feature_value_class_grey : '',
    sku_feature_value_class_hide :'',
    sku_feature_element_data_id : 'feature-id',
    sku_feature_container: '.inline-select',
    sku_feature_button: 'a',
    sku_options: '.options',
    options_container: '.salesku_options',
    cart_button_plugin: '.salesku_plugin-cart-button',
    cart_button_class: '.addtocart',
    cart_button: '[type=submit]',
    quantity: '.qty,[name="quantity"]',
    added2cart: '.added2cart',
    cart: '#cart',
    form_action_indicator: 'data-url'
});
$.saleskuPluginProductElements.set('Root', function(id_element) {
    var self = this;
    var id = $(id_element).attr('id');
    if(id != undefined && (!this.hasOwnProperty('_products_root_elements') || !self._products_root_elements.hasOwnProperty(id))) {

        var salesku_id =  $(self._Selectors.salesku_product_id);
        this._products_root_elements = {};
        var product_list = false;
        if(salesku_id.length) {
            if(salesku_id.length>1) {
                if($(id_element).closest(self._Selectors.salesku_product_root).hasClass(self._Selectors.salesku_product_root.replace(/\./g, ''))) {
                    product_list = $(id_element).closest(self._Selectors.salesku_product_root).parent();
                } else {
                    product_list = salesku_id.commonParent();
                }
            } else {
                if($(id_element).closest(self._Selectors.salesku_product_root).hasClass(self._Selectors.salesku_product_root.replace(/\./g, ''))) {
                    product_list = $(id_element).closest(self._Selectors.salesku_product_root).parent();
                } else {
                    // Ищем блок продукта по форме
                    var current_element = salesku_id;
                    if(current_element.closest('li').attr('itemtype')) {
                        product_list = current_element.closest('li').parent();
                    } else {
                        var pr_id;
                        for(var i = 0; i < 10; i++) {
                            current_element = current_element.parent();
                            pr_id = current_element.find('form').find(self._Selectors.input_product_id);
                            if(pr_id && pr_id.val()>0) {
                                product_list = current_element.parent().parent();
                                console.log('Salesku: родительский элемент пределен не точно, рекомендуется указать класс(salesku_plugin-product) для корневых элементов продуктов!');
                                break;
                            }
                        }
                    }
                    // Ищем по ссылке на продукт
                    if(!product_list) {
                        current_element = salesku_id;
                        for(var i = 0; i < 10; i++) {
                            current_element = current_element.parent();
                            if(current_element.find('a').length) {
                                pr_id = current_element.find(self._Selectors.input_product_id);
                                if(pr_id && pr_id.val()>0) {
                                    product_list = current_element.parent();
                                    console.log('Salesku: родительский элемент пределен не точно, рекомендуется указать класс(salesku_plugin-product) для корневых элементов продуктов!');
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        if(!product_list) {
            console.log('Salesku: Не найден родительский элемент продуктов!');
        }  else {
            if(!product_list.hasClass('salesku_plugin_product_list')) {
                $.saleskuPluginProductsPool.addList(product_list);
            }
            // Методом эхолокации выбираем только продукты, мало ли что запихнули в product-list кроме продуктов
            product_list.children().each(function () {
                var find_id_element = $(this).find(self._Selectors.salesku_product_id);
                // Проверяем дополнительно класс
                if(find_id_element.hasClass(self._Selectors.salesku_product_id.replace(/\./g, ''))) {
                    self._products_root_elements[find_id_element.attr('id')] = $(this);
                    $(this).addClass('salesku_plugin-product');
                }
            });
        }
    }
    if(self._products_root_elements.hasOwnProperty(id)) {
        return self._products_root_elements[id];
    } else {
        console.log('Salesku: Корневой элемент продукта ('+id+')не найден, для более правильного ' +
            'определения добавьте корневому элементу (обычно <li>) класс "salesku_plugin-product"'+"\n"+'Должно получиться примерно так: <li class="salesku_plugin-product">.');
    }
});
$.saleskuPluginProductElements.set('Parent', function(root_element) {
    return root_element.parent();
});
// Форма
$.saleskuPluginProductElements.set('Form', function(root_element) {
    var self = this;
    var form = false;
    if(root_element) {
        root_element.find('form').each(function () {
            var pr_id = $(this).find(self._Selectors.input_product_id);
            if(pr_id.attr('name')==self._Selectors.input_product_id_name && pr_id.val()>0) {
                form = $(this);
            }
        });
        if(form) {
            return form;
        }
        return root_element;
    }
    return root_element;

});
// Цена товара
$.saleskuPluginProductElements.set('Price', function(root_element) {
    var price = false;
    var self = this;
    root_element.find(self._Selectors.price).each(function () {
        if(!$(this).closest(self._Selectors.skus).hasClass(self._Selectors.skus.replace(/\./g, ''))) {
            price = $(this);
        }
    });
    if(!price) {
        console.log('Salesku: Не найден элемент цены продукта: '+self._Selectors.price+'! Рекоммендуется добавить тегу аоказа цены товара селектор: '+self._Selectors.price);
    }
    return price;
});
// Старая цена товара
$.saleskuPluginProductElements.set('ComparePrice',  function(root_element) {
    return root_element.find(this._Selectors.compare_price);
});
// Элемент картинки товара
$.saleskuPluginProductElements.set('Image',  function(root_element) {
    return root_element.find(this._Selectors.image).find('img');
});
$.saleskuPluginProductElements.set('OriginalImage',  function(root_element) {
    return root_element.find(this._Selectors.image).find('img').attr('src');
}  );
// Элементы артикулов товара
$.saleskuPluginProductElements.set('Skus',   function(root_element) {
    return root_element.find(this._Selectors.skus);
});
$.saleskuPluginProductElements.set('SkuContainer',  function(sku_input) {
    return sku_input.closest(this._Selectors.skus_button_container);
});
/// Элемент контейнера выбора опций товара
$.saleskuPluginProductElements.set('OptionsContainer',   function(root_element) {
    return root_element.find(this._Selectors.options_container);
});
$.saleskuPluginProductElements.set('Options',   function(root_element) {
    return root_element.find(this._Selectors.sku_options);
});
// Элементы сервисов продукта
$.saleskuPluginProductElements.set('Services',  function(root_element) {
    return root_element.find(this._Selectors.services);
});
// Элемент характеристики товара
$.saleskuPluginProductElements.set('SkuFeature',  function(root_element, id) {
    return root_element.find('[name="features['+id+']"]');
});
// Все элементы характеристик товара
$.saleskuPluginProductElements.set('SkuFeatures',  function(root_element) {
    return root_element.find(this._Selectors.sku_feature);
});
$.saleskuPluginProductElements.set('Stocks',  function(root_element) {
    return root_element.find(this._Selectors.stocks);
});
// Элемент кнопки "В корзину"
$.saleskuPluginProductElements.set('CartButton',  function(root_element) {
    var button = this.Form(root_element).find(this._Selectors.cart_button_plugin);
    if(!(button.length==1)) {
        button = this.Form(root_element).find(this._Selectors.cart_button_class);
        if(!(button.length==1)) {
            button = this.Form(root_element).find(this._Selectors.cart_button);
        }
    }
    return button;
});
// Элемент ввода количества товара для покупки
$.saleskuPluginProductElements.set('Quantity',   function(root_element) {
    return root_element.find(this._Selectors.quantity);
});
// Элемент для появления надписи в корзине о товаре
$.saleskuPluginProductElements.set('added2cart', function(root_element) {
    return root_element.find(this._Selectors.added2cart);
});
$.saleskuPluginProductElements.set('Selectors', function() {
    return this._Selectors;
});

// Управляющий класс
saleskuPluginProductElements = function(id_element) {
    this.root_element = $.saleskuPluginProductElements.Root(id_element);
};
saleskuPluginProductElements.prototype = {
    'Root' :           function () {return $.saleskuPluginProductElements.root_element},
    'Selectors':       function () {return $.saleskuPluginProductElements.get('_Selectors');},
    'Parent':          function () {return $.saleskuPluginProductElements.Parent(this.root_element);},
    'Form':            function () {return $.saleskuPluginProductElements.Form(this.root_element);},
    'Price':           function () {return $.saleskuPluginProductElements.Price(this.root_element);},
    'ComparePrice':    function () {return $.saleskuPluginProductElements.ComparePrice(this.root_element);},
    'Image':           function () {return $.saleskuPluginProductElements.Image(this.root_element);},
    'OriginalImage':   function () {return $.saleskuPluginProductElements.OriginalImage(this.root_element);},
    'Skus':            function () {return $.saleskuPluginProductElements.Skus(this.root_element);},
    'SkuContainer':    function (sku_input) {return $.saleskuPluginProductElements.SkuContainer(sku_input);},
    'OptionsContainer':function () {return $.saleskuPluginProductElements.OptionsContainer(this.root_element);},
    'Options':         function () {return $.saleskuPluginProductElements.Options(this.root_element);},
    'SkuFeature':      function (id) {return $.saleskuPluginProductElements.SkuFeature(this.root_element, id);},
    'SkuFeatures':     function () {return $.saleskuPluginProductElements.SkuFeatures(this.root_element);},
    'Stocks':          function () {return $.saleskuPluginProductElements.Stocks(this.root_element);},
    'Services':        function () {return $.saleskuPluginProductElements.Services(this.root_element);},
    'CartButton':      function () {return $.saleskuPluginProductElements.CartButton(this.root_element);},
    'Quantity':        function () {return $.saleskuPluginProductElements.Quantity(this.root_element);},
    'added2cart':      function () {return $.saleskuPluginProductElements.added2cart(this.root_element);}
};

saleskuPluginProductSkuFeature = function($feature_obj, options) {
    if(typeof options =='object') {
        for(var k in options) {
            this[k] = options[k];
        }
    }
    this.feature = $feature_obj;
    if(this.getTag()==='INPUT' && this.getElement().attr('type')=='radio') {
        this.radio = this.getElement().closest(this.Selectors().sku_feature_container).find('input[type=radio]');
        var checked = this.getElement().closest(this.Selectors().sku_feature_container).find('input[type=radio]:checked');
        if(checked.length>0) {
            this.feature = checked;

        } else {
            this.feature = this.getElement().closest(this.Selectors().sku_feature_container).find('input[type=radio]:first');
            this.setValue(this.feature.val());
        }
    }
};
saleskuPluginProductSkuFeature.prototype = {

    Selectors: function(){
        return  $.saleskuPluginProductElements.Selectors();
    },
    getId: function(){
        return this.feature.data(this.Selectors().sku_feature_element_data_id);
    },
    getElement: function () {
        return this.feature;
    },
    getButtons: function () {
        return this.feature
            .closest(this.Selectors().sku_feature_container)
            .find(this.Selectors().sku_feature_button);
    },
    getButtonValue: function($button_obj) {
        return $button_obj.data('value');
    },
    show: function () {
        var feature_block =  this.feature.closest('.salesku-feature-block');
        feature_block.find('.salesku-feature-value').hide();
        feature_block.find('.salesku-feature-select').show();
    },
    hide: function() {
        var feature_block =  this.feature.closest('.salesku-feature-block');
        feature_block.find('.salesku-feature-value').hide();
        feature_block.find('.salesku-feature-select').hide();
        feature_block.find('.salesku-feature-value-'+this.getValue()).show();
    },
    getValues: function (active, sort) {
        var self = this;
        sort = sort||false;
        var tag = self.getTag();
        var values = {};
        var index = 0;
        if(tag == 'INPUT') {
            self.getButtons().each(function () {
                var val_id = self.getButtonValue($(this));
                if(!active || (!$(this).hasClass(self.getHideClass(1)) && !$(this).hasClass(self.getHideClass(2)))) {
                    if(!sort) {
                        values[val_id] = $(this).html();
                    }  else {
                        values[index] = {'id': val_id, 'value': $(this).html()};
                        index++;
                    }
                }
            });
        }  else if(tag == 'SELECT') {
            self.feature.find('option').each(function () {
                var val_id = $(this).val();
                if(!active || (!$(this).hasClass(self.getHideClass(1)) && !$(this).hasClass(self.getHideClass(2)))) {
                    if(!sort) {
                        values[val_id] = $(this).html();
                    }  else {
                        values[index] = {'id': val_id, 'value': $(this).html()};
                        index++;
                    }
                }
            });
        }
        return values;
    },
    getSimilarValues:function(value_id) {
        value_id = value_id || this.getValue();
        var f_values = this.getValues(false, true);
        var feature_values = [];
        var c = 0;
        var val_index = 0;
        // Делаем массив значений, по нему будем менять
        for(var k in f_values) {
            feature_values[c] = f_values[k].id;
            if(value_id == f_values[k].id) {
                val_index = c;
            }
            c++;
        }
        var similar_values = {};
        var ind = 1;
        for (var i = 1;i<=this.count(feature_values);i++) {
            if(feature_values.hasOwnProperty(val_index+i)){
                var val_id = feature_values[val_index+i];
                similar_values[ind] = val_id;
                ind++;
            }
            if(feature_values.hasOwnProperty(val_index-i)){
                var val_id = feature_values[val_index-i];
                similar_values[ind] = val_id;
                ind++;
            }
        }
        return similar_values;
    },
    getValue: function () {
        if(this.getTag()==='INPUT' && this.getElement().attr('type')==='radio') {
            return this.getElement()
                .closest(this.Selectors().sku_feature_container)
                .find('input[type=radio]:checked').val();
        } else {
            return this.getElement().val();
        }
    },
    getTag: function(){
        if(this.getElement().length > 0 ) {
            return this.getElement().get(0).tagName;
        }  else {
            console.log('SaleskuPlugin: no tag in element ');
            console.log($(this));
        }
    },
    setValue: function (value_id) {
        var self = this;
        var tag = self.getTag();
        if(tag == 'INPUT') {
            if(self.getElement().attr('type')=='hidden') {
                self.setHiddenValue(value_id);
            } else if(self.getElement().attr('type')=='radio') {
                self.setRadioValue(value_id);
            } else {
                console.log('SaleskuPlugin: Неизвестный тип характеристики-'+self.getElement().attr('type')+";\n");
            }
        }  else if(tag == 'SELECT') {
            self.setSelectValue(value_id);
        } else {
            console.log('SaleskuPlugin: Неизвестный тип характеристики-'+self.getElement().attr('type')+";\n");
        }
    },
    setHiddenValue: function(value_id){
        var self = this;
        self.getElement().val(value_id);
        self.setButtonsValue(value_id);
    },
    setRadioValue: function(value_id) {
        var self = this;
        self.getElement()
            .closest(this.Selectors().sku_feature_container).find('input[type=radio]').prop('checked', false).removeAttr('checked', false);
        //self.getElement().find('[value="' + value_id + '"]').attr('checked','checked').prop('checked', true);
        this.feature = self.getElement()
            .closest(this.Selectors().sku_feature_container)
            .find('input[value="' + value_id + '"]');
        this.feature.attr('checked', 'checked').prop('checked', true);
        //self.getElement().val(value_id);
        self.setButtonsValue(value_id);
    },
    setSelectValue: function(value_id) {
        var self = this;
        self.getElement().val(value_id);
        self.getElement().find('option[value="' + value_id + '"]')
            .removeClass(self.getHideClass(1)).removeClass(self.getHideClass(2));
        if(self.getButtons().length>0) {
            self.setButtonsValue(value_id);
        }
    },
    setButtonsValue: function(value_id) {
        var self = this;
        self.getButtons().each(function ()  {
            var val_id = self.getButtonValue($(this));
            var button = $(this);
            if(value_id == val_id) {
                self.setButtonState(button, 1);
            } else {
                self.setButtonState(button, 0);
            }
        });
    },
    /* state int 0-1-2 */
    setButtonState: function(button, state) {
        var self = this;
        if(state===1) {
            button
                .addClass(this.Selectors().sku_feature_value_class_active)
                .removeClass(self.getHideClass(1))
                .removeClass(self.getHideClass(2));
        } else if(state===0) {
            button.removeClass(this.Selectors().sku_feature_value_class_active);
        }
    },
    hideButton: function(button, type) {
        var self = this;
        setTimeout(
            function(){
                button
                    .removeClass(self.getHideClass(1))
                    .removeClass(self.getHideClass(2))
                    .addClass(self.getHideClass(type));
            }
            ,10);
    },
    showValue: function (value_id) {
        this.hideValue(value_id, 0);
    },
    hideValue: function(value_id, type) {
        var self = this;
        var tag = self.getTag();
        if(tag == 'INPUT') {
            // возможно кто-то сделает на чистых Radio, тогда добавлю
        } else if(tag == 'SELECT') {
            self.feature.find('option[value="'+value_id+'"]')
                .removeClass(self.getHideClass(1))
                .removeClass(self.getHideClass(2))
                .addClass(self.getHideClass(type));
        }
        self.getButtons().each(function () {
            var val_id = self.getButtonValue($(this));
            if(value_id == val_id) {
                self.hideButton($(this), type);
                return;
            }
        });
    },
    getHideClass: function (type) {
        if(type == 1) {
            return this.Selectors().sku_feature_value_class_grey;
        } else if(type == 2) {
            return this.Selectors().sku_feature_value_class_hide;
        }
        return '';
    },
    count:function (mixed_var, mode) {
        var key, cnt = 0;
        if (mode == 'COUNT_RECURSIVE')mode = 1;
        if (mode != 1)mode = 0;
        for (key in mixed_var) {
            cnt++;
            if (mode == 1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object)) {
                cnt += this.count(mixed_var[key], 1)
            }
        }
        return cnt;
    }
};
// Класс поиска артикулов, умное переключение артикулов
function saleskuPluginFindSku (product_elements, sku_features, options) {
    this._elements = product_elements;
    this.form =  product_elements.Form();
    this.sku_features = sku_features;
    this.features = {};
    this.debug = 0;
    this.sku_variants = {};

    this.smart_sku = 0;
    this.smart_sku_replace = 0;
    this.smart_sku_hard_hide_type = 0;
    this.smart_sku_hide_non_existent_type = 0;
    this.smart_sku_hide_not_available_type = 0;
    this.smart_sku_hide_single_feature = 0;
    this.smart_sku_hide_multi_feature = 0;
    /*  Скрытие по минимальной цене нужно делать на беке
      this.smart_sku_hide_price = 1;
      this.smart_sku_hide_min_price = 5000;
      */
    this.smart_sku_hide_changed_feature_not_available = 0;

    this.smart_sku_class_grey = '';
    this.smart_sku_class_hide = '';
    this.debug_message = '';
    this.features_values_variants = false;
    /*
     *  'smart_sku'                         => 1, // Общая настройка
     'smart_sku_replace'                 => 1, // Менять ли артикул на доступный
     'smart_sku_hide_single_feature'     => 1, // Скрывать характеристику если всего один вариант выбора
     'smart_sku_hide_not_available_type' => 1, // Тип скрытия характеристик недоступного артикула
     'smart_sku_hide_non_existent_type'  => 1, // Тип скрытия характеристик несуществующего артикула
     'smart_sku_hide_style'              => 0,  // Свои классы для  скрытия
     'smart_sku_class_grey'              => '', // Класс частичного скрытия
     'smart_sku_class_hide'	            => '', // Класс полного скрытия
     */
    // Ставим опции
    for(var k in options) {
        if(options.hasOwnProperty(k)) {
            this[k] = options[k];
        }
    }
    this.algorithm = 2;

    $.saleskuPluginProductElements.Selectors().sku_feature_value_class_grey = this.getHideClass(1);
    $.saleskuPluginProductElements.Selectors().sku_feature_value_class_hide  = this.getHideClass(2);
    // Сетим характеристики
    var self = this;
    this.getElements().SkuFeatures().each(function () {
        var feature = new saleskuPluginProductSkuFeature($(this));
        self.features[self.getFeatureSid(feature.getId())] = feature;
    });
};
saleskuPluginFindSku.prototype = {
    'getElements': function () {
        return this._elements;
    },
    'getSku' : function (feature_obj) {
        var self = this;
        var sku = false;
        var sku_key = self.getSkuByValues();
        if(sku_key) {
            sku = self.sku_features[sku_key];
            if(self.debug=='1') {
                self.debug_message ='Salesku_Plugin:findSku:getSku '+"\n"+'Артикул Найден - "'+sku_key+'"'+";\n";
                self.debug_message +=var_dump(sku)+"\n";
                self.debug_message +=var_dump(self.sku_features)+"\n";
                if(!sku.available && self.smart_sku_replace=='1'){
                    self.debug_message +='Salesku_Plugin:findSku:getSku Артикул не доступен для покупки;'+"\n";
                }
            }
        }  else {
            if(self.debug=='1') {
                self.debug_message ='Salesku_Plugin:findSku:getSku Артикул не существует;'+"\n";
            }
        }
        if (!sku || (!sku.available && self.smart_sku_replace=='1')) {
            sku = self.findSku(feature_obj);
        } else {
            self.hideSkuFeaturesValues(feature_obj);
        }
        if(self.debug=='1') {
            console.log(self.debug_message);
        }
        return sku;
    },
    'getFeatureSid' : function (id) {
        return "f"+id;
    },
    'getFeatureId' : function (id) {
        return id.replace('f','');
    },
    'findSku': function (changed_feature_obj) {
        var self = this;
        if(self.debug=='1') {
            self.debug_message +='Запущен поиск артикула...;'+"\n";
        }
        changed_feature_obj = new saleskuPluginProductSkuFeature(changed_feature_obj);
        var value_id = changed_feature_obj.getValue();
        var changed_feature_id = changed_feature_obj.getId();
        var features_values_variants = this.getFeaturesValuesVariants(true);
        var features = features_values_variants[value_id];
        var sku_key = false;
        if(self.count(features)>0) {
            var variants = {};
            for (var k in features) {
                for (var fk in features[k]) {
                    for (var sku_d in features[k][fk]) {
                        if (typeof(variants[sku_d]) != 'object') {
                            variants[sku_d] = {}
                        }
                        variants[sku_d][k] = fk;
                    }
                }
            }
            var max_similar = {
                'count_features':0,
                'features_identical':0,
                'similar_offset': 100,
                'feature_available':0
            };
            var count_sku_features = {};
            for (var vk in variants) {
                var variant = variants[vk];
                var features_identical = 0;
                var similar_offset = 0;
                var available = 0;
                var sku = self.sku_features[vk];
                for(var id in self.features) {
                    var feature_obj = self.features[id];
                    var feature_id = feature_obj.getId();
                    var feature_value = feature_obj.getValue();
                    if (typeof(variant[feature_id]) !== 'undefined') {
                        if (variant[feature_id] == feature_value) {
                            features_identical++;
                        } else {
                            var similar_values = feature_obj.getSimilarValues(feature_obj.getValue());
                            for(var offset in similar_values) {
                                if(similar_values[offset]==variant[feature_id]) {
                                    similar_offset += parseInt(offset);
                                }
                            }
                        }
                    }
                }
                if(variant[changed_feature_obj.getId()] == value_id && (self.smart_sku_replace=='0' || sku.available)) {
                    available = 1;
                }
                var count_features = this.count(variant);
                count_sku_features[vk] = {};
                count_sku_features[vk]['available'] = available;
                count_sku_features[vk]['similar_offset'] = similar_offset;
                count_sku_features[vk]['count_features'] = count_features;
                count_sku_features[vk]['features_identical'] = features_identical;
                if(self.debug=='1') {
                    count_sku_features[vk]['sku_available'] = sku.available;
                    count_sku_features[vk]['feature_id'] = changed_feature_id;
                    count_sku_features[vk]['value_id'] = variant[changed_feature_id];
                }
                if ((self.smart_sku_replace=='0' || sku.available) &&
                    similar_offset     <= max_similar['similar_offset'] &&
                    available          >= max_similar['feature_available'] &&
                    count_features     >= max_similar['count_features'] &&
                    features_identical >= max_similar['features_identical'] ) {
                    max_similar['count_features'] = count_features;
                    max_similar['features_identical'] = features_identical;
                    max_similar['similar_offset'] = similar_offset;
                    max_similar['feature_available'] = available;
                }
            }
            for (var ck in count_sku_features) {
                if (count_sku_features[ck]['available'] === max_similar['feature_available']
                    && count_sku_features[ck]['similar_offset'] <= max_similar['similar_offset']
                    && count_sku_features[ck]['count_features'] === max_similar['count_features']
                    && count_sku_features[ck]['features_identical'] === max_similar['features_identical']
                ) {
                    sku_key = ck;
                    break;
                }
            }
            if(self.debug == '1') {
                self.debug_message += 'Salesku:findsku '+"\n"+
                    'Варианты '+var_dump(count_sku_features)+"\n"+
                    'Максимальная схожесть ' +var_dump(max_similar)+"\n"+
                    'Артикул '+sku_key+"\n\n\n";
            }
        } else {
            sku_key = self.getSkuByValues();
        }
        if(!sku_key) {
            var similar_values = changed_feature_obj.getSimilarValues();
            for(var k in similar_values) {
                changed_feature_obj.setValue(similar_values[k]);
                if(changed_feature_obj.getValue()!= similar_values[k]) {
                    self.debug_message +='Salesku_plugin:findsku Не установлено новое значение характеристики: '+similar_values[k]+'!;'+"\n";
                }
                var sku_key_temp = self.getSkuByValues();
                if(sku_key_temp) {
                    sku_key = sku_key_temp;
                    break;
                }
            }
        }
        if (sku_key) {
            if(self.debug==1) {
                self.debug_message +='Salesku_plugin:findsku Артикул найден: "'+sku_key+'";'+"\n";
            }
            var sku_features = self.getSkuKeyFeaturesValues(sku_key);
            for(var id in sku_features) {
                if(self.features.hasOwnProperty(self.getFeatureSid(id))) {
                    self.features[self.getFeatureSid(id)].setValue(sku_features[id]);
                    if(self.features[self.getFeatureSid(id)].getValue() != sku_features[id]) {
                        self.debug_message +='Salesku_plugin:findsku Не установлено новое значение характеристики: '+sku_features[id]+'!;'+"\n";
                    }
                }
            }
            sku = self.sku_features[sku_key];
            self.getElements().SkuFeatures().change();
        } else {
            self.debug_message +='Salesku_plugin: Ни одного доступного артикула не найдено!;'+"\n";
        }
        self.hideSkuFeaturesValues(changed_feature_obj);
        return sku;
    },
    'getSkuByValues': function() {
        var self = this;

        var sku_key = false;

        var variants = self.getSkuVariantsByValues(self.getFeaturesValues(true));
        for(var s_key in self.sku_features) {
            var sku = self.sku_features[s_key];
            if(variants.hasOwnProperty(s_key)) {
                if(sku.available || (self.smart_sku_replace=='0')) {
                    sku_key = s_key;
                    break;
                }
            }
        }
        return sku_key;
    },
    'getAlgorithm' :function () {
        return this.algorithm;
    },
    /**
     *
     * @param Object features_values {feature_id: value_id,....}
     * @return {*}
     */
    'getSkuVariantsByValues' :function (features_values, algorithm) {
        algorithm = algorithm||this.getAlgorithm();
        var arr = [];
        var i = 0;
        for(var id in features_values) {
            arr[i++] = id + ':' + features_values[id] + ';'
        }
        this.sku_variants = {};
        if(algorithm == 2) {
            this.generateSkuVariants(arr);
        } else {
            var def_sku = arr.join('');
            this.sku_variants[def_sku] = def_sku;
        }
        return this.sku_variants
    },
    'getSkuVariants' :function (arr) {
        this.sku_variants = {};
        if(this.getAlgorithm() == 2) {
            this.generateSkuVariants(arr);
        } else {
            var def_sku = arr.join('');
            this.sku_variants[def_sku] = def_sku;
        }
        return this.sku_variants
    },
    /**
     * @return Object
     */
    'getFeaturesValues':function(key_by) {
        if(key_by && (key_by !== 'value' && key_by !==' feature') ){
            key_by = 'feature';
        }
        var features_values = key_by? {}:[];
        var i = 0;
        for(var sid in this.features) {
            var value_id = this.features[sid].getValue();
            if(!key_by) {
                features_values[i++] = {
                    feature_id:this.getFeatureId(sid),
                    value_id: value_id
                };
            } else {
                if(key_by === 'value') {
                    features_values[value_id] = this.getFeatureId(sid);
                } else {
                    features_values[this.getFeatureId(sid)] = value_id;
                }
            }

        }
        return features_values;
    },
    'generateSkuVariants':function (arr, temp, level) {
        if (typeof(level) != 'number') {
            level = 0
        }
        for (var k in arr) {
            if (level < 1) {
                temp = ''
            }
            var arr1 = arr.slice();
            delete arr1[k];
            if (this.count(arr1) > 0) {
                this.generateSkuVariants(arr1, temp + '' + arr[k], level + 1)
            } else {
                this.sku_variants[temp + '' + arr[k]] = temp + '' + arr[k]
            }
        }
    },
    'getHideClass' : function (type) {
        if(typeof ($.saleskuPluginProductsPool) == 'object' && $.saleskuPluginProductsPool.hasOwnProperty('getFeatureHideClass')) {
            return $.saleskuPluginProductsPool.getFeatureHideClass(type);
        }
        return 'salesku_plugin-feature_hide';
    },
    'hideSkuFeaturesValues': function (changed_feature_obj) {
        var self = this;
        if(!(changed_feature_obj instanceof saleskuPluginProductSkuFeature)) {
            changed_feature_obj = new saleskuPluginProductSkuFeature(changed_feature_obj);
        }
        var features_values_variants = this.getFeaturesValuesVariants(false, changed_feature_obj.getValue());
        if(this.smart_sku_hide_non_existent_type == '0' && this.smart_sku_hide_not_available_type =='0') {
            return;
        }
        if(self.count(self.features)==1) {
            // Скрываем значения несуществующих артикулов
            var count_available_sku = 0;
            for(var id in self.features) {
                var f_values = self.features[id].getValues();
                for(var k in f_values) {
                    var sku = false;
                    if(self.sku_features.hasOwnProperty(self.getFeatureId(id)+':'+ k+';')){
                        sku = self.sku_features[self.getFeatureId(id)+':'+ k+';'];
                    }
                    if(!sku && self.smart_sku_hide_non_existent_type !='0') {
                        self.features[id].hideValue(k,  self.smart_sku_hide_non_existent_type);
                    } else if(sku && self.smart_sku_hide_not_available_type !='0' && !sku.available) {
                        self.features[id].hideValue(k, self.smart_sku_hide_not_available_type);
                    } else {
                        count_available_sku++;
                    }
                }
            }
            // Скрытие характеристики, если доступен только 1 вариант и нельзя выбрать другие
            if(count_available_sku < 2 && parseInt(self.smart_sku_hide_single_feature) == 1 && parseInt(self.smart_sku_replace)==1 && self.smart_sku_hide_not_available_type !='0') {
                for(var id in self.features) {
                    self.features[id].hide();
                }
            } else {
                for(var id in self.features) {
                    self.features[id].show();
                }
            }
        }
        else if(self.count(self.features) > 1) {

            if(parseInt(this.smart_sku_hard_hide_type)<1) {
                for(var id in self.features) {
                    var value_id = self.features[id].getValue();
                    if (features_values_variants.hasOwnProperty(value_id)) {
                        var features = features_values_variants[value_id];
                        for (var k in features) {
                            if (k != self.getFeatureId(id)) {
                                var feature_obj = self.getSkuFeature(k);
                                var available_sku_values = 0;
                                var values = features[k];
                                var feature_values = feature_obj.getValues();
                                for(var val_id in feature_values) {
                                    if (!values.hasOwnProperty(val_id) && parseInt(self.smart_sku_hide_non_existent_type) > 0) {
                                        feature_obj.hideValue(val_id, self.smart_sku_hide_non_existent_type);
                                    } else {
                                        var sku_available = false;
                                        for (var sku_key in values[val_id]) {
                                            if (values[val_id][sku_key]==true) {
                                                sku_available = true;
                                            }
                                        }
                                        if (sku_available == false && parseInt(self.smart_sku_hide_not_available_type) > 0) {
                                            feature_obj.hideValue(val_id, self.smart_sku_hide_not_available_type);
                                        } else {
                                            available_sku_values++;
                                            feature_obj.showValue(val_id);
                                        }
                                    }
                                }
                                if (available_sku_values < 2 && parseInt(self.smart_sku_hide_multi_feature) == 1 && parseInt(self.smart_sku_replace) == 1) {
                                    feature_obj.hide();
                                } else {
                                    feature_obj.show();
                                }
                            }
                        }
                    } else {
                        self.features[id].hideValue(value_id, self.smart_sku_hide_not_available_type);
                    }
                }
            }
            else {
                features_values_variants = features_values_variants[changed_feature_obj.getValue()];
                var features_values = this.getFeaturesValues(true);

                for(var sid in self.features) {
                    var feature_obj = self.features[sid];
                    var feature_values = feature_obj.getValues();
                    if(self.getFeatureId(sid) == changed_feature_obj.getId()) {
                        if(!self.smart_sku_hide_changed_feature_not_available) {
                            for (var value_id in feature_values) {
                                feature_obj.showValue(value_id);
                            }
                        }
                        continue;
                    }

                    var feature_values_variants = features_values_variants[self.getFeatureId(sid)];

                    for (var value_id in feature_values) {
                        var clone_features_values =  Object.assign({}, features_values);
                        clone_features_values[self.getFeatureId(sid)] = value_id;
                        var values_skus_variants = this.getSkuVariantsByValues(clone_features_values, 2);
                        if (feature_values_variants.hasOwnProperty(value_id)) {
                            var skus = feature_values_variants[value_id];
                            var sku_available = false;
                            for (var sku_key in skus) {
                                if (values_skus_variants.hasOwnProperty(sku_key) &&  skus[sku_key]==true) {
                                    sku_available = true;
                                }
                            }
                            if (!sku_available && parseInt(self.smart_sku_hide_not_available_type) > 0) {
                                feature_obj.hideValue(value_id, self.smart_sku_hide_not_available_type);
                            } else {
                                feature_obj.showValue(value_id);
                            }
                        }else {
                            self.features[sid].hideValue(value_id, self.smart_sku_hide_not_available_type);
                        }
                    }
                }
            }
        }
    },
    'getSkuFeature': function (id){
        id  = this.getFeatureId(id);
        if(this.features.hasOwnProperty(this.getFeatureSid(id))) {
            return this.features[this.getFeatureSid(id)];
        }
    },
    'getFeaturesValuesVariants': function (include, current_feature_value_id) {
        include = include||false;
        current_feature_value_id = current_feature_value_id || false;

        if(!this.features_values_variants || current_feature_value_id) {
            this.features_values_variants = {};
            var features_values_variants = {};
            var features_values_variants_all = {};
            for (var sku_id in this.sku_features) {
                var available = false;
                if(this.sku_features.hasOwnProperty(sku_id) && this.sku_features[sku_id].available) {
                    available = true;
                }
                var features = this.getSkuKeyFeaturesValues(sku_id);
                for (var feature_id in features) {
                    var feature_value = features[feature_id];
                    if (typeof(features_values_variants[feature_value]) !== 'object') {
                        features_values_variants[feature_value] = {};
                    }
                    if (typeof(features_values_variants_all[feature_value]) !== 'object') {
                        features_values_variants_all[feature_value] = {};
                    }
                    for (var feature_val_id in features) {
                        var feature_val_value = features[feature_val_id];
                        if (feature_id != feature_val_id) {
                            if (typeof(features_values_variants[feature_value][feature_val_id]) != 'object') {
                                features_values_variants[feature_value][feature_val_id] = {};
                            }
                            if (typeof(features_values_variants[feature_value][feature_val_id][feature_val_value]) != 'object') {
                                features_values_variants[feature_value][feature_val_id][feature_val_value] = {}
                            }
                            features_values_variants[feature_value][feature_val_id][feature_val_value][sku_id] = available;
                        }
                        if (typeof(features_values_variants_all[feature_value][feature_val_id]) != 'object') {
                            features_values_variants_all[feature_value][feature_val_id] = {};
                        }
                        if (typeof(features_values_variants_all[feature_value][feature_val_id][feature_val_value]) != 'object') {
                            features_values_variants_all[feature_value][feature_val_id][feature_val_value] = {}
                        }
                        features_values_variants_all[feature_value][feature_val_id][feature_val_value][sku_id] = available;
                    }
                }
            }
            this.features_values_variants['other'] = features_values_variants;
            this.features_values_variants['all'] = features_values_variants_all;
        }
        if(include) {
            return  this.features_values_variants['all'];
        }
        return this.features_values_variants['other'];
    },
    'getSkuKeyFeaturesValues': function (sku_key) {
        var features = sku_key.split(';');
        var return_features = {};
        for (var fk in features) {
            if (features[fk] != '') {
                var feature_arr = features[fk].split(':');
                var feature_id = feature_arr[0];
                return_features[feature_id] = feature_arr[1];
            }
        }
        return return_features
    },
    'count' :function (mixed_var, mode) {
        var key, cnt = 0;
        if (mode == 'COUNT_RECURSIVE')mode = 1;
        if (mode != 1)mode = 0;
        for (key in mixed_var) {
            cnt++;
            if (mode == 1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object)) {
                cnt += this.count(mixed_var[key], 1)
            }
        }
        return cnt
    }
};
// Альтернативный класс Product
function saleskuPluginProduct(selector_id, options) {
    /* Класс выборки элементов продукта */
    this.__elements = new saleskuPluginProductElements($(selector_id));
    this.debug = 0;
    this.find_Sku = null;
    this.features = {};
    this.smartsku = {};
    this.related_sku = 0;
    this.sku_image = '';
    this.skus = {};
    this.sku = {}; // skus[sku_id] default sku
    this.uid = 0;
    this.type_id = 0;
    this._parent = null; // элеменкт контейнера продуктов
    this.form = this.getElements().Form();

    for (var k in options) {
        this[k] = options[k];
    }
}
saleskuPluginProduct.prototype.binds = {
    'fly_options_notice' : function(self) {
        var sel = self.getElements().Selectors();
        $(document).on('click','.salesku_options.fly select[name="salesku_skus"]',function () {
            $(this).closest('form').addClass('salesku_plugin-selected-sku');
        });
        self.getForm().find(sel.sku_feature_container).find(sel.sku_feature_button).bind('click', function () {
            self.getForm().addClass('salesku_plugin-selected-sku');
        });
        self.getForm().find(sel.sku_feature).bind('click', function () {
            self.getForm().addClass('salesku_plugin-selected-sku');
        });
        self.getForm().find(sel.skus_button).closest('label').bind('mouseup', function () {
            self.getForm().addClass('salesku_plugin-selected-sku');
        });
    },
    'sku_features': function(self) {
        var sel = self.getElements().Selectors();
        if(self.getForm().find(sel.sku_feature).length>0) {
            self.getForm().find(sel.sku_feature).bind('change', function () {
                self.setSkuByFeature($(this));
            });
            self.getForm().find(sel.sku_feature+":first").change();
        }
    },
    'sku_features_inline': function(self) {
        var sel = self.getElements().Selectors();
        if(self.getForm().find(sel.sku_feature_container).length>0) {
            self.getForm().find(sel.sku_feature_container).find(sel.sku_feature_button).bind('click', function () {
                var feature_obj = $(this).closest(sel.sku_feature_container).find(sel.sku_feature);
                var feature = new saleskuPluginProductSkuFeature(feature_obj);
                feature.setValue(feature.getButtonValue($(this)));
                feature.getElement().change();

                return false;
            });
        }
    },
    'skus': function(self) {
        var sel = self.getElements().Selectors();
        var smartsku = self.smartsku;
        if(smartsku.smart_sku && self.getForm().find(sel.skus_button).length>0 && self.hasOwnProperty('skus') && self.count(self.skus)>0) {
            self.getForm().find(sel.skus_button).bind('click', function () {
                var sku_id = $(this).val();
                var sku = false;
                if(self.skus.hasOwnProperty(sku_id)) {
                    sku = self.skus[sku_id];
                    if(smartsku.smart_sku_replace=='1' && !sku.sku_available) {
                        // Ставим доступный артикул
                        var similar_skus = self.getSimilarSkus(sku_id);
                        for (var k in similar_skus) {
                            var sku_id = similar_skus[k];
                            if(self.skus.hasOwnProperty(sku_id)) {
                                sku = self.skus[sku_id];
                                if(sku.sku_available) {
                                    self.getForm().find(sel.skus_button).removeAttr('checked');
                                    var el = false;
                                    self.getForm().find(sel.skus_button).each(function () {
                                        if($(this).val()==sku_id) {
                                            el = $(this);
                                        }
                                    });
                                    self.getForm().find(sel.skus_button).prop('checked', false).attr('checked',false);
                                    setTimeout(function() {
                                        if(typeof(el)=='object') {
                                            el.prop('checked', true);
                                            el.trigger('change');
                                        }
                                    },50);
                                    break;
                                }
                            }
                        }
                    }
                }
                self.skuFunctions(sku);
                if(sku) {
                    self.setSku(sku);
                }
            });
        }
        //
        var $initial_cb =  self.getForm().find(".skus input[type=radio]:checked:not(:disabled)");
        if (!$initial_cb.length) {
            $initial_cb =  self.getForm().find(".skus input[type=radio]:not(:disabled):first").prop('checked', true).click();
        }
        $initial_cb.change();
        $initial_cb.click();
        
        // Скрываем недоступные
        self.getForm().find(sel.skus_button).each(function () {
            var sku_id = $(this).val();
            if(self.skus.hasOwnProperty(sku_id)) {
                var sku = self.skus[sku_id];
                if(!sku.sku_available) {
                  self.getElements().SkuContainer($(this)).addClass(self.getHideClass(smartsku.smart_sku_hide_not_available_type));
                    self.getElements().Skus().parent().find('.salesku_skus').find('[name="salesku_skus"]').find('option[value="'+sku_id+'"]').addClass(self.getHideClass(smartsku.smart_sku_hide_not_available_type));

                }
            }
        });
    },
    'services_checkbox': function(self) {
        self.getForm().find(".services input[type=checkbox]").on('click', function () {
            var obj = self.getForm().find('select[name="service_variant[' + $(this).val() + ']"]');
            if (obj.length) {
                if ($(this).is(':checked')) {
                    obj.removeAttr('disabled');
                } else {
                    obj.attr('disabled', 'disabled');
                }
            }
            self.updatePrice();
        });
    },
    'service_variants': function(self) {
        var sel = self.getElements().Selectors();
        self.getForm().find(sel.services+' '+sel.services_variants).bind('change', function () {
            self.updatePrice();
        });
    },
    'select_skus': function (self) {
        $('.salesku_skus_'+self.getUid()).find('[name="sku_id"]').change(function () {
            var container = $(this).closest('.skus').parent();
            if( container.find('[name="salesku_skus"]').val() != $(this).val()) {
                container.find('[name="salesku_skus"]').val($(this).val());
            }
        });
    },
    'change_quantity': function(self) {
        if(self.getElements().Quantity().length>0) {
            self.getElements().Quantity().change(function(){
                self.updatePrice();
            });
        }
    },
};
saleskuPluginProduct.prototype.before_binds = {};
saleskuPluginProduct.prototype.after_binds = {};
saleskuPluginProduct.prototype.bind = function (binds) {
    var self = this;
    for(var k in binds) {
        var func = binds[k];
        if(typeof func === 'function') {
            func(self);
        }
    }
};


saleskuPluginProduct.prototype.skuBinds = {};
saleskuPluginProduct.prototype.skuFunctions =  function (sku) {
    var self = this;
    if(typeof(self.skuBinds) == 'object' && self.count(self.skuBinds) > 0) {
        for(var id in self.skuBinds) {
            var func = self._bind(self.skuBinds[id],{'sku': sku, 'product': self});
            if (typeof func === "function") {
                func();
            }
        }
    }
};
saleskuPluginProduct.prototype._bind = function(func, context /*, args*/) {
    var bindArgs = [].slice.call(arguments, 2); // (1)
    function wrapper() {                        // (2)
        var args = [].slice.call(arguments);
        var unshiftArgs = bindArgs.concat(args);  // (3)
        return func.apply(context, unshiftArgs);  // (4)
    }
    return wrapper;
};
saleskuPluginProduct.prototype.init = function (active) {
    // Если один артикул, ставим ему data-price, иначе мы просто не отдаем в массив данные артикула
    if(this.hasOwnProperty('sku') && this['sku'].hasOwnProperty('price')) {
        if(this.getElements().Price()) {
            this.getElements().Price().data('price', parseFloat(this['sku']['price']));
        }
    }
    if(this.debug =='1') {
        this._debug();
    }
    this.form_action_data = this.getFormIndicator();
    if(active) {
        this.on();
    } else {
        this.off();
    }
};
saleskuPluginProduct.prototype.getDebugMessage = function (index, flag) {
    var self = this;
    var el = self.getElements();
    var sel = self.getElements().Selectors();
    var messages = {
        '-form_action_indicator': 'Индикатор отправки формы не найден ('+sel.form_action_indicator+')',
        '+form_action_indicator': 'Найден',
        '-skus': 'Не найден внутри тега форм контейнер характеристик ('+sel.skus+')',
        '+skus': 'Найден',
        '-skus_button': 'Не найдены внутри тега форм элементы показа артикулов ('+sel.skus_button+')',
        '+skus_button': 'Найдены',
        '-options': 'Не найден внутри тега форм контейнер характеристик ('+sel.sku_options+')',
        '+options': 'Найден',
        '-sku_feature': 'Не найдены внутри тега форм элементы показа характеристик ('+sel.sku_feature+')',
        '+sku_feature': 'Найден',
        '-sku_feature_data_id': 'Не найдены атрибуты id арактеристик ('+sel.sku_feature_element_data_id+')',
        '+sku_feature_data_id': 'Найден',
        '-sku_feature_inline_container': 'Не найдены контейнеры кнопок характеристик внутри тега форм ('+sel.sku_feature_container+')',
        '+sku_feature_inline_container': 'Найден',
        '-sku_feature_button': 'Не найдены кнопки характеристик ('+sel.sku_feature_button+')',
        '+sku_feature_button': 'Найден',
        '-image': 'Не найден Тег Img картинки товара ('+sel.image+')',
        '+image': 'Найден',
        '-price': 'Не найден элемент цены товара ('+sel.price+')',
        '+price': 'Найден',
        '-cart_button': 'Не найдена кнопка "В корзину"',
        '+cart_button': 'Найден',
        '-added2cart': 'Не найден элемент инфо блока ('+sel.added2cart+')',
        '+added2cart': 'Найден',
        '-compare_price': 'Не найден элемент ('+sel.compare_price+')',
        '+compare_price': 'Найден',
        'product': ' продукт не имеет вариаций для выбора артикулов'
    };
    var key = (flag==1) ?'+':'-';
    if(messages.hasOwnProperty(key+index)) {
        return key+'Salesku_Plugin:'+index+' '+messages[key+index]+';'+"\n";
    }
    return 'Salesku_Plugin:'+index+' Не известная ошибка;'+"\n";
};
saleskuPluginProduct.prototype._debug = function ()  {
    var self = this;
    var el = self.getElements();
    var sel = self.getElements().Selectors();
    var count_bags = 0;
    var message = '';
    if(self.count(self.skus)>1 || self.count(self.features)>0) {
        if(self.getForm().attr(sel.form_action_indicator)==undefined) {
            message += self.getDebugMessage('form_action_indicator', 0);
            count_bags++;
        } else {
            message += self.getDebugMessage('form_action_indicator', 1);
        }
        if(self.count(self.skus)>1) {
            if(self.getForm().find(sel.skus).length<1) {
                message += self.getDebugMessage('skus', 0);
                count_bags++;
            } else {
                message += self.getDebugMessage('skus', 1);
            }
            if(self.getForm().find(sel.skus_button).length<1){
                message += self.getDebugMessage('skus_button', 0);
                count_bags++;
            } else {
                message += self.getDebugMessage('skus_button', 1);
            }
        } else if(self.count(self.features)>0) {
            if(self.getForm().find(sel.sku_options).length<1) {
                message += self.getDebugMessage('options', 0);
                count_bags++;
            } else {
                message += self.getDebugMessage('options', 1);
            }
            if(self.getForm().find(sel.sku_feature).length<1) {
                message += self.getDebugMessage('sku_feature', 0);
                count_bags++;
            } else {
                message += self.getDebugMessage('sku_feature', 1);
                var inline_count = 0;
                var feature_data_id = true;
                self.getForm().find(sel.sku_feature).each(function () {
                    if($(this).data(sel.sku_feature_element_data_id)==undefined) {
                        feature_data_id = false;
                    }
                    if($(this).get(0).tagName=='INPUT') {
                        inline_count++;
                    }
                });
                if(!feature_data_id) {
                    message += self.getDebugMessage('sku_feature_data_id', 0);
                    count_bags++;
                } else {
                    message += self.getDebugMessage('sku_feature_data_id', 1);
                }
                if(self.getForm().find(sel.sku_feature_container).length<inline_count) {
                    message += self.getDebugMessage('sku_feature_inline_container', 0);
                    count_bags++;
                } else {
                    message += self.getDebugMessage('sku_feature_inline_container', 1);
                    if(inline_count>0 && self.getForm().find(sel.sku_feature_container).find(sel.sku_feature_button).length<1){
                        message += self.getDebugMessage('sku_feature_button', 0);
                        count_bags++;
                    } else {
                        message += self.getDebugMessage('sku_feature_button', 1);
                    }
                }
            }
        }
    } else {
        message += self.getDebugMessage('product', 0);
    }
    if(el.Image().length <1) {
        message += self.getDebugMessage('image', 0);
        count_bags++;
    } else {
        message += self.getDebugMessage('image', 1);
    }

    if(el.Price().length <1) {
        message += self.getDebugMessage('price', 0);
        count_bags++;
    } else {
        message += self.getDebugMessage('price', 1);
    }

    if(el.CartButton().length <1) {
        message += self.getDebugMessage('cart_button', 0);
        count_bags++;
    } else {
        message += self.getDebugMessage('cart_button', 1);
    }

    if(el.added2cart().length<1) {
        message += self.getDebugMessage('added2cart', 0);
    } else {
        message += self.getDebugMessage('added2cart', 1);
    }
    if(el.ComparePrice().length <1) {
        message += self.getDebugMessage('compare_price', 0);
    } else {
        message += self.getDebugMessage('compare_price', 1);
    }
    message += 'Salesku_Plugin:smart_sku_class_grey '+self.smartsku.smart_sku_class_grey+";\n";
    message += 'Salesku_Plugin:smart_sku_class_hide '+self.smartsku.smart_sku_class_hide+";\n";
    message += var_dump(this.smartsku)+";\n";
    if(count_bags<1){
        message = 'Продукт '+self.getUid()+':Ок'+"\n"+message;
    } else {
        message = 'Продукт '+self.getUid()+': ---------------------!!!!!!!!!!!!!!!!! Багов найдено '+count_bags+' !!!!!!!!!!!!!!!!!!-----------------'+"\n"+message;
    }
    $.saleskuPluginProductsPool.setLogMessage(message);
    console.log(message+"\n");

};
saleskuPluginProduct.prototype.setParent = function ($element)  {
    this._parent = $element;
};
saleskuPluginProduct.prototype.getParent = function ()  {
    if(this._parent == null) {
        this._parent = this.getElements().Parent();
    }
    return this._parent;
};
saleskuPluginProduct.prototype.getProductListClass = function ()  {
    var _parent = this.getParent();
    var classes = _parent.attr('class').split(' ');
    for(var k in classes) {
        var _class = classes[k];
        var Regex = new RegExp('salesku_plugin_product_list-');
        if(Regex.test(_class)){
            return _class;
        }
    }
    return '';

};
saleskuPluginProduct.prototype.show = function () {
    this.getElements().OptionsContainer().show();
    this.getElements().Options().show();
    if(!this.getElements().Skus().hasClass('salesku_skus_select')) {
        this.getElements().Skus().show();
    } else {
        this.getElements().Skus().parent().find('.salesku_skus').show();
    }
    this.getElements().Services().show();
    this.getElements().Stocks().show();
};
saleskuPluginProduct.prototype.hide = function () {
    this.getElements().OptionsContainer().hide();
    this.getElements().Options().hide();
    this.getElements().Skus().hide();
    if(this.getElements().Skus().hasClass('salesku_skus_select')) {
        this.getElements().Skus().parent().find('.salesku_skus').hide();
    }
    this.getElements().Services().hide();
    this.getElements().Stocks().hide();
};
saleskuPluginProduct.prototype.on = function () {
    this.bind(this.before_binds);
    this.removeFormIndicator();
    this.getElements().root_element.addClass(this.getElements().Selectors().salesku_product_root.replace(/\./g, ''));
    this.show();
    this.bind(this.binds);
    this.bind(this.after_binds);
};

saleskuPluginProduct.prototype.off = function () {
    this.setFormIndicator();
    this.hide();
    this.getElements().root_element.removeClass(this.getElements().Selectors().salesku_product_root.replace(/\./g, ''));
};
saleskuPluginProduct.prototype.getElements = function () {
    return this.__elements;
};
saleskuPluginProduct.prototype.getForm = function () {
    return this.form;
};
saleskuPluginProduct.prototype.getFormIndicator = function () {
    return this.getForm().attr(this.getElements().Selectors().form_action_indicator);
};
saleskuPluginProduct.prototype.removeFormIndicator = function () {
    return this.getForm().removeAttr(this.getElements().Selectors().form_action_indicator);
};
saleskuPluginProduct.prototype.setFormIndicator = function () {
    return this.getForm().attr(this.getElements().Selectors().form_action_indicator , this.form_action_data);
};
saleskuPluginProduct.prototype.getType = function () {
    return this.type_id;
};
saleskuPluginProduct.prototype.getUid = function () {
    return this.uid;
};
saleskuPluginProduct.prototype.isRelatedSku = function () {
    if(this.related_sku==1) {
        return true;
    }
    return false
};
// Поиск артикула по измененной характеристике
saleskuPluginProduct.prototype.findSku = function (feature_obj) {
    if(this.find_Sku==null) {
        this.smartsku['debug'] = this.debug;
        this.find_Sku = new saleskuPluginFindSku(this.getElements(), this.features, this.smartsku);
    }
    return this.find_Sku.getSku(feature_obj);
};
// Установка артикула по значению характеристики
saleskuPluginProduct.prototype.setSkuByFeature = function (feature_obj) {
    var self = this;
    var sku = this.findSku(feature_obj);
    this.skuFunctions(sku);
    if(sku) {
        this.setSku(sku);
        if(typeof ($.saleskuPluginProductsPool) == 'object' && $.saleskuPluginProductsPool.hasOwnProperty('getFeatureHideClass')) {
            if(!$.saleskuPluginProductsPool.current_change_product) {
                $.saleskuPluginProductsPool.changeFeature(feature_obj, self);
            }
        }
    }

};
// Используется только для выбираемых характеристик
saleskuPluginProduct.prototype.setSku = function (sku_data) {
    var sku = false;
    var self = this;
    if(typeof sku_data=='object' && sku_data.hasOwnProperty('id')) {
        var sku = sku_data;
    } else if(typeof sku_data == 'string'){
        if(this.features.hasOwnProperty(sku_data)) {
            sku = this.features[sku_data];
        }
    }
    if (sku) {
        self.setSkuImage(sku);
        self.updateSkuServices(sku.id);
        if (sku.available) {
            this.cartButtonVisibility(true);
            self.updatePrice(sku.price, sku.compare_price);
        } else {
            self.form.find("div.stocks div").hide();
            self.form.find(".sku-no-stock").show();
            self.cartButtonVisibility(false);
        }
    } else {
        self.getElements().Form().find("div.stocks div").hide();
        self.getElements().Form().find(".sku-no-stock").show();
        self.cartButtonVisibility(false);
        self.getElements().ComparePrice().hide();
        self.getElements().Price().empty();
    }
};
// Установка картинки товара из артикула, если картинки нет, вернется основная
saleskuPluginProduct.prototype.setSkuImage = function (sku_data) {
    if(this.sku_image=='1') {
        var image_obj = this.getElements().Image();
        // Сохраняем оригинал
        if(!image_obj.data('salesku-original-image')) {
            image_obj.data('salesku-original-image', this.getElements().OriginalImage());
        }
        if( typeof(sku_data) == 'object' && sku_data.hasOwnProperty('image')) {
            image_obj.attr('src', sku_data.image);
        } else {
            image_obj.attr('src', image_obj.data('salesku-original-image'));
        }
    }
};
// Немного измененные методы продукта
saleskuPluginProduct.prototype.currencyFormat = function (number, no_html) {
    // Format a number with grouped thousands
    //
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +	 bugfix by: Michael White (http://crestidg.com)

    var i, j, kw, kd, km;
    var decimals = this.currency.frac_digits;
    var dec_point = this.currency.decimal_point;
    var thousands_sep = this.currency.thousands_sep;

    // input sanitation & defaults
    if( isNaN(decimals = Math.abs(decimals)) ){
        decimals = 2;
    }
    if( dec_point == undefined ){
        dec_point = ",";
    }
    if( thousands_sep == undefined ){
        thousands_sep = ".";
    }

    i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

    if( (j = i.length) > 3 ){
        j = j % 3;
    } else{
        j = 0;
    }

    km = (j ? i.substr(0, j) + thousands_sep : "");
    kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
    //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
    kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


    var number = km + kw + kd;
    var s = no_html ? this.currency.sign : this.currency.sign_html;
    if (!this.currency.sign_position) {
        return s + this.currency.sign_delim + number;
    } else {
        return number + this.currency.sign_delim + s;
    }
};
saleskuPluginProduct.prototype.getSimilarSkus = function(sku_id) {
    var sel = this.getElements().Selectors();
    var skus = this.getForm().find(sel.skus_button);
    var skus_ids = [];
    var c = 0;
    var sku_index = 0;
    // Делаем массив значений, по нему будем менять
    skus.each(function() {
        skus_ids[c] = $(this).val();
        if(sku_id ==  $(this).val()) {
            sku_index = c;
        }
        c++;
    });
    var similar_values = {};
    var ind = 1;
    for (var i = 1;i<=this.count(skus_ids);i++) {
        if(skus_ids.hasOwnProperty(sku_index+i)){
            var sku_id = skus_ids[sku_index+i];
            similar_values[ind] = sku_id;
            ind++;
        }
        if(skus_ids.hasOwnProperty(sku_index-i)){
            var sku_id = skus_ids[sku_index-i];
            similar_values[ind] = sku_id;
            ind++;
        }
    }
    return similar_values;
};
saleskuPluginProduct.prototype.getHideClass = function (type) {
    if(typeof ($.saleskuPluginProductsPool) == 'object' && $.saleskuPluginProductsPool.hasOwnProperty('getFeatureHideClass')) {
        return $.saleskuPluginProductsPool.getFeatureHideClass(type);
    }
    return 'none-class';
};
saleskuPluginProduct.prototype.serviceVariantHtml = function (id, name, price) {
    return $('<option data-price="' + price + '" value="' + id + '"></option>').text(name + ' (+' + this.currencyFormat(price, 1) + ')');
};
saleskuPluginProduct.prototype.updateSkuServices = function (sku_id) {
    this.form.find("div.stocks div").hide();
    this.form.find(".sku-" + sku_id + "-stock").show();
    for (var service_id in this.services[sku_id]) {
        var v = this.services[sku_id][service_id];
        if (v === false) {
            this.form.find(".service-" + service_id).hide().find('input,select').attr('disabled', 'disabled').removeAttr('checked');
        } else {
            this.form.find(".service-" + service_id).show().find('input').removeAttr('disabled');
            if (typeof (v) == 'string') {
                this.form.find(".service-" + service_id + ' .service-price').html(this.currencyFormat(v));
                this.form.find(".service-" + service_id + ' input').data('price', v);
            } else {
                var select = this.form.find(".service-" + service_id + ' .service-variants');
                var selected_variant_id = select.val();
                for (var variant_id in v) {
                    var obj = select.find('option[value=' + variant_id + ']');
                    if (v[variant_id] === false) {
                        obj.hide();
                        if (obj.attr('value') == selected_variant_id) {
                            selected_variant_id = false;
                        }
                    } else {
                        if (!selected_variant_id) {
                            selected_variant_id = variant_id;
                        }
                        obj.replaceWith(this.serviceVariantHtml(variant_id, v[variant_id][0], v[variant_id][1]));
                    }
                }
                this.form.find(".service-" + service_id + ' .service-variants').val(selected_variant_id);
            }
        }
    }
};
saleskuPluginProduct.prototype.setComparePrice = function (compare_price) {
    if (compare_price) {
        $compare_price = this.getElements().ComparePrice();
        if (!$compare_price.length) {
            $compare_price = $(this.getElements().Selectors().compare_price_html);
            this.getElements().Price().before($compare_price);
        }
        $compare_price.html(this.currencyFormat(compare_price)).show();
    } else {
        this.getElements().ComparePrice().remove();
    }
};
saleskuPluginProduct.prototype.setPrice = function (price, data_price) {
    if(this.getElements().Price().length>0) {
        if(data_price) {
            this.getElements().Price().data('price', String(data_price));
        }
        var quantity =   this.getElements().Quantity();
        if(quantity.length>0) {
            var q = parseFloat(quantity.val()) > 0? parseFloat(quantity.val()) : 1;
            price = q*price;
        }
        this.getElements().Price().html(this.currencyFormat(price));
    }
};
saleskuPluginProduct.prototype.updatePrice = function (price, compare_price) {
    if (price === undefined) {
        var input_checked = this.form.find(".skus input:radio:checked");
        if (input_checked.length) {
            var price = parseFloat(input_checked.data('price'));
            compare_price = parseFloat(input_checked.data('compare-price'));
        } else {
            if(this.getElements().Price()) {
                var price = parseFloat(this.getElements().Price().data('price'));
            }
        }
    }
    var clean_price = price;
    var self = this;
    this.form.find(".services input:checked").each(function () {
        var s = $(this).val();
        if (self.form.find('.service-' + s + '  .service-variants').length) {
            price += parseFloat(self.form.find('.service-' + s + '  .service-variants :selected').data('price'));
        } else {
            price += parseFloat($(this).data('price'));
        }
    });
    this.setPrice(price, clean_price);
    this.setComparePrice(compare_price);
};
saleskuPluginProduct.prototype.cartButtonVisibility = function (visible) {
    //toggles "Add to cart" / "%s is now in your shopping cart" visibility status
    if (visible) {
        this.cartButtonActive(true);
        if(this.getElements().ComparePrice().length>0) {
            this.getElements().ComparePrice().show();
        }
        if(this.getElements().Price().length>0) {
            this.getElements().Price().show();
        }
        this.getElements().Quantity().show();
        this.getElements().added2cart().hide();
    } else {
        this.cartButtonActive(false);
        if(this.getElements().ComparePrice().length>0) {
            this.getElements().ComparePrice().hide();
        }
        if(this.getElements().Price().length>0) {
            this.getElements().Price().hide();
        }
        this.getElements().Quantity().hide();
        this.getElements().added2cart().hide();
    }
};
saleskuPluginProduct.prototype.cartButtonActive = function (active) {
    var button = this.getElements().CartButton();
    if(button.length) {
        var tag = button.get(0).tagName;
        if(tag == 'INPUT' || tag == 'BUTTON') {
            if (active) {
                button.removeAttr('disabled');
            } else {
                button.attr('disabled', 'disabled');
            }
            button.show();
        } else if(tag == 'A') {
            if (active) {
                button.show();
            } else {
                button.hide();
            }
        } else {
            if (active) {
                button.show();
            } else {
                button.hide();
            }
        }
    }
};
saleskuPluginProduct.prototype.count = function (mixed_var, mode) {
    var key, cnt = 0;
    if (mode == 'COUNT_RECURSIVE')mode = 1;
    if (mode != 1)mode = 0;
    for (key in mixed_var) {
        cnt++;
        if (mode == 1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object)) {
            cnt += this.count(mixed_var[key], 1)
        }
    }
    return cnt;
};

$(document).ready(function() {
    $(document).on('change','[name="salesku_skus"]',function () {
        var val = $(this).val();
        var container = $(this).closest('.salesku_skus').parent();
        var ein = false;
        container.find('[name="sku_id"]').attr('checked', false).each(function () {
            if($(this).val() == val) {
                ein = $(this);
            }
        });
        if(ein) {
            ein.attr('checked', true).prop('checked', true).trigger('click');
        }
    });
});
