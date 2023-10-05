(function($) {
    $.saleskuPluginProductsPool = {
        debug: 0,
        debug_messages: {},
        smart_sku_class_grey: '',
        smart_sku_class_hide: '',
        current_change_product: false,
        set_products_flag: 0,
        product_id_selector: '#salesku-id-',
        products: {},
        product_lists: {},
        product_list_products:{},
        binds:{},
        _off: {},
        _on: {},
        init_timeout: 0,
        init_flag:false,
        init: function() {
            if(this.init_timeout<1) {
                this.init_timeout = (Math.round(new Date().getTime()/1000))+1;
            }
            if(this.init_timeout < Math.round(new Date().getTime()/1000)) {
                return; // Выполнено с ошибкой
            }
            // Проверяем что все продукты уже добавлены в пул
            if($('.salesku-id').length>this.count(this.products)) {
                setTimeout(function(){$.saleskuPluginProductsPool.init()}, 50);
            } else {
                for (var f in this.binds) {
                    var bind = this.binds[f];
                    var func = bind['_function']
                    if (typeof func === "function") {
                        func();
                    }
                }
                this.init_flag = true;
            }
        },
        isInit: function() {
            return this.init_flag;
        },
        addList: function($element) {
            var list_class = this.getListClass($element);
            if(list_class == '' || !this.product_lists.hasOwnProperty(list_class)) {
                list_class = 'salesku_plugin_product_list-'+Math.random().toString(36).substr(2);
                $element.addClass(list_class);
                $element.addClass('salesku_plugin_product_list');
                this.product_lists[list_class] = $element;
                this.product_list_products[list_class] = {};
            }
        },
        // Создаем объект продукта и кладем в пул
        addProduct : function(id, product_data) {
            if (typeof saleskuPluginProduct === "function") {
                // Создаем объект продукта
                var form_selector = this.product_id_selector+''+id;
                this.products[id] = new saleskuPluginProduct(form_selector,  product_data);
                //  Проверяем продукт лист
                var list_class = this.products[id].getProductListClass();
                if(!this.product_list_products.hasOwnProperty(list_class)) {
                    this.product_list_products[list_class] = {};
                    this.product_lists[list_class] = this.products[id].getParent();
                }
                // Добавляем в лист
                this.product_list_products[list_class][id] = true;
                // Проверяем активность продуктов в листе
                if(this.isOff(this.product_lists[list_class]) ) {
                    this.products[id].init(false);

                } else {
                    this.products[id].init(true);
                }
            }
        },
        isOff: function($product_list) {
            var product_list_element = $product_list.get(0);
            for(var list in this._off) {
                var current_list = $(this._off[list]);
                if(current_list.get(0)==product_list_element && !this._on.hasOwnProperty(list)) {
                    return true;
                } else {
                    if($(this._off[list]).find($product_list).length>0) {
                        return true;
                    }
                }
            }
            return false;
        },
        setOn: function(selector) {
            this._on[selector] = selector;
        },
        setOff: function(selector) {
            this._off[selector] = selector;
        },
        on: function ($product_list) {
            if($product_list.length) {
                $product_list.find('.salesku-id').each(function () {
                    var id = $(this).attr('id').replace(/salesku-id-/g, '');
                    if($.saleskuPluginProductsPool.products.hasOwnProperty(id)) {
                        $.saleskuPluginProductsPool.products[id].on();
                    }
                });
            }
        },
        off: function ($product_list) {
            if($product_list.length) {
                $product_list.find('.salesku-id').each(function () {
                    var id = $(this).attr('id').replace(/salesku-id-/g, '');
                    if($.saleskuPluginProductsPool.products.hasOwnProperty(id)) {
                        $.saleskuPluginProductsPool.products[id].off();
                    }
                });
            }
        },
        _bind: function(selector, action, _function) {
            selector = selector || '';
            action = action || 'click';
            _function = _function || '';
            var _bind = {
                'selector':selector,
                'action':action,
                '_function': _function
            };
            var id = selector.trim()+''+action.trim();
            this.binds[id] = _bind;
        },
        _unbind: function(selector,action) {
            action = action || 'click';
            selector = selector || '';
            var id = selector.trim()+''+action.trim();
            if(this.binds.hasOwnProperty(id)) {
                delete this.binds[id];
            }
        },
        bindOff: function(selector, product_list_selector, action) {
            action = action || 'click';
            selector = selector || '';
            product_list_selector = product_list_selector || $(document);
            var func = function () {
                var self = this;
                $(self.selector).bind(self.action, function(){
                    $.saleskuPluginProductsPool.off($(self.product_list_selector));
                });
            };
            this._bind(selector,action,this.bind(func,{'selector':selector,'product_list_selector':product_list_selector,'action':action}));
        },
        bindOn: function(selector, product_list_selector, action) {
            action = action || 'click';
            selector = selector || '';

            product_list_selector = product_list_selector || $(document);
            var func = function () {
                var self = this;
                $(self.selector).bind(self.action, function(){
                    $.saleskuPluginProductsPool.on($(self.product_list_selector));
                });
            };

            this._bind(selector,action,this.bind(func,{'selector':selector,'product_list_selector':product_list_selector,'action':action}));

        },
        getListClass: function ($product_list) {
            var classes = $product_list.attr('class').split(' ');
            for(var k in classes) {
                var _class = classes[k];
                var Regex = new RegExp('salesku_plugin_product_list-');
                if(Regex.test(_class)) {
                    return _class;
                }
            }
            return '';
        },
        setSettings: function(settings){
            for(var k in settings) {
                this[k] = settings[k];
            }
        },
        getFeatureHideClass: function (type) {
            if(parseInt(type)==1) {
                return this.smart_sku_class_grey;
            } else if(parseInt(type)==2) {
                return this.smart_sku_class_hide;
            } else {
                return '';
            }
        },
        // Функция массовой смены характеристики у всехх продуктов
        changeFeature: function(feature_obj, product_obj) {
            if(!product_obj.isRelatedSku() || !this.isInit()) {
                return false;
            }
            var self = this;
            if(!this.current_change_product) {
                this.current_change_product = product_obj;
            } else if(product_obj.getUid()==this.current_change_product.getUid()) {
                return;
            }
            var valueExists = function(product_value_obj, value_id) {
                if(feature_value_id  == val_id && (!$(this).hasClass(self.getFeatureHideClass(1)) && !$(this).hasClass(self.getFeatureHideClass(2)))) {
                    value_exists = true;
                }
            };
            var setFeatureValue = function(feature_obj, feature_value_id) {
                var tag = feature_obj.get(0).tagName;
                if(tag == 'INPUT') {
                    // Проверяем что в продукте есть такое значение
                    var value_exists = false;
                    feature_obj.closest('.inline-select').find('a').each(function () {
                        var val_id = $(this).data('value');
                        if(feature_value_id  == val_id && (!$(this).hasClass(self.getFeatureHideClass(1)) && !$(this).hasClass(self.getFeatureHideClass(2)))) {
                            value_exists = true;
                        }
                    });
                    // станавливаем значение
                    if(value_exists) {
                        feature_obj.closest('.inline-select').find('a').each(function () {
                            var val_id = $(this).data('value');
                            if(feature_value_id == val_id) {
                                $(this).addClass('selected');
                                $(this).removeClass(self.getFeatureHideClass(1)).removeClass(self.getFeatureHideClass(2));
                            } else {
                                $(this).removeClass('selected');
                            }
                        });
                        feature_obj.val(feature_value_id);
                        return true;
                    }
                } else if(tag == 'SELECT') {
                    var val =  feature_obj.find('option[value="' + feature_value_id + '"]');
                    if(val.val()==feature_value_id && (!val.hasClass(self.getFeatureHideClass(1)) && !val.hasClass(self.getFeatureHideClass(2)))) {
                        feature_obj.find('option[value="' + feature_value_id + '"]').removeClass(self.getFeatureHideClass(1)).removeClass(self.getFeatureHideClass(1));
                        feature_obj.val(feature_value_id);
                        return true;
                    }
                }
                return false;
            };
            var feature_id = feature_obj.data('feature-id');
            var value_id = feature_obj.val();
            for(var id in this.products) {
                var product = this.products[id];
                if(product.getType() == product_obj.getType() && product.getUid() != product_obj.getUid() ) {
                    var product_feature = product.getForm().find('[name="features['+feature_id+']"]');
                    if(product_feature && product_feature.hasClass('sku-feature')) {
                        // Ставим значение характеристики и делаем пересчет продукта
                        if(setFeatureValue(product_feature, value_id)) {
                            product.setSkuByFeature(product_feature);
                        }
                    }
                }
            }
            this.current_change_product = false;
        },
        bind: function(func, context /*, args*/) {
            var bindArgs = [].slice.call(arguments, 2); // (1)
            function wrapper() {                        // (2)
                var args = [].slice.call(arguments);
                var unshiftArgs = bindArgs.concat(args);  // (3)
                return func.apply(context, unshiftArgs);  // (4)
            }
            return wrapper;
        },
        setLogMessage: function(message) {
            console.log(message+"\n");
            var rand = function() {
                return Math.random().toString(36).substr(2);
            };
            var token = function() {
                return rand() + rand();r
            };
            this.debug_messages[token()] = message;
        },
        getLogMessages: function (separator) {
            separator = separator||"\n ";
            return this.debug_messages.join(separator+' ');
        },
        count : function (mixed_var, mode) {
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
    $(document).ready(function () {
        $.saleskuPluginProductsPool.init();
    });
})(jQuery);