$(document).ready(function () {

    // Adaptive start
    $(".mobile-bars .show-mainmenu").click(function () {
        $(".mainmenu").slideToggle();
        return false;
    });

    $(".topmenumain .topmenu > li").clone().appendTo(".mainmenu").addClass("clone");

    $(".adaptive-left-block").clone().appendTo(".adaptive-block-insert").css("display","block");

    // Adaptive end

    // countdown
    if ($.fn.countdowntimer) {
        $('.js-promo-countdown').each(function () {
            var $this = $(this).html('');
            var id = ($this.attr('id') || 'js-promo-countdown' + ('' + Math.random()).slice(2));
            $this.attr('id', id);
            var start = $this.data('start').replace(/-/g, '/');
            var end = $this.data('end').replace(/-/g, '/');
            $this.countdowntimer({
                startDate: start,
                dateAndTime: end,
                size: 'lg'
            });
        });
    }

    $(".compare-from-list").tooltip();
    $(".left_menu_block a:first").click(function () {
        $(this).parent("div").find("div:first").slideToggle();
        return false;
    });
    $(".slideblock").click(function () {
        $(this).parent("div").find("div:first").slideToggle();
        return false;
    });
    $(".leftmenu .collapsible > a").after('<a href="#" class="show_cat"><i class="fa fa-angle-down"></i></a>');
    $(".selected").find("a:first").addClass("selected_menu");
    $(".leftmenu li.selected").find("ul:first").css("display", "block");
    $(".leftmenu li.selected").parents("ul").css("display", "block");

    $(".leftmenu_second .collapsible > a").after('<a href="#" class="show_cat"><i class="fa fa-angle-down"></i></a>');
    $(".leftmenu_second li.selected").find("ul:first").css("display", "block");
    $(".leftmenu_second li.selected").parents("ul").css("display", "block");


    $(".show_cat").click(function () {
        $(this).parent(".collapsible").find("ul:first").slideToggle();
        return false;
    });

    // Добавление товара без выбора количества. Вид thumbs
    $(document).on("click", ".product-thumbs button.singleadd", function () {
        var f = $(this).closest(".buy");
        var price = f.find(".price").data("price").toString().replace(/[^0-9,]/, "").replace(/[,-]/g, ".");
        var quantity = 1;
        var name = f.data("name");
        var url = f.data("url");
        var formsend = $(this).closest("form");

        if (formsend.data('preview')) {
            var d = $('#preview');
            var c = d.find('.cartpreview').empty();
            c.load(formsend.data('url'), function () {
                d.show();
                if ((c.height() > c.find('form').height())) {
                    c.css('bottom', 'auto');
                } else {
                    c.css('bottom', 'auto');
                }
            });
            return false;
        }

        $.post(formsend.attr('action') + '', formsend.serialize(), function (response) {
            if (response.status == "ok") {
                $(".cart-count").html(response.data.count);
                $(".cart-total").html(response.data.total);
                var b = $("#bottombar");
                var d = $('#showorder');
                b.find(".cart-count").removeClass('empty_cart_count');
                b.find(".bottombar_bg_cart").removeClass('empty_cart');
                d.show();
                var c = d.find('.cartpreview');
                var productimage = formsend.find('.image img').attr('src');
                var productname = formsend.find('.name a').html();
                c.append('<div class="text-center popimage"><img src="' + productimage + '"></div>');
                c.append('<div class="text-center"><h3>' + productname + '</h3></div>');
            } else if (response.status == 'fail') {
                alert(response.errors);
            }
        }, "json");
        return false;
    });


    // Добавление кросс товаров в корзину. Вид thumbs
    $(document).on("click", ".product-thumbs-cs button.singleadd", function () {
        var f = $(this).closest(".buy");
        var price = f.find(".price").data("price").replace(/[^0-9,]/, "").replace(/[,-]/g, ".");
        var name = f.data("name");
        var url = f.data("url");
        var formsend = $(this).closest("form");

        $.post(formsend.attr('action') + '', formsend.serialize(), function (response) {
            if (response.status == "ok") {
                var d = $('#showorder');
                var e = $("#bottombar");
                var c = d.find('.cartpreview');
                $(".cart-count").html(response.data.count);
                $(".cart-total").html(response.data.total);
                e.find(".cart-count").removeClass('empty_cart_count');
                e.find(".bottombar_bg_cart").removeClass('empty_cart');
                d.show();
                var productimage = formsend.find('.image img').attr('src');
                var productname = formsend.find('.name a').html();
                c.append('<div class="text-center popimage"><img src="' + productimage + '"></div>');
                c.append('<div class="text-center"><h3>' + productname + '</h3></div>');
            } else if (response.status == 'fail') {
                alert(response.errors);
            }
        }, "json");
        return false;
    });

    // Добавление в корзину из карточки товара
    $(".cart-form").submit(function () {
        var f = $(this);
        $.post(f.attr('action'), f.serialize(), function (response) {
            if (response.status == 'ok') {
                $('.cart-count').html(response.data.count);
                $("#bottombar").find(".cart-count").removeClass('empty_cart_count');
                $("#bottombar").find(".bottombar_bg_cart").removeClass('empty_cart');
                $('.cart-total').html(response.data.total);
                $(".buyproduct").slideUp();
                $('.status').slideDown();
                $(".added2cart").show();
                var price = parseFloat($(".changeprice").html().replace(/[^0-9,]/, "").replace(/[,-]/g, "."));
                var quantity = parseInt(f.find(".select_input").val());
                var name = f.data("name");
                var url = f.data("url");
                var product = $(".cart-popup").find("[data-cartpopup='" + response.data.item_id + "']");
            } else if (response.status == 'fail') {
                alert(response.errors);
            }
        }, "json");
        return false;
    });

    // Прибавление кол-ва товара. Лимит 99. Измените 99 на необходимое число
    $(document).on("click", ".inc_product", function () {
        var current = $(this).prev(".select_input").val();
        if (current >= 99) {
            var current = 99;

        }
        $(this).prev(".select_input").val(parseInt(current) + 1);
        $(this).closest(".cart-product").find(".item-count").html(parseInt(current) + 1);
        $(this).prev(".select_input").animate({'backgroundColor': '#d5e3e7'}, "fast").animate({'backgroundColor': 'white'}, "slow");
        return false;
    });

    // Вычитание кол-ва товара.
    $(document).on("click", ".dec_product", function () {
        var current = $(this).next(".select_input").val();
        if (current == 1) {
            var current = 2;
        }

        $(this).next(".select_input").val(parseInt(current) - 1);
        $(this).closest(".cart-product").find(".item-count").html(parseInt(current) - 1);
        $(this).next(".select_input").animate({'backgroundColor': '#d5e3e7'}, "fast").animate({'backgroundColor': 'white'}, "slow");
        return false;
    });

    // Показывать "Быстрый просмотр" в каталоге
    $(".image").hover(function () {
        $(this).find(".preview").animate({
            opacity: "show",
            top: "+8px"
        }, "fast");
    }, function () {
        $(this).find(".preview").animate({
            opacity: "hide",
            top: "-8px"
        }, "fast");

    });

    // Обработка клика на кнопку "Быстрый просмотр"
    $(document).on("click", ".preview a", function () {
        var f = $(this).closest("form");
        var d = $('#preview');
        var c = d.find('.cartpreview').empty();
        c.load(f.data('url'), function () {
            d.show();
            if ((c.height() > c.find('form').height())) {
                c.css('bottom', 'auto');
            } else {
                c.css('bottom', 'auto');
            }
        });
        return false;
    });

    // Закрыть всплывающее окно
    $('.dialog').on('click', 'a.dialog-close', function () {
        $(this).closest('.dialog').hide().find('.cartpreview').empty();
        return false;
    });

    // Закрыть всплываюее окно по кнопке ESC
    $(document).keyup(function (e) {
        if (e.keyCode == 27) {
            $(".dialog:visible").hide().find('.cartpreview').empty();
        }
    });

    // Живой поиск по каталогу
    var f = function () {
        var ajax_form_callback = function (f) {
            var fields = f.serializeArray();
            var params = [];
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].value !== '') {
                    params.push(fields[i].name + '=' + fields[i].value);
                }
            }
            var url = '?' + params.join('&');
            $(window).lazyLoad && $(window).lazyLoad('sleep');
            var block_loading = $(".centermenufilter");
            var height = block_loading.height();
            block_loading.append("<div class='loading'><img src='" + f.data('loading') + "'></div>");
            block_loading.css("opacity", "0.7");
            $(".loading").css("top", height / 2 - 30 + "px");
            $.get(url, function (html) {
                var tmp = $('<div></div>').html(html);
                $(".loading").html("");
                $(".centermenufilter").css("opacity", "1");
                $('#product-list').html(tmp.find('#product-list').html());
                if (!!(history.pushState && history.state !== undefined)) {
                    window.history.pushState({}, '', url);
                }
                $(window).lazyLoad && $(window).lazyLoad('reload');

                $(".image").hover(function () {
                    if ($(".container").width() > 500) {
                        $(this).find(".preview").animate({
                            opacity: "show",
                            top: "+8px"
                        }, "fast");
                    }
                }, function () {
                    $(this).find(".preview").animate({
                        opacity: "hide",
                        top: "-8px"
                    }, "fast");

                });
                compare();
                // Вид отображения каталога при загрузке
                var cookie = $.cookie('show');
                if (!cookie || cookie == 'thumbs') {
                    $("#thumbs").addClass('current');
                    $("#list").removeClass('current');
                    $(".show-catalog").attr("id", "");
                } else if (cookie == 'list') {
                    $("#list").addClass('current');
                    $("#thumbs").removeClass('current');
                    $(".show-catalog").attr("id", "list");
                }

                // Вид отображения каталога при нажатии на кнопку
                $(".showvariant a").click(function () {
                    var current = $(this).attr("id");
                    if (current == 'thumbs') {
                        $("#thumbs").addClass('current');
                        $("#list").removeClass('current');
                        $(".show-catalog").attr("id", "");
                        $.cookie('show', 'thumbs', {
                            expires: 7,
                            path: '/'
                        });
                    } else {
                        $("#list").addClass('current');
                        $("#thumbs").removeClass('current');
                        $(".show-catalog").attr("id", "list");
                        $.cookie('show', 'list', {
                            expires: 7,
                            path: '/'
                        });
                    }
                    return false;
                });

            });
        };

        $('.filters.ajax form input').change(function () {
            ajax_form_callback($(this).closest('form'));
            return false;
        });

        $('.filters .slider').each(function () {
            if (!$(this).find('.filter-slider').length) {
                $(this).append('<div class="filter-slider"></div>');
            } else {
                return;
            }
            var min = $(this).closest(".slider_block").find('.min');
            var max = $(this).closest(".slider_block").find('.max');
            var min_value = parseFloat(min.attr('placeholder'));
            var max_value = parseFloat(max.attr('placeholder'));
            var step = 1;
            var slider = $(this).find('.filter-slider');
            if (slider.data('step')) {
                step = parseFloat(slider.data('step'));
            } else {
                var diff = max_value - min_value;
                if (Math.round(min_value) != min_value || Math.round(max_value) != max_value) {
                    step = diff / 10;
                    var tmp = 0;
                    while (step < 1) {
                        step *= 10;
                        tmp += 1;
                    }
                    step = Math.pow(10, -tmp);
                    tmp = Math.round(100000 * Math.abs(Math.round(min_value) - min_value)) / 100000;
                    if (tmp && tmp < step) {
                        step = tmp;
                    }
                    tmp = Math.round(100000 * Math.abs(Math.round(max_value) - max_value)) / 100000;
                    if (tmp && tmp < step) {
                        step = tmp;
                    }
                }
            }
            slider.slider({
                range: true,
                min: parseFloat(min.attr('placeholder')),
                max: parseFloat(max.attr('placeholder')),
                step: step,
                values: [parseFloat(min.val().length ? min.val() : min.attr('placeholder')),
                    parseFloat(max.val().length ? max.val() : max.attr('placeholder'))],
                slide: function (event, ui) {
                    var v = ui.values[0] == $(this).slider('option', 'min') ? '' : ui.values[0];
                    min.val(v);
                    v = ui.values[1] == $(this).slider('option', 'max') ? '' : ui.values[1];
                    max.val(v);
                },
                stop: function (event, ui) {
                    min.change();
                }
            });
            min.add(max).change(function () {
                var v_min = min.val() === '' ? slider.slider('option', 'min') : parseFloat(min.val());
                var v_max = max.val() === '' ? slider.slider('option', 'max') : parseFloat(max.val());
                if (v_max >= v_min) {
                    slider.slider('option', 'values', [v_min, v_max]);
                }
            });
        });

    }
    f();
    $(".filter-button a").click(function () {
        var filter = $.cookie('filter');
        if (!filter || filter == '1') {
            $.cookie('filter', '0', {
                expires: 7,
                path: '/'
            });
        } else if (filter == '0') {
            $.cookie('filter', '1', {
                expires: 7,
                path: '/'
            });
        }
        $(".filters").slideToggle();
        return false;
    });


    // Изменение кол-ва выводимого товара в каталоге
    $(document).on("click", ".product-per-page a", function () {
        var perpage = $(this).data("page");
        $.cookie('products_per_page', perpage);
        $(".filters.ajax form").submit();
        return false;
    });

    // Запрещаем вводить неверные значения в input
    $('.rangefilter input').keypress(function (event) {
        var key,
            keyChar;
        if (!event)
            var event = window.event;
        if (event.keyCode)
            key = event.keyCode;
        else if (event.which)
            key = event.which;
        if (key == null || key == 0 || key == 8 || key == 13 || key == 9 || key == 46 || key == 37 || key == 39)
            return true;
        keyChar = String.fromCharCode(key);
        if (!/\d/.test(keyChar))
            return false;
    });

    // Вид отображения каталога при загрузке
    var cookie = $.cookie('show');
    if (!cookie || cookie == 'thumbs') {
        $("#thumbs").addClass('current');
        $("#list").removeClass('current');
        $(".show-catalog").attr("id", "");
    } else if (cookie == 'list') {
        $("#list").addClass('current');
        $("#thumbs").removeClass('current');
        $(".show-catalog").attr("id", "list");
    }

    // Вид отображения каталога при нажатии на кнопку
    $(".showvariant a").click(function () {
        var current = $(this).attr("id");
        if (current == 'thumbs') {
            $("#thumbs").addClass('current');
            $("#list").removeClass('current');
            $(".show-catalog").attr("id", "");
            $.cookie('show', 'thumbs', {
                expires: 7,
                path: '/'
            });
        } else {
            $("#list").addClass('current');
            $("#thumbs").removeClass('current');
            $(".show-catalog").attr("id", "list");
            $.cookie('show', 'list', {
                expires: 7,
                path: '/'
            });
        }
        return false;
    });

    // Сбрасываем все отмеченные input
    $.fn.toggleChecked = function () {
        return this.each(function () {
            this.checked = this.unchecked;
        });
    };

    // Очистить фильтр в каталоге
    $('.clearfilter').click(function () {
        $('.filtergroup').find('input').toggleChecked().trigger('refresh');
        var min = $("input[name=price_min]");
        var max = $("input[name=price_max]");
        var value1 = parseFloat(min.attr('placeholder'));
        var value2 = parseFloat(max.attr('placeholder'));
        console.log(value1, value2);
        var slider = $('.filter-slider');
        slider.slider({
            min: value1,
            max: value2,
            values: [value1, value2]

        });
        min.val(value1);
        max.val(value2);
        $('.rangefilter input').val('');
        $('.filterform').submit();
        return false;

    });
    compare();

    var promo_slider = $('#promo').bxSlider({
        slideWidth: 209,
        maxSlides: 4,
        minSlides: 1,
        auto: true,
        autoHover: true,
        slideMargin: 20,
        responsive: false,
        adaptiveHeight: true,
        easing: "ease-in-out"
    });
    $('#next-promo').click(function () {
        promo_slider.goToNextSlide();
        return false;
    });

    $('#prev-promo').click(function () {
        promo_slider.goToPrevSlide();
        return false;
    });

    var slider = $('.slider-image').bxSlider({
        minSlides: 1,
        auto: true,
        maxSlides: 1,
        pause: 10000,
        autoHover: true,
        responsive: true,
        adaptiveHeight: true,
        slideMargin: 0,
    });
    $('#bx-next').click(function () {
        slider.goToPrevSlide();
        return false;
    });

    $('#bx-prev').click(function () {
        slider.goToNextSlide();
        return false;
    });


    // Compare
    function compare() {
        $(".li200px").on('click', 'a.compare', function () {
            var compare = $.cookie('shop_compare');
            $.cookie('shop_compare', compare, {expires: 30, path: '/'});

            if (!$(this).hasClass('active')) {
                if (compare) {
                    compare += ',' + $(this).data('product');
                } else {
                    compare = '' + $(this).data('product');
                }
                if (compare.split(',').length > 0) {
                    var url = $(".compare_bottom_link").attr('href').replace(/compare\/.*$/, 'compare/' + compare + '/');
                    $('.compare_bottom_link').attr('href', url);
                    $('.compare_bottom_count').html(compare.split(',').length);
                    $('.bottom_compare_light').css("background", "#C95515").animate({backgroundColor: '#FFFFFF'}, 300);
                }
                $.cookie('shop_compare', compare, {expires: 30, path: '/'});
            } else {
                if (compare) {
                    compare = compare.split(',');
                } else {
                    compare = [];
                }

                var i = $.inArray($(this).data('product') + '', compare);
                if (i != -1) {
                    compare.splice(i, 1)
                }
                if (compare.length > 0) {
                    $.cookie('shop_compare', compare.join(','), {expires: 30, path: '/'});
                    var url = $(".compare_bottom_link").attr('href').replace(/compare\/.*$/, 'compare/' + compare.join(',') + '/');
                    $('.compare_bottom_link').attr('href', url);
                    $('.compare_bottom_count').html(compare.length);
                    $('.bottom_compare_light').css("background", "#C95515").animate({backgroundColor: '#FFFFFF'}, 300);
                } else {
                    $.cookie('shop_compare', null, {path: '/'});
                }
            }
            $(this).toggleClass('active');
            return false;
        });
    }

    var currency_format_product = function (number, no_html, currency) {
        var i, j, kw, kd, km;
        var decimals = currency.frac_digits;
        var dec_point = currency.decimal_point;
        var thousands_sep = currency.thousands_sep;

        // input sanitation & defaults
        if (isNaN(decimals = Math.abs(decimals))) {
            decimals = 2;
        }
        if (dec_point == undefined) {
            dec_point = ",";
        }
        if (thousands_sep == undefined) {
            thousands_sep = " ";
        }

        i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

        if ((j = i.length) > 3) {
            j = j % 3;
        } else {
            j = 0;
        }

        km = (j ? i.substr(0, j) + thousands_sep : "");
        kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
        kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");

        var number = km + kw + kd;
        var s = no_html ? currency.sign : currency.sign_html;
        if (currency.sign_position != 0) {
            return number + ' ' + s;
        } else {
            return s + number;
        }
    }
    $(".topmenu li ul li:has(ul)").prepend('<span class="arrow-right"><i class="fa fa-angle-right"></i></span>');
    $(".topmenu li:has(ul)").not('ul ul li').prepend('<span class="arrow-down"><i class="fa fa-angle-down"></i></span>');
    $(window).scroll(function () {
        if ($(this).scrollTop() > 0) {
            $('#scrollerbutton').fadeIn();
        } else {
            $('#scrollerbutton').fadeOut();
        }
    });
    $('#scrollerbutton').click(function () {
        $('body,html').animate({
            scrollTop: 0
        }, 400);
        return false;
    });
    $(".clearview").click(function () {
        $.cookie('shop_view', '', {
            expires: 30,
            path: '/'
        });

    });
});