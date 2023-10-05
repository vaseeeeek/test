$.autobadge_conditions = {
    urls: {
        cat: '?plugin=autobadge&action=handler&data=getCategoryJson',
        set: '?plugin=autobadge&action=handler&data=getSetJson',
        type: '?plugin=autobadge&action=handler&data=getTypeJson',
        product: '?plugin=autobadge&module=dialog&action=getProducts',
        feature: '?plugin=autobadge&action=handler&data=getFeatureJson',
        featureValues: '?plugin=autobadge&action=handler&data=getFeatureValuesJson',
        services: '?plugin=autobadge&action=handler&data=getServicesJson',
        servicesVariants: '?plugin=autobadge&action=handler&data=getServicesVariantsJson',
        params: '?plugin=autobadge&action=handler&data=getParamsJson',
        tags: '?plugin=autobadge&action=handler&data=getTagsJson',
        ucat: '?plugin=autobadge&action=handler&data=getUserCategoryJson',
        user: '?plugin=autobadge&module=dialog&action=getUsers',
        autocomplete_product: '?plugin=autobadge&action=autocomplete&type=product',
        autocomplete_contact: '?plugin=autobadge&action=autocomplete&type=contact',
        shipping: '?plugin=autobadge&action=handler&data=getShippingJson',
        payment: '?plugin=autobadge&action=handler&data=getPaymentJson',
        storefrontDomains: '?plugin=autobadge&action=handler&data=getStorefrontDomainsJson',
        storefrontRoutes: '?plugin=autobadge&action=handler&data=getStorefrontRoutesJson',
        theme: '?plugin=autobadge&action=handler&data=getThemeDesignJson',
        stocks: '?plugin=autobadge&action=handler&data=getStocksJson'
    },
    optsEqNe: {
        type: 'Select',
        width: '100px',
        name: 'op',
        values: {
            eq: $__("equal"),
            neq: $__("not equal")
        }
    },
    optsNum: function (name) {
        return {
            type: 'Select',
            width: '60px',
            name: name ? name : 'op',
            values: {
                gt: '>',
                gte: '>=',
                lt: '<',
                lte: '<=',
                eq_num: '=',
                neq_num: '<>'
            }
        };
    },
    optsText: {
        type: 'Select',
        width: '130px',
        name: 'op',
        values: {
            eq: $__("equal"),
            neq: $__("not equal"),
            cont: $__("contains"),
            notcont: $__("not contains"),
            begins: $__("begins with")
        }
    },
    optsAll: {
        type: 'Select',
        width: '130px',
        name: 'op',
        placeholder: $__('select'),
        values: {
            eq: $__("equal"),
            neq: $__("not equal"),
            cont: $__("contains"),
            notcont: $__("not contains"),
            begins: $__("begins with"),
            gt: '>',
            gte: '>=',
            lt: '<',
            lte: '<=',
            eq_num: '=',
            neq_num: '<>'
        }
    },
    optsSumSku: {
        type: 'Select',
        width: '150px',
        class: 's-sum-sku',
        name: 'sum_type',
        values: {
            not_sum: $__("each sku is separate"),
            sum: $__("summarize skus")
        }
    },
    optsStocks: {
        type: 'Select',
        width: '150px',
        class: 'stock-options',
        name: 'stock_type',
        values: {
            virtreal: $__("virtual and real"),
            virt: $__("virtual"),
            real: $__("real")
        }
    },
    filterCond: '<i title="' + $__('This condition will filter the result items') + '" class="icon16 funnel"></i>',
    resetField: '<a href="#/reset/selection/" class="js-action s-reset-button" style="display: none" title="' + $__('reset selection') + '"><i class="icon16 no"></i></a>',
    chosenParams: {disable_search_threshold: 12, no_results_text: $__("No result text"), search_contains: true},
    init: function (options) {
        this.currency = options.currency || '';
        this.optCats = {type: 'SelectFromUrl', source: this.urls['cat'], placeholder: $__('Select category'), id: 'cat', name: 'value'};
        this.types = {
            cat: [$__('Category'), this.optsEqNe, this.optCats, this.filterCond],
            cat_all: [$__('Category and subcategories'), this.optsEqNe, this.optCats, this.filterCond],
            set: [$__('Product set'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['set'], placeholder: $__('Select product set'), id: 'set', name: 'value'}, this.filterCond],
            type: [$__('Product type'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['type'], placeholder: $__('Select product type'), id: 'type', name: 'value'}, this.filterCond],
            product: [$__('Product'), this.optsEqNe, {type: 'PopupFromUrl', source: this.urls['product'], link: $__("select product"), id: 'product', name: 'value'}, this.filterCond],
            feature: [
                $__('Product feature'),
                {type: 'SelectFromUrl', source: this.urls['feature'], placeholder: $__('Select product feature'), id: 'feature', name: 'field', class: 'feature-select', width: '350px'},
                this.optsAll,
                {type: 'SelectFromUrl', source: this.urls['featureValues'], id: 'featureValue', placeholder: $__('Select feature value'), name: 'value', class: 'feature-value-template', hidden: 1, width: '350px'},
                {type: 'Input', name: 'value', class: 'feature-value-input', hidden: 1, width: '150px'},
                this.filterCond
            ],
            params: [
                $__('Product params'),
                {type: 'SelectFromUrl', source: this.urls['params'], placeholder: $__('Select product params'), id: 'params', name: 'field', width: '350px'},
                this.optsAll,
                {type: 'Input', name: 'value', width: '150px'},
                this.filterCond
            ],
            product_name: [$__('Product name'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_sku: [$__('SKU code'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_sku_name: [$__('SKU name'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_summary: [$__('Product summary'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_mt: [$__('META title'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_mk: [$__('META keywords'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_md: [$__('META description'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_description: [$__('Product description'), this.optsText, {type: 'Input', name: 'value', width: '450px'}, this.filterCond],
            product_create: [$__('Product create datetime'), this.optsNum(), {type: 'Date', width: '120px', name: 'value', callback: 'dateCallback'}, this.filterCond],
            product_age: [$__('Product age'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, $__('days'), this.filterCond],
            product_edit: [$__('Product edit datetime'), this.optsNum(), {type: 'Date', width: '120px', name: 'value', callback: 'dateCallback'}, this.filterCond],
            product_video: [$__('Product'), this.optsEqNe, $__('has video'), this.filterCond],
            product_image: [$__('Product'), this.optsEqNe, $__('has image'), this.filterCond],
            product_rating: [$__('Product rating'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.filterCond],
            product_rating_count: [$__('Product rating count'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.filterCond],
            product_price: [$__('Product price'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_compare_price: [$__('Product compare price'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_purchase_price: [$__('Product purchase price'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_margin: [$__('Product price') + " - " + $__('Product purchase price'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_margin_comp: [$__('Product compare price') + " - " + $__('Product price'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_min_price: [$__('Product minimal price'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_max_price: [$__('Product maximum price'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_stock:
            [
                $__('Product stock count'),
                this.optsSumSku,
                $__('on '),
                {type: 'SelectFromUrl', source: this.urls['stocks'], placeholder: $__('all stocks'), id: 'stocks', class: 'stocks-select', name: 'field'},
                this.optsStocks,
                this.optsNum(),
                {type: 'Input', name: 'value', width: '150px', placeholder: '∞'},
                this.filterCond
            ],
//            product_stock_change: [$__('Changing of product stocks for'), {type: 'Period'}, this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.filterCond],
            product_total_sales: [$__('Product total sales for'), {type: 'Period'}, this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            product_number_sales: [$__('Product total quantity of sales for'), {type: 'Period'}, this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.filterCond],
            product_services: [
                $__('Product has service'),
                this.optsEqNe,
                {type: 'SelectFromUrl', source: this.urls['services'], placeholder: $__('Select service'), id: 'services', name: 'field', class: 'feature-select s-services', width: '350px'},
                {type: 'SelectFromUrl', source: this.urls['servicesVariants'], placeholder: $__('Select service variant'), id: 'servicesVariants', name: 'value', class: 'feature-value', hidden: 1, width: '350px'},
                this.resetField,
                this.filterCond
            ],
            product_tags: [
                $__('Product has tag'),
                this.optsEqNe,
                {type: 'SelectFromUrl', source: this.urls['tags'], placeholder: $__('Select tags'), id: 'tags', name: 'value', width: '400px'},
                this.filterCond
            ],
            product_badge_type: [$__('Badge type'), this.optsText, {type: 'Input', name: 'value', width: '150px'}],
            num_total: [$__('Total quantity of all products'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}],
            num: [$__('Quantity of products'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}],
            num_prod: [$__('Quantity of product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, this.optsSumSku, this.optsNum(), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_cat: [$__('Quantity of product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, this.optsSumSku, $__('from category'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['cat'], placeholder: $__('select category'), id: 'cat', name: 'ext'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_cat_all: [$__('Quantity of product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, this.optsSumSku, $__('from category and subcategories of'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['cat'], placeholder: $__('select category'), id: 'cat', name: 'ext'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_set: [$__('Quantity of product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, this.optsSumSku, $__('from set'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['set'], placeholder: $__('Select product set'), id: 'set', name: 'ext'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_type: [$__('Quantity of product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, this.optsSumSku, $__('from type'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['type'], placeholder: $__('Select product type'), id: 'type', name: 'ext'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_all_cat: [$__('Quantity of all products from category'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['cat'], placeholder: $__('select category'), id: 'cat', name: 'field'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_all_cat_all: [$__('Quantity of all products from category and subcategories'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['cat'], placeholder: $__('select category'), id: 'cat', name: 'field'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_all_set: [$__('Quantity of all products from set'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['set'], placeholder: $__('Select product set'), id: 'set', name: 'field'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_all_type: [$__('Quantity of all products from type'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['type'], placeholder: $__('Select product type'), id: 'type', name: 'field'}, this.optsNum('op2'), {type: 'Input', name: 'value', width: '90px'}, this.filterCond],
            num_feat: [
                $__('Quantity of products with features'),
                {type: 'SelectFromUrl', source: this.urls['feature'], placeholder: $__('Select product feature'), id: 'feature', name: 'field', class: 'feature-select', width: '350px'},
                this.optsAll,
                {type: 'SelectFromUrl', source: this.urls['featureValues'], placeholder: $__('Select feature value'), id: 'featureValue', name: 'ext', class: 'feature-value-template', hidden: 1, width: '350px'},
                {type: 'Input', name: 'ext', class: 'feature-value-input', hidden: 1, width: '90px'},
                this.optsNum('op2'),
                {type: 'Input', name: 'value', width: '90px'},
                this.filterCond
            ],
            num_items: [$__('Quantity of unique items'), this.optsNum(), {type: 'Input', name: 'value', width: '90px'}],
            total: [$__('Order price without discount'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency],
            sum: [$__('Total price of all products'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency],
            sum_cat: [$__('Total price of products'), $__('from category'), {type: 'SelectFromUrl', source: this.urls['cat'], placeholder: $__('select category'), id: 'cat', name: 'field'}, this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            sum_cat_all: [$__('Total price of products'), $__('from category and subcategories of'), {type: 'SelectFromUrl', source: this.urls['cat'], placeholder: $__('select category'), id: 'cat', name: 'field'}, this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency, this.filterCond],
            sum_feat: [
                $__('Total price of all products with features'),
                {type: 'SelectFromUrl', source: this.urls['feature'], placeholder: $__('Select product feature'), id: 'feature', name: 'field', class: 'feature-select', width: '350px'},
                this.optsAll,
                {type: 'SelectFromUrl', source: this.urls['featureValues'], placeholder: $__('Select feature value'), id: 'featureValue', name: 'ext', class: 'feature-value-template', hidden: 1, width: '350px'},
                {type: 'Input', name: 'ext', class: 'feature-value-input', hidden: 1, width: '150px'},
                this.optsNum('op2'),
                {type: 'Input', name: 'value', width: '150px'},
                this.currency,
                this.filterCond
            ],
            'total_feat': [
                $__('Total sum of features values'),
                {type: 'SelectFromUrl', source: this.urls['feature'], placeholder: $__('Select product feature'), id: 'feature', name: 'field', class: 'feature-select extrem-feature-select', width: '350px'},
                this.optsNum(),
                {type: 'Input', name: 'value', class: 'feature-value-input', hidden: 1, width: '150px'}
            ],
            prod_each_price: [$__('Price of each product'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency],
            services: [
                $__('Service'),
                this.optsEqNe,
                {type: 'SelectFromUrl', source: this.urls['services'], placeholder: $__('Select service'), id: 'services', name: 'field', class: 'feature-select s-services', width: '350px'},
                {type: 'SelectFromUrl', source: this.urls['servicesVariants'], placeholder: $__('Select service variant'), id: 'servicesVariants', name: 'value', class: 'feature-value', hidden: 1, width: '350px'},
                this.resetField
            ],
            ucat: [$__('User category'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['ucat'], placeholder: $__('Select user category'), id: 'ucat', name: 'value'}],
            user: [$__('Contact'), this.optsEqNe, {type: 'PopupFromUrl', source: this.urls['user'], link: $__("select contact"), id: 'user', name: 'value'}],
            user_date: [$__('Contact create datetime'), this.optsNum(), {type: 'Date', width: '120px', name: 'value', callback: 'dateCallback'}],
            shipping: [$__('Shipping'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['shipping'], placeholder: $__('Select shipping'), id: 'shipping', name: 'value'}],
            payment: [$__('Payment'), this.optsEqNe, {type: 'SelectFromUrl', source: this.urls['payment'], placeholder: $__('Select payment'), id: 'payment', name: 'value'}],
            all_orders: [$__('Total sum of all orders'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency],
            order_int: [$__('Sum of orders for'), {type: 'Period'}, this.optsNum(), {type: 'Input', name: 'value', width: '150px'}, this.currency],
            count_orders: [$__('Quantity of all orders'), this.optsNum(), {type: 'Input', name: 'value', width: '150px'}],
            order_count_int: [$__('Quantity of orders for'), {type: 'Period'}, this.optsNum(), {type: 'Input', name: 'value', width: '90px'}],
            order_prod: [$__('Orders have product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("select product"), id: 'product', name: 'value'}],
            order_prod_int: [$__('Orders have product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'value', 'can_reset': 1}, $__('for'), {type: 'Period'}, this.filterCond],
            order_prod_cat: [$__('Orders have product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, $__("from category"), this.optCats, this.filterCond],
            order_prod_cat_all: [$__('Orders have product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, $__("from category and subcategories of"), this.optCats, this.filterCond],
            order_prod_cat_int: [$__('Orders have product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, $__("from category"), this.optCats, $__('for'), {type: 'Period'}, this.filterCond],
            order_prod_cat_all_int: [$__('Orders have product'), {type: 'PopupFromUrl', source: this.urls['product'], link: $__("any product"), id: 'product', name: 'field', 'can_reset': 1}, $__("from category and subcategories of"), this.optCats, $__('for'), {type: 'Period'}, this.filterCond],
            date: [$__('Date'), this.optsNum(), {type: 'Date', width: '120px', name: 'value', callback: 'dateCallback'}],
            week: [$__('Day of week'), {type: 'Select', width: '110px', name: 'value', values: {1: $__("Monday"), 2: $__("Tuesday"), 3: $__("Wednesday"), 4: $__("Thursday"), 5: $__("Friday"), 6: $__("Saturday"), 7: $__("Sunday")}}],
            time: [$__('Time'), this.optsNum(), {type: 'Time'}],
            cookie: ['$_COOKIE["', {type: 'Input', name: 'field', width: '150px'}, '"]', this.optsAll, {type: 'Input', name: 'value', width: '150px'}],
            session: ['$_SESSION["', {type: 'Input', name: 'field', width: '150px'}, '"]', this.optsAll, {type: 'Input', name: 'value', width: '150px'}],
            get: ['$_GET["', {type: 'Input', name: 'field', width: '150px'}, '"]', this.optsAll, {type: 'Input', name: 'value', width: '150px'}],
            post: ['$_POST["', {type: 'Input', name: 'field', width: '150px'}, '"]', this.optsAll, {type: 'Input', name: 'value', width: '150px'}],
            server: ['$_SERVER["', {type: 'Input', name: 'field', width: '150px'}, '"]', this.optsAll, {type: 'Input', name: 'value', width: '150px'}],
            storefront: [
                $__('Storefront'),
                this.optsEqNe,
                {type: 'SelectFromUrl', placeholder: $__('Select domain'), width: '350px', source: this.urls['storefrontDomains'], id: 'storefrontDoman', class: 'storefront-domain', name: 'field'},
                {type: 'SelectFromUrl', placeholder: $__('Select route'), hidden: 1, class: 'storefront-route', source: this.urls['storefrontRoutes'], id: 'storefrontRoutes', name: 'value', width: '350px'},
                this.resetField
            ],
            mobile: [this.optsEqNe, $__("Mobile version")],
            theme: [
                $__('Theme design'),
                this.optsEqNe,
                {type: 'SelectFromUrl', placeholder: $__('Select theme design'), width: '350px', source: this.urls['theme'], id: 'theme', name: 'value'}
            ],
            product_page: [this.optsEqNe, $__("Product page")]
        };
        if (options.target) {
            $.autobadge.buildTargets(options.target);
        }

        this.initCallbacks();

        if (options.conditions !== '' || options.target !== '') {
            $.post("?plugin=autobadge&module=filter&action=decodeJSON", {conditions: options.conditions !== '' ? JSON.stringify(options.conditions) : '', target: options.target !== '' ? JSON.stringify(options.target) : ''}, function (response) {
                if (response.status == 'ok' && response.data) {
                    if (response.data.conditions !== undefined) {
                        $("#autobadge-save-form .s-conditions .condition-block").replaceWith(response.data.conditions);
                        $("#autobadge-save-form .s-conditions > .value > .condition-block select").chosen($.autobadge_conditions.chosenParams).each(function () {
                            var that = $(this);
                            if (that.hasClass("extrem-feature-select")) {
                                that.find(".selectable").hide();
                                that.trigger("chosen:updated");
                            }
                            that.addClass("inited");
                        });
                    }
                    if (response.data.target !== undefined && $.trim(response.data.target) !== '') {
                        $("#autobadge-save-form .targets .s-add-target").before(response.data.target);
                        $("#autobadge-save-form .targets .target-chosen").not(".hidden").each(function () {
                            var select = $(this);
                            if (select.hasClass("target-chosen")) {
                                select.addClass("inited");
                                $.autobadge.initTargetChosen(select, true);
                            } else {
                                select.chosen($.autobadge_conditions.chosenParams).addClass("inited");
                            }
                            if (select.hasClass("hide-after-init")) {
                                select.next(".chosen-container").hide();
                            }
                        });
                    }
                    $.autobadge_conditions.afterLoadInit();
                }
                $("#fixed-save-panel input[type='submit']").removeAttr("disabled");
                $("#autobadge-save-form .is-loading").remove();
            });
        } else {
            $("#autobadge-save-form .is-loading").remove();
            $("#fixed-save-panel input[type='submit']").removeAttr("disabled");
        }
    },
    afterLoadInit: function () {
        /* Скрываем ненужные характеристики */
        $(".feature-select").each(function () {
            $(this).change();
        });

        /* Скрываем ненужные домены */
        $(".storefront-domain").each(function () {
            $(this).change();
        });

        /* Инициализируем поля с датами */
        $(".init-datepicker").each(function () {
            $.autobadge_conditions.initDatepicker($(this));
        });

        /* Скрываем поля для выбора способа суммирования артикулов */
        $('.s-type-field').each(function () {
            $(this).val() == 'sku' && $(this).closest(".condition").find(".s-sum-sku").next(".chosen-container").hide();
        });
        $.autobadge.changeBadgeSort();
    },
    /* Автозаполнение */
    initAutocomplete: function (elem, type) {
        var that = this;
        elem.autocomplete({
            source: that.urls['autocomplete_' + type],
            minLength: 3,
            delay: 300,
            autoFocus: true,
            search: function () {
                elem.autocomplete('option', 'source', that.urls['autocomplete_' + type] + (elem.next().find(".f-autocomplete-skus").prop("checked") ? '&with_skus=1' : ''));
            },
            select: function (event, ui) {
                if (ui.item && $(".condition a.has-dialog").length) {
                    $.autobadge_conditions.dialogSelectProduct(elem, ui.item.name, ui.item.id, typeof ui.item.sku_name !== 'undefined' ? ui.item.sku_name : '', typeof ui.item.sku_id !== 'undefined' ? ui.item.sku_id : '');
                }
                elem.val('');
                return false;
            }
        });
    },
    isTypeExists: function (type) {
        return typeof this.types[type] !== 'undefined';
    },
    /* Выбор товара во всплывающем окне */
    dialogSelectProduct: function (elem, name, value, skuName, skuId) {
        var div = $(".condition a.has-dialog").html(name + (skuName ? ' (' + skuName + ')' : '')).closest("div");
        if (div.find("input.s-type-field").length) {
            div.find("input.s-type-field").val(skuId ? 'sku' : 'product');
            if (skuId) {
                div.parent().find(".s-sum-sku").next('.chosen-container').hide();
            } else {
                div.parent().find(".s-sum-sku").next('.chosen-container').show();
            }
        }
        div.find("input.s-value-field").val(skuId ? skuId : value);
        div.hasClass("has-reset") && div.find('.s-reset-button').show();
        elem.closest(".dialog").trigger("close");
    },
    /* Добавление поля */
    addField: function (block, type) {
        if (typeof this.types[type] !== 'undefined') {
            var callbacks = [];
            var field = $("<div class='condition inprocess'></div>");
            for (var i in this.types[type]) {
                var param = this.types[type][i];
                if (typeof param === 'string') {
                    field.append(this.addFieldText(param));
                } else {
                    var methodName = "get" + param.type + "Code";
                    if (this[methodName] === undefined) {
                        console.log("Method %s not exists", methodName);
                        continue;
                    }
                    field.append(this[methodName](param));
                    typeof param.callback !== 'undefined' && callbacks.push(param.callback);
                }
            }
            field.append(this.getInputCode({name: 'type', input_type: 'hidden', value: type}));
            field.append(this.getRemoveButtonCode());
            block.append(field);

            block.find(".inprocess select").chosen(this.chosenParams).each(function () {
                /* Подгружаем значения select */
                var select = $(this);
                if (select.data('source')) {
                    select.before("<i class='icon16 loading'></i>");
                    $.post(select.data('source'), function (response) {
                        select.append(response.data);
                        select.prev(".loading").remove();
                        select.trigger("chosen:updated").trigger("options_loaded");

                        /* Скрываем поле при необходимости */
                        var selectParam = select.data('param');
                        if (typeof selectParam !== 'undefined' && selectParam.hidden !== undefined) {
                            select.next(".chosen-container").hide();
                        }
                    }, "json");
                } else {
                    var selectParam = select.data('param');
                    if (typeof selectParam !== 'undefined' && selectParam.hidden !== undefined) {
                        select.next(".chosen-container").hide();
                    }
                }
                select.trigger("chosen:updated");
            });
            block.find("select.extrem-feature-select").each(function () {
                var select = $(this);
                select.find(".selectable").hide();
                select.trigger("chosen:updated");
            });

            if (callbacks) {
                for (var i in callbacks) {
                    try {
                        if (typeof this[callbacks[i]] === 'function') {
                            this[callbacks[i]].call(this);
                        }
                    } catch (e) {
                        console.log('Callback error: ' + e.message, e);
                    }
                }
            }

            block.find(".inprocess").removeClass("inprocess");
            this.updateConditionOperatorBlock(block.closest(".condition-block"));
        }
    },
    addFieldText: function (text) {
        return  "<span class='condition-text'>" + text + "</span>";
    },
    /* Обновление оператора для условий */
    updateConditionOperatorBlock: function (block) {
        if ($("> .conditions > .condition", block).length > 1) {
            if (block.children(".cond-op").length) {
                block.find(".cond-op").show();
            } else {
                block.prepend("<div class='cond-op margin-block'></div>");
                block.children(".cond-op").append(this.getConditionOperatorCode());
                $("> .cond-op select", block).chosen(this.chosenParams);
            }
            block.addClass("show-cond-op");
        } else {
            block.children(".cond-op").hide();
            block.removeClass("show-cond-op");
        }
    },
    /* Добавление группы условий */
    addGroup: function (block) {
        var group = $("<div class='condition'><div class='condition-block'><div class='conditions'></div>" + this.getRemoveBlockButtonCode() + "</div></div>");
        group.find(".condition-block").append(this.getAddButtonCode());
        block.parents(".condition-block").length % 2 === 1 && group.find(".condition-block").addClass("even");
        block.append(group);
        this.updateConditionOperatorBlock(block.closest(".condition-block"));
    },
    getConditionOperatorCode: function () {
        return this.getSelectCode({name: 'cond-op', values: {and: $__("All conditions return true"), or: $__("Any of condition returns true")}, width: '350px'});
    },
    getAddButtonCode: function () {
        return '<a href="#/show/condition/" class="js-action" title="' + $__("Add condition") + '"><i class="icon16 add"></i> ' + $__("Add condition") + '</a>';
    },
    getRemoveButtonCode: function () {
        return '<span class="condition-text s-delete"><a href="#/delete/condition/" class="js-action block half-padded" title="' + $__("Delete") + '"><i class="icon16 delete"></i></a></span>';
    },
    getRemoveBlockButtonCode: function () {
        return '<a href="#/delete/conditionBlock/" style="position: absolute; top: -5px; right: -5px;" class="js-action" title="' + $__("delete") + '"><i class="icon16 delete"></i></a>';
    },
    getSelectCode: function (param) {
        var select = $("<select name='" + param.name + "'" + (param.class !== undefined ? ' class="' + param.class + '"' : '') + "></select>");
        if (param.width !== undefined) {
            select.width(param.width);
        }
        if (param.placeholder !== undefined) {
            select.attr("data-placeholder", param.placeholder).prepend("<option value=''></option>");
        }
        if (param.source !== undefined) {
            select.data('source', param.source).data('param', param);
        }
        if (param.values !== undefined) {
            $.each(param.values, function (i, v) {
                select.append("<option class='op-" + i + "' value='" + i + "'>" + v + "</option>");
            });
        }
        return select;
    },
    getInputCode: function (param) {
        var params = {
            name: param.name,
            class: (param.hidden !== undefined ? 'hidden ' : '') + (param.class !== undefined ? param.class : ''),
            type: param.input_type ? param.input_type : 'text',
            value: param.value !== undefined ? param.value : ''
        };
        if (param.width !== undefined) {
            params['width'] = param.width;
            params['style'] = "min-width: " + param.width + ";width:" + param.width;
        }
        if (param.maxlength !== undefined) {
            params['maxlength'] = param.maxlength;
        }
        return $("<input />", params);
    },
    getSelectFromUrlCode: function (param) {
        var selectList = $("#" + param.id + "List");
        if (selectList.length) {
            var select = selectList.clone();
            select.removeData("source").removeAttr("id").data('param', param).attr('name', param.name).show().find("option").removeAttr('style');
            if (param.class !== undefined) {
                select.toggleClass().addClass(param.class);
            }
        } else {
            var select = $("<select" + (param.class !== undefined ? " class='" + param.class + "'" : "") + " name='" + param.name + "'></select>");
            select.css('width', param.width !== undefined ? param.width : '400px');
            if (param.placeholder !== undefined) {
                select.attr("data-placeholder", param.placeholder).prepend("<option value=''></option>");
            }
            select.attr("id", param.id + "List").data('source', param.source).data('param', param);
        }
        return select;
    },
    getPopupFromUrlCode: function (param) {
        var div = $("<div />", {class: 'condition-text' + (param.can_reset !== undefined ? ' has-reset' : '')});
        $("<a href='#/open/conditionDialog/' class='js-action' data-id='" + param.id + "' data-source='" + param.source + "' title='" + param.link + "'>" + param.link + "</a>").appendTo(div);
        div.append('<a href="#/reset/dialogSelection/" style="display: none" data-reset="' + param.link + '" class="js-action s-reset-button" title="' + $__('reset product') + '"><i class="icon16 no"></i></a>');
        div.append(this.getInputCode({name: param.name, class: 's-value-field', input_type: 'hidden'}));
        if (param.id == 'product') {
            div.append(this.getInputCode({name: 'product_type', class: 's-type-field', input_type: 'hidden'}));
        }
        return div;
    },
    getDateCode: function (param) {
        param['class'] = 'f-datepicker not-inited';
        return this.getInputCode(param);
    },
    getTimeCode: function (param) {
        var html = "<div class='inline-block align-center condition-text'>" + this.getInputCode({type: 'text', name: 'hour', width: '35px', maxlength: 2}).prop('outerHTML') + "<br>" + $__("HH") + "</div>";
        html += "<div class='inline-block align-center condition-text'>" + this.getInputCode({type: 'text', name: 'minute', width: '35px', maxlength: 2}).prop('outerHTML') + "<br>" + $__("MM") + "</div>";
        return html;
    },
    getPeriodCode: function (param) {
        var html = "<div class='inline-block condition-text'><select name='period_type' style='width: 400px' class='inherit period-select' data-placeholder='" + $__("select value") + "'>";
        html += "<option value=''></option>"
            + "<option value='period'>" + $__('period') + "</option>"
            + "<option value='ndays'>" + $__('last n-days') + "</option>"
            + "<option value='pweek'>" + $__('previous week') + "</option>"
            + "<option value='pmonth'>" + $__('previous month') + "</option>"
            + "<option value='pquarter'>" + $__('previous quarter') + "</option>"
            + "<option value='p6m'>" + $__('previous half a year') + "</option>"
            + "<option value='p9m'>" + $__('previous 9 months') + "</option>"
            + "<option value='p12m'>" + $__('previous year') + "</option>"
            + "<option value='today'>" + $__('today') + "</option>"
            + "<option value='cweek'>" + $__('current week') + "</option>"
            + "<option value='cmonth'>" + $__('current month') + "</option>"
            + "<option value='cquarter'>" + $__('current quarter') + "</option>"
            + "<option value='c6m'>" + $__('current 6 months') + "</option>"
            + "<option value='c9m'>" + $__('current 9 months') + "</option>"
            + "<option value='c12m'>" + $__('current year') + "</option>";
        html += "</select></div>";
        return html;
    },
    dateCallback: function () {
        $.each($(".f-datepicker.not-inited"), function () {
            $.autobadge_conditions.initDatepicker($(this));
            $(this).removeClass("not-inited");
        });
    },
    /* Инициализация всплывающего календаря */
    initDatepicker: function (field, outside) {
        var params = {};
        if ($.autobadge.locale == 'ru_RU') {
            params["months"] = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
            params["days_abbr"] = ["Вск", "Пон", "Вт", "Ср", "Чтв", "Пят", "Суб"];
            params["show_select_today"] = "Сегодня";
            params["lang_clear_date"] = "Очистить";
        }
        params["inside"] = typeof outside !== 'undefined' ? false : true;
        params["show_clear_date"] = true;
        field.Zebra_DatePicker(params);
    },
    initCallbacks: function () {
        /* Выбор характеристик товара */
        $(document).off("change", ".feature-select").on("change", ".feature-select", function () {
            var that = $(this);
            var selected = that.find(":selected");
            var block = that.closest(".condition");

            block.find(".feature-value.is-active").removeClass('is-active').addClass('hidden').next(".chosen-container").hide();
            block.find(".feature-value-template").next(".chosen-container").hide();

            /* Обработка услуг */
            if (block.find(".s-services").length) {
                var featureValuesSelect = block.find(".feature-value");
                var featureValues = featureValuesSelect.next(".chosen-container");
                var resetButton = block.find(".s-reset-button");
            }

            var operatorSelect = block.find("select[name='op']");
            var input = block.find(".feature-value-input");
            /* Если поле имеет выпадающие значения характеристик */
            if (selected.hasClass("selectable")) {

                /* Обработка услуг */
                if (block.find(".s-services").length) {
                    if (featureValues.length) {
                        var chosen = featureValuesSelect.data('chosen');
                        /* Сбрасываем результат */
                        if (typeof chosen !== 'undefined' && !featureValuesSelect.hasClass("inited")) {
                            chosen.results_reset();
                        }

                        featureValuesSelect.find("option").hide().siblings(".feature-" + that.val()).show();
                        featureValuesSelect.trigger("chosen:updated").removeClass("inited");
                        featureValues.show();
                        resetButton.show().parent().show();
                        featureValuesSelect.removeClass("hidden");
                    } else {
                        featureValues.hide();
                        resetButton.hide().parent().hide();
                        featureValuesSelect.addClass("hidden");
                        operatorSelect.find("option").show();
                        operatorSelect.trigger("chosen:updated");
                    }
                    return;
                }

                var featureValuesSelect = block.find(".feature-value-" + selected.val());
                var featureOriginalValue = $("#feature-value-" + selected.val());
                /* Если значений характеристик у данного фильтра нет, но на странице их уже добавили, тогда копируем их */
                if (featureOriginalValue.length && !featureValuesSelect.length) {
                    var featureValuesClone = block.find(".feature-value-template").clone();
                    featureValuesClone.html(featureOriginalValue.html());
                    input.before(featureValuesClone);
                    featureValuesClone.removeClass("feature-value-template hidden").addClass("feature-value feature-value-" + selected.val()).show().chosen($.autobadge_conditions.chosenParams);
                    var featureValuesSelect = block.find(".feature-value-" + selected.val());
                }

                var chosenOp = operatorSelect.data('chosen');
                operatorSelect.find("option[value='cont'], option[value='notcont'], option[value='begins'], option[value='eq_num'], option[value='neq_num']").hide();
                operatorSelect.trigger("chosen:updated");
                /* Сбрасываем результат */
                if (typeof chosenOp !== 'undefined' && !featureValuesSelect.hasClass("inited") && !block.find(".s-services").length) {
                    chosenOp.results_reset();
                }

                that.removeClass("inited");
                if (featureValuesSelect.length) {
                    var featureValues = featureValuesSelect.next(".chosen-container");
                    featureValuesSelect.removeClass("inited hidden").addClass('is-active');
                    featureValues.show();
                    input.addClass("hidden");
                } else {
                    var featureValuesClone = block.find(".feature-value-template").clone();
                    /* Подгружаем значения характеристик select */
                    input.before(featureValuesClone);
                    featureValuesClone.before("<i class='icon16 loading'></i>");
                    featureValuesClone.attr('id', "feature-value-" + selected.val()).removeClass("feature-value-template hidden").addClass("feature-value feature-value-" + selected.val()).show().chosen($.autobadge_conditions.chosenParams);

                    var source = block.find(".feature-value-template").data('param') !== undefined ? block.find(".feature-value-template").data('param').source : $.autobadge_conditions.urls['featureValues'];
                    if (source) {
                        $.post(source, {feature_id: selected.val()}, function (response) {
                            featureValuesClone.append(response.data);
                            featureValuesClone.prev(".loading").remove();
                            featureValuesClone.trigger("chosen:updated");

                            /* Скрываем поле при необходимости */
                            var selectParam = featureValuesClone.data('param');
                            if (typeof selectParam !== 'undefined' && selectParam.hidden !== undefined) {
                                featureValuesClone.next(".chosen-container").hide();
                            } else {
                                featureValuesClone.addClass("is-active").removeClass("hidden");
                            }
                            input.addClass("hidden");
                        }, "json");
                    } else {
                        featureValuesClone.prev(".loading").remove();
                    }
                }
            } else {
                operatorSelect.find("option").show();
                operatorSelect.trigger("chosen:updated");
                if (!that.hasClass("inited")) {
                    input.val('');
                }
                that.removeClass("inited");
                input.removeClass("hidden");

                if (block.find(".s-services").length) {
                    featureValues.hide();
                    featureValuesSelect.addClass("hidden");
                    resetButton.hide().parent().hide();
                }
            }
            if (selected.data("base-unit") !== undefined) {
                input.next(".f-temp").remove();
                input.addClass("has-base-unit").after("<span class='condition-text f-temp'>" + selected.data("base-unit") + "</span>");
            } else {
                input.removeClass("has-base-unit").next(".f-temp").remove();
            }
        });

        /* Выбор витрин */
        $(document).off("change", ".storefront-domain").on("change", ".storefront-domain", function () {
            var that = $(this);
            var block = that.closest(".condition");
            var routeSelect = block.find(".storefront-route");
            var routeValues = routeSelect.next(".chosen-container");
            var resetButton = block.find(".s-reset-button");
            /* Если поле имеет выпадающие значения характеристик */
            if (routeValues.length && block.find(".storefront-route .domain-" + that.val()).length) {
                var chosen = routeSelect.data('chosen');
                /* Сбрасываем результат */
                if (typeof chosen !== 'undefined' && !routeSelect.hasClass("inited")) {
                    chosen.results_reset();
                }
                routeSelect.find("option").hide().siblings(".domain-" + that.val()).show();
                routeSelect.trigger("chosen:updated");
                routeValues.show();
                resetButton.show().parent().show();
            } else {
                routeValues.hide();
                resetButton.hide().parent().hide();
            }
        });

        /* Изменение типа условий (OR, AND) */
        $(document).off('change', '.cond-op select').on('change', '.cond-op select', function () {
            var that = $(this);
            if (that.val() == 'or') {
                that.closest(".condition-block").addClass('s-or');
            } else {
                that.closest(".condition-block").removeClass('s-or');
            }
        });

        $(document).off('options_loaded', '.extrem-feature-select').on('options_loaded', '.extrem-feature-select', function () {
            var that = $(this);
            that.find(".selectable").hide();
            that.trigger("chosen:updated");
        });

        /* Выбор периода */
        $(document).off('change', '.period-select').on('change', '.period-select', function () {
            var that = $(this);
            if (!that.closest(".condition").find(".period-block").length) {
                that.closest(".condition-text").after("<div class='period-block condition-text'></div>");
            }
            var periodBlock = that.closest(".condition").find(".period-block");
            if (that.val() == 'period' || that.val() == 'ndays') {
                if (!periodBlock.find(".s-" + that.val()).length) {
                    var html = "<div class='s-" + that.val() + "'>";
                    switch (that.val()) {
                        case "period":
                            html += $.autobadge_conditions.addFieldText($__('period from')) + $.autobadge_conditions.getDateCode({width: '120px', name: 'field1'}).prop('outerHTML') + $.autobadge_conditions.addFieldText($__('to')) + $.autobadge_conditions.getDateCode({width: '120px', name: 'ext1'}).prop('outerHTML');
                            break;
                        case "ndays":
                            html += $.autobadge_conditions.addFieldText($__('the last')) + $.autobadge_conditions.getInputCode({width: '70px', name: 'field1'}).prop('outerHTML') + $.autobadge_conditions.addFieldText($__('days'));
                            break;
                    }
                    html += "</div>";
                    periodBlock.append(html);
                    $.autobadge_conditions.dateCallback();
                }
                periodBlock.show().children().hide().find("input").addClass("hidden");
                periodBlock.find(".s-" + that.val()).show().find("input").removeClass("hidden");
            } else {
                periodBlock.hide().find("input").addClass("hidden");
            }
        });
    },
    /* Собираем все условия в JSON объект */
    getJsonConditions: function () {
        var group = $(".s-conditions .condition-block").first();
        var result = this.getConditionGroupData(group);
        return result ? JSON.stringify(result) : '';
    },
    getConditionGroupData: function (group) {
        var groupOpObj = group.find("> .cond-op select");
        var data = {group_op: groupOpObj.length ? groupOpObj.val() : 'and', conditions: []};
        var conditions = group.children(".conditions").children(".condition");
        /* Если нет условий */
        if (!conditions.length) {
            return null;
        }
        conditions.each(function () {
            var condition = $(this);
            var obj;
            if (condition.children(".condition-block").length) {
                obj = $.autobadge_conditions.getConditionGroupData(condition.children(".condition-block"));
            } else {
                obj = $.autobadge_conditions.getConditionData(condition);
            }
            if (obj) {
                data.conditions.push(obj);
            }
        });
        return data;
    },
    getConditionData: function (condition) {
        var values = {};
        condition.find("input, select").not(".hidden").each(function () {
            var elem = $(this);
            if (elem.attr("name") === undefined) {
                return true;
            }
            values[elem.attr("name")] = elem.val();
        });
        return values;
    },
    getDetailsData: function (details) {
        return details.find(".details-block input").val() ? {field: details.find(".details-select").val(), value: details.find(".details-block input").length > 1 ? [details.find(".details-block input").first().val(), details.find(".details-block input").last().val()] : details.find(".details-block input").val()} : false;
    },
    /* Собираем все цели в JSON объект */
    getJsonTarget: function () {
        var result = [];
        $(".target-row").each(function () {
            var target = $(this);
            var value = target.find(".target-chosen").val();
            if ($.trim(value) !== '') {
                var data = {target: value};
                /* Если наклейка добавлена в панель предпросмотра */
                if ($(".live-preview .autobadge-pl[data-id='" + target.attr('data-id') + "']").length) {
                    var targetClass = 'ab-target-' + target.attr('data-id');
                    if ($.autobadge_appearance.ribbons[targetClass] !== undefined && $.autobadge_appearance.ribbons[targetClass][$.autobadge_appearance.ribbons[targetClass]['active']] !== undefined) {
                        var settings = $.autobadge_appearance.ribbons[targetClass][$.autobadge_appearance.ribbons[targetClass]['active']];
                        /* Удаляем лишние настройки */
                        if (settings.size.values !== undefined) {
                            delete settings.size.values;
                        }
                        data['conditions'] = {id: $.autobadge_appearance.ribbons[targetClass]['active'], settings: settings};
                    }
                }
                result.push(data);
            }
        });
        return result.length ? JSON.stringify(result) : '';
    }
};
