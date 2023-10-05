(function($) {
	$.bundlingFrontend = {
		products: { },
		plugin_url: null,
		frontend_url: null,
		price: null,
		currency: null,
		format: null,
		type: null,
		wrapper: null,
		onInit: null,
		selectedProducts: null,
		getDefaultImage: function() {
			return this.plugin_url + 'img/no-image.png';
		},
		parseQuantity: function(quantity) {
			if(quantity) {
				quantity = quantity.toString().replace(',', '.');
				quantity = parseFloat(quantity);

				return quantity;
			}
		},
		mainQuantity: 1,
		updateSelectedProducts: function({
			action = '',
			bundle = null,
			product_id = null,
			sku_id = null,
			price = null,
			quantity = 1,
			image = null
		}) {
			let selected_products = JSON.parse($(this.selectedProducts).val());

			quantity = this.parseQuantity(quantity);
			if(quantity < 0)
				quantity = 0;

			switch(action) {
				case '+':
					selected_products.push({
						bundle: parseInt(bundle),
						product_id: parseInt(product_id),
						sku_id: parseInt(sku_id),
						
						price: parseFloat(price),
						quantity: quantity
					});
					break;
				case '-':
					selected_products = selected_products.filter(function(data) {
						if(!product_id)
							return !(data.bundle == bundle);
						else if(product_id && sku_id)
							return !(data.bundle == bundle && data.product_id == product_id && data.sku_id == sku_id);
						else if(!sku_id)
							return !(data.bundle == bundle && data.product_id == product_id);
					});
					break;
				case 'quantity':
					selected_products.map(function(data, key) {
						if(!product_id) {
							if(data.bundle == bundle)
								data.quantity = quantity;
						} else {
							if(data.bundle == bundle && data.product_id == product_id && data.sku_id == sku_id)
								data.quantity = quantity;
							else if(!sku_id)
								data.quantity = quantity;
						}
						
						return data;
					});
					break;
			}
			
			this.updateYourBundle(selected_products);

			$(document).trigger('bundling_update_products', selected_products);
			
			$(this.selectedProducts).val(JSON.stringify(selected_products));
		},
		getSelectedProducts: function() {
			let selected_products = JSON.parse($(this.selectedProducts).val());

			return selected_products;
		},
		init: function({
			plugin_url = null,
			frontend_url = null,
			price = null,
			excludeOriginal = false,
			currency = null,
			format = null,
			type = null,
			formSelector = '#cart-form',
			submitAction = 'submit',
			quantitySelector = 'input[name=quantity]',
			quantityPlusMinusSelector = '.buttons .plus, .buttons .minus',
			skuSelector = 'input[name=sku_id]',
			skuTypeSelector = '.sku-feature',
			skuType = 0,
			skuFeaturesSelectable = {},
			servicesSelector = 'input[name="services[]"]',
			selectedProducts = '#bundling-selected-products',
			products = {}
		}) {
			let dat = this;

			$(document).off('change.bundling_sku').on('change.bundling_sku', skuSelector, function() {
				$.bundlingFrontend.price = parseFloat($(this).data('price'));
				$.bundlingFrontend.changePrice();
			});
			
			this.skuType = skuType;
			this.skuTypeSelector = skuTypeSelector;
			this.skuFeaturesSelectable = skuFeaturesSelectable;
			
			if(skuType && skuFeaturesSelectable) {
				$(document).off('change.bundling_sku_type').on('change.bundling_sku_type', skuTypeSelector, function() {
					var sku_f = '';
					$(skuTypeSelector).each(function() {
						var feature_id = $(this).data('feature-id');
						var value = $(this).val();
						
						sku_f += '|' + feature_id + ':' + value;
					});
					sku_f = sku_f.slice(1);

					if(skuFeaturesSelectable[sku_f] !== undefined) {
						$.bundlingFrontend.price = parseFloat(skuFeaturesSelectable[sku_f]['price']);
						$.bundlingFrontend.changePrice();
					} else
						console.log('Bundling error: Can\'t find selected sku');
				});
			}
			
			this.servicesSelector = servicesSelector;
			$(document).off('change.bundling_services').on('change.bundling_services', servicesSelector, function() {
				$.bundlingFrontend.changePrice();
			});
			$(`select[name^="service_variant"]`).change(function() {
				$.bundlingFrontend.changePrice();
			});
			
			this.plugin_url = plugin_url;
			this.frontend_url = frontend_url;
			this.price = parseFloat(price);
			this.excludeOriginal = excludeOriginal;
			this.submitAction = submitAction;
			this.currency = currency;
			this.format = format;
			this.type = type;
			this.selectedProducts = selectedProducts;
			this.products = products;
			$(this.selectedProducts).val('[]');
			this.wrapper = $(`.bundling-${this.type}`);
			
			if(this.excludeOriginal) {
				document.write('<style>.bundling-your-bundle-item.default, .bundling-your { display: none; }</style>');
				
				this.mainQuantity = 0;
			}

			let cb = function() {
				window.shop_bundling = dat;
				$(document).trigger('bundling_view_init', [dat]);
			};
			
			this.updateSelectedProducts({ });
			if(typeof this.onInit == 'function')
				this.onInit(cb);
			else {
				cb();
			}
			
			let quantityBind = function(dat) {
				if(dat.data('previous-value') != dat.val()) {
					dat.data('previous-value', dat.val());
					$.bundlingFrontend.mainQuantity = parseInt(dat.val());
					$.bundlingFrontend.changePrice();
					$.bundlingFrontend.updateYourBundle($.bundlingFrontend.getSelectedProducts());
				}
			}
			
			$(quantitySelector).each(function() {
				let dat = $(this);
				quantityBind(dat);
				dat.data('previous-value', dat.val());
				dat.bind('propertychange change click keyup input paste', function() {
					quantityBind(dat);
				});
			});
			$(quantityPlusMinusSelector).bind('click', function() {
				setTimeout(function() {
					quantityBind($(quantitySelector));
				}, 100);
			});
			
			$(skuTypeSelector).trigger('change');
			
			$(document).off('click.bundling_add2cart').on('click.bundling_add2cart', '.bundling-add2cart', function() {
				let products = $.bundlingFrontend.getSelectedProducts();
				
				$.post($.bundlingFrontend.frontend_url + 'bundling/add2cart/', {
					products: products
				}, function(data) {
					if($.bundlingFrontend.submitAction == 'submit')
						$(formSelector).submit();
					else if(typeof $.bundlingFrontend.submitAction == 'function')
						$.bundlingFrontend.submitAction({
							bundling: $.bundlingFrontend,
							products: products,
							data: data
						});
				}, 'json');
			});
		},
		changePrice: function() {
			let last_price = this.price;
			$(this.servicesSelector).filter(':checked').each(function() {
				let id = $(this).val();
				let price = $(`select[name="service_variant[${id}]"]`).length ? $(`select[name="service_variant[${id}]"] option:checked`).data('price') : $(this).data('price');
				let plus = parseFloat(price);
				last_price += plus;
			});
			if(this.mainQuantity > 1) {
				last_price += this.price * (this.mainQuantity - 1);
			}

			$.bundlingFrontend.getSelectedProducts().forEach(function(data) {
				if(data.quantity > 0) {					
					if(!isNaN(data.price))
						last_price += data.price * data.quantity;
				}
			});

			let formatted_price = this.formatPrice(last_price);

			let price_wrapper = $('.bundling-last-price .price, .bundling-your-bundle .price');
			price_wrapper.html(formatted_price);

			$(document).trigger('bundling_change_price', {
				price: last_price,
				formatted_price: formatted_price
			});
		},
		formatPrice: function(price) {
			return this.format.replace(/0/, (parseFloat(price.toFixed(2))).toLocaleString('ru').replace('.', ','));
		},
		decl: function(number, titles) {
			let cases = [2, 0, 1, 1, 1, 2];  
			return titles[(number%100>4 && number%100<20)? 2 : cases[(number%10<5)?number%10:5]];  
		},
		getProduct: function(product_id, sku_id, param = null) {
			let product = $.bundlingFrontend.products[product_id + '-' + sku_id];
			
			if(param)
				return product[param];
			else
				return product;
		},
		updateYourBundle: function(products) {
			var self = this;
			let items = this.mainQuantity;

			$.each(products, function(index, value) {
				items += value.quantity;
			});

			if(items >= 1 || this.mainQuantity > 0) {
				$('.bundling-your').show();
				
				$('.bundling-your-bundle-header .items').html(items);
				let it = $('.bundling-your-bundle-header .items-text');
				it.html(this.decl(items, [it.data('one'), it.data('two'), it.data('five')]));

				let def = $('.bundling-your-bundle-item.default');
				$('.bundling-your-bundle-item:not(.default)').remove();
				def.find('.quantity').html(this.mainQuantity > 1 ? this.mainQuantity : '');

				$.each(products, function(index, value) {
					let product = $.bundlingFrontend.getProduct(value.product_id, value.sku_id);
					if(product) {
						let clone = def.eq(0).clone().removeClass('default');
						let image_src;
						if(product.image)
							image_src = product.image[$.bundlingFrontend.yourBundleImageSize];
						else
							image_src = $.bundlingFrontend.getDefaultImage();
						clone.find('img').attr('src', image_src).attr('title', product.title).wrap($('<a/>').attr('href', product.frontend_url));
						if(value.quantity > 1)
							clone.find('.quantity').html(value.quantity);
						else
							clone.find('.quantity').html('');

						clone.find('.bundling-your-bundle-item-frontend-price').html(product.frontend_price ? self.formatPrice(product.frontend_price) : '');
						clone.find('.bundling-your-bundle-item-default-frontend-price').html(product.default_frontend_price ? self.formatPrice(product.default_frontend_price) : '');
						clone.find('.bundling-your-bundle-item-compare-price').html(product.compare_price ? self.formatPrice(product.compare_price) : '');
						clone.find('.bundling-your-bundle-item-name').html(product.name);
						clone.find('.bundling-your-bundle-item-title').html(product.title);

						def.each(function() {
							let b = $(this).closest('.bundling-your-bundle-items');
							b.append('<div class="bundling-your-bundle-item">' + clone.html() + '</div>');
						});
					}
				});
			} else {
				$('.bundling-your').hide();
				
				return false;
			}
		}
	}
})(jQuery);
