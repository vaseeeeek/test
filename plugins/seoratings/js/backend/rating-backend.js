(function(module, global) {
  'use strict';

  module.navTemplate = module.getTemplate('#nav-top');

  module.RatingModel = Backbone.Model.extend({
    defaults: {
      'title': 'Новый рейтинг',
      'template_id': 'standard'
    },
    url: function() {
      let url = '?plugin=seoratings&module=backendRating&action=save';
      if (this.id) {
        url += '&id=' + this.id;
      }
      return url;
    },
    validate: function(attrs) {
      const errors = [];

      if (!attrs.url) {
        errors.push({ key: 'url', value: 'Укажите правильный url.' });
      }
      if (!attrs.title) {
        errors.push({ key: 'title', value: 'Задайте название для рейтинга.' });
      }

      return errors.length ? errors : undefined;
    }
  });

  module.RatingCollection = Backbone.Collection.extend({
    model: module.RatingModel,
    url: '?plugin=seoratings&module=backendRating&action=getAll'
  });

  module.RatingView = Backbone.View.extend({
    events: {
      'click [data-js="image-upload"]': function(event) {
        event.preventDefault();
        const imageInput = this.$('#image');
        const allowedImageExtension = 'jpeg|jpg|gif|png';
        const files = imageInput[0].files;
        const imageUploadStatusElement = this.$('[data-js="image-upload-status"]');
        if (!files.length) {
          imageUploadStatusElement.html('Выберите файл для загрузки');
          return;
        }
        const formData = new FormData();
        const file = files[0];
        imageUploadStatusElement.html('Загрузка...');
        if (!file.type.match(`image/(${allowedImageExtension})`)) {
          imageUploadStatusElement.innerHTML = `Доступные форматы: ${allowedImageExtension.replace('|', ',')}`;
          return;
        }
        formData.append('image', file, file.name);
        $.ajax({
          url: `?plugin=seoratings&module=backendRating&action=imageUpload&id=${this.model.get('id')}`,
          data: formData,
          cache: false,
          processData: false,
          contentType: false,
          type: 'POST',
        }).done((response) => {
          if (this.$('[data-js="image-thumb-element"]').length) {
            this.$('[data-js="image-thumb-element"]').attr('src', response.img_thumb);
          } else {
            $(`
            <div class="seoratings-backend__control--image">
              <img class="seoratings-backend__image--thumb" src="${response.img_thumb}" alt="Изображение рейтинга" data-js="image-thumb-element"/>
            </div>
          `).insertBefore(imageInput);
          }
          imageUploadStatusElement.html(response.message);
          setTimeout(() => {
            imageUploadStatusElement.html('');
          }, 2000);
        });
      },
      'click [data-js="image-clear"]': function(event) {
        event.preventDefault();
        if (confirm('Удалить изображение?')) {
          Backbone.trigger('loader:show');
          $.post(`?plugin=seoratings&module=backendRating&action=imageRemove`, {id: this.model.get('id')}).done((response) => {
            Backbone.trigger('loader:hide');
            this.$('[data-js="image-upload-status"]').html(response.message);
            if (response.status === 'success') {
              this.$('[data-js="image-thumb-element"]').remove();
            }
          });
        }
      },
      'click [data-js="save"]': function(event) {
        event.preventDefault();
        Backbone.trigger('loader:show');
        this.$('.seoratings-backend__error').remove();
        this.model.set('feature_codes', $('[name="feature_codes"]').val());
        this.model.set('shop_categories', $('[name="shop_categories"]').val());
        this.model.set('filter_codes', $('[name="filter_codes"]').val());
        this.model.set('related_ratings', $('[name="related_ratings"]').val());
        if (this.model.isNew()) {
          if (this.model.isValid()) {
            this.model.save().then(() => {
              this.collection.add(this.model);
              this.$('#seoratings-nav-top').html(module.navTemplate({ ratingId: this.model.get('id') }));
              this.$el.removeClass('seoratings-backend__content--is-new-rating');
              Backbone.trigger('loader:hide');
            });
          } else {
            Backbone.trigger('loader:hide');
          }
        } else {
          this.model.save().then(() => Backbone.trigger('loader:hide'));
        }
      },
      'keyup [name="title"]': function(event) {
        this.model.set(event.target.name, event.target.value);
      },
      'keyup [name="url"]': function(event) {
        this.model.set(event.target.name, event.target.value);
      },
      'change': function(event) {
        let $target = $(event.target);
        if ($target.attr('name') === 'url' || $target.attr('name') === 'title') {
          return;
        }
        if ($target.attr('type') === 'checkbox') {
          this.model.set($target.attr('name'), $target.prop('checked'));
        } else {
          this.model.set($target.attr('name'), $target.val());
        }
      }
    },
    tagName: 'div',
    template: module.getTemplate('#rating'),
    initialize: function() {
      this.listenTo(this.model, 'destroy', () => {
        this.remove();
      });
      this.listenTo(this.model, 'invalid', () => {
        const errors = this.model.validationError;
        _.forEach(errors, (error) => {
          $(`[name="${error.key}"]`).after(`<div class="seoratings-backend__error">${error.value}</div>`);
        });
      });
      this.listenTo(this.model, 'change:url', (model, val) => {
        this.$('.seoratings-backend__hint--frontend').empty();
        _.forEach(module.frontend_urls, (url) => {
          this.$('.seoratings-backend__hint--frontend').append(
            `<a class="seoratings-backend__link--frontend" href="${url.url}${val}/" target="_blank">${url.url}${val}</a><br>`
          );
        });
      });
    },
    createSlimSelects: function() {
      this.$el.find('select').each((i, select) => {
        const name = select.getAttribute('name');
        const options = { select, searchPlaceholder: 'Поиск', placeholderText: '' };
        const limit = select.getAttribute('data-limit');
        if (name === 'type') {
          options.onChange = (info) => {
            const setSelector = this.$el.find('[data-js="set_id_select"]');
            info.value === 'set' ? setSelector.show() : setSelector.hide();
          };
        }
        if (limit !== null) {
          options.limit = limit;
        }

        const slim = new SlimSelect(options);
        this.$el.find(`[data-js-for="${name}"]`).on('click', (e) => {
          e.preventDefault();
          slim.set([]);
        });
      });
    },
    createDatepickers: function() {
      this.$('[data-toggle="datepicker"]').each((i, el) => {
        el = $(el);
        el.datepicker({
          autoHide: true,
          format: 'yyyy-mm-dd',
          trigger: this.$(`[data-js-datepicker-trigger-for="${el.attr('name')}"]`)[0]
        });
        this.$(`[data-js-datepicker-empty-for="${el.attr('name')}"]`).on('click', () => {
          el.val('');
          this.model.set(el.attr('name'), '');
        });
      });
    },
    createColorPickers: function() {
      this.$el.find('[data-js="color-picker"]').each((i, el) => {
        const name = el.getAttribute('data-for');
        const inputSelector = `input[name="${name}"]`;
        const input = this.$el.find(inputSelector);
        const swatches = [
          input.attr('data-default'),
        ];
        if (input.val() && input.val() !== input.attr('data-default')) {
          swatches.push(input.val());
        }
        const pickr = new Pickr({
          el,
          theme: 'nano',
          default: input.val() ? input.val() : '#333',
          swatches,
          components: {
            preview: true,
            hue: true,
            interaction: {
              input: true,
            }
          },
          i18n: {
            'btn:save': 'Сохранить'
          }
        });
        pickr.on('hide', (instance) => {
          const color = instance.getColor().toHEXA().toString();
          pickr.applyColor();
          this.model.set(name, color);
          input.val(color);
        });
        pickr.on('change', (color) => {
        });
      });
    },
    createFolds: function() {
      this.$el.find('[data-js="fold__title"]').on('click', (e) => {
        e.preventDefault();
        const current = $(e.currentTarget);
        const folds = this.$el.find('[data-js="fold__title"]').not(current);
        folds.removeClass('-active');
        folds.next('[data-js="fold__content"]').hide();
        current.addClass('-active');
        current.next('[data-js="fold__content"]').show();
      });
    },
    render: function() {
      let data = this.model.toJSON();
      data['frontend_urls'] = module.frontend_urls;
      data['seoratings_features'] = module.seoratings_features;
      data['seoratings_shop_categories'] = module.seoratings_shop_categories;
      data['seoratings_templates'] = module.seoratings_templates.toJSON();
      data['seoratings_standard_templates'] = module.seoratings_standard_templates.toJSON();
      data['sets'] = module.seoratings_sets;
      data['ratings'] = _.chain(this.collection.toJSON()).filter((item) => {
        return item.id !== this.model.get('id');
      }).value();
      this.$el.html(this.template(data));
      this.$('#seoratings-nav-top').html(module.navTemplate({ ratingId: this.model.get('id') }));
      this.createDatepickers();
      this.createSlimSelects();
      this.createColorPickers();
      this.createFolds();

      return this;
    }
  });

  module.RatingCollectionView = Backbone.View.extend({
    el: '[data-js="rating__links"]',
    initialize: function() {
      this.collection.on('add', (model) => {
        this.$el.append(this.renderOne(model));
      });
      this.collection.on('change', (model) => {
      });
      this.listenTo(Backbone, 'rating:nav:item:click', (model) => {
        const activeItem = this.$(`[data-js="tree__link--category"][href="${model.get('id')}"]`);
        activeItem.closest('.seoratings-backend__item--categories').addClass('-current');
      });
      this.listenTo(Backbone, 'rating:nav:item:deactivate', () => {
        this.$('.seoratings-backend__item--categories').removeClass('-current');
      });
    },
    renderOne: function(model) {
      let view = new module.RatingNavItemView({ model });
      return view.render().el;
    },
    render: function() {
      this.collection.forEach((model) => {
        this.$el.append(this.renderOne(model));
      });
    }
  });

  module.RatingNavItemView = Backbone.View.extend({
    className: 'seoratings-backend__item--categories',
    template: module.getTemplate('#nav-item'),
    events: {
      'click': function() {
        Backbone.trigger('rating:nav:item:deactivate', this.model);
        Backbone.trigger('rating:nav:item:click', this.model);
      }
    },
    initialize: function() {
      this.listenTo(this.model, 'change:title', () => {
        this.render();
      });
      this.listenTo(this.model, 'destroy', () => {
        this.remove();
      });
    },
    render: function() {
      let data = this.model.toJSON();
      this.$el.html(this.template(data));
      return this;
    }
  });

  module.RatingProductsModel = Backbone.Model.extend({
    defaults: {
      timestamp: Date.now()
    },
    url: function() {
      let url = '?plugin=seoratings&module=backendRatingProducts&action=save';
      if (this.id) {
        url += '&id=' + this.id;
      }
      return url;
    },
  });

  module.RatingProductsCollection = Backbone.Collection.extend({
    model: module.RatingProductsModel,
    url: function() {
      let url = '?plugin=seoratings&module=backendRatingProducts&action=default';
      if (this.ratingId) {
        url += '&id=' + this.ratingId;
      }
      return url;
    },
    comparator: function(left) {
      return parseInt(left.get('sort'));
    },
    initialize: function(models, options) {
      this.ratingId = options.ratingId;
    },
  });

  module.RatingProductsItemView = Backbone.View.extend({
    events: {
      'click [data-js="rating__product--delete"]': 'deleteProduct',
      'click [data-js="rating__product--up"]': function() {
        Backbone.trigger('products:sort', this.model, {
          'current': parseInt(this.model.get('sort')),
          'wanted': parseInt(this.model.get('sort')) - 1,
          'swap': true
        });
      },
      'click [data-js="rating__product--down"]': function() {
        Backbone.trigger('products:sort', this.model, {
          'current': parseInt(this.model.get('sort')),
          'wanted': parseInt(this.model.get('sort')) + 1,
          'swap': true
        });
      },
      'keydown [data-js="rating__product--sort"]': function(event) {
        let wanted = parseInt(event.target.value);
        if (wanted === parseInt(this.model.get('sort'))) {
          return true;
        }
        if (!isNaN(wanted) && wanted > 0 && /*(*/event.which === 13/* || (event.which || event.keyCode) === 9)*/) {
          Backbone.trigger('products:sort', this.model, {
            current: this.model.get('sort'),
            wanted,
            swap: false
          });
        }
      },
      'blur [data-js="rating__product--sort"]': function(event) {
        let wanted = parseInt(event.target.value);
        if (wanted === parseInt(this.model.get('sort'))) {
          return true;
        }
        if (isNaN(wanted) || wanted === 0) {
          event.target.value = this.model.get('sort');
        } else {
          Backbone.trigger('products:sort', this.model, {
            current: this.model.get('sort'),
            wanted,
            swap: false
          });
        }
      }
    },
    className: 'seoratings-backend__item--products',
    template: module.getTemplate('#products-item'),
    render: function() {
      let data = this.model.toJSON();
      this.$el.html(this.template(data));
      return this;
    },
    deleteProduct: function() {
      this.model.destroy().then(() => {
        this.remove();
        Backbone.trigger('products:delete');
      });
    }
  });

  module.RatingProductsCollectionView = Backbone.View.extend({
    className: 'seoratings-backend__list--products',
    template: module.getTemplate('#products'),
    initialize: function(attributes, options) {
      this.ratingId = options.ratingId;
      this.listenTo(Backbone, 'products:sort', function(model, options) {
        let wanted = parseInt(options.wanted);
        let current = parseInt(options.current);
        const swap = options.swap;
        if (wanted === 0) {
          return;
        }
        model.set('timestamp', Date.now());
        const deferredArray = [];
        if (swap) {
          const next = this.collection.find((product) => parseInt(product.get('sort')) === wanted);
          if (next) {
            next.set('sort', model.get('sort'));
            deferredArray.push(next.save());
          }
          model.set('sort', wanted);
          deferredArray.push(model.save());
        } else {
          model.get('sort') < wanted ? wanted++ : wanted--;
          model.set('sort', wanted);
          deferredArray.push(model.save());
        }
        $.when(deferredArray).then(() => {
          if (!swap) {
            this.resort(() => {
              const el = this.$('.seoratings-backend__item--products').eq(model.get('sort') - 1);
              el.addClass('-absolute');
              el.attr('data-shift', current - wanted);
            });
          } else {
            this.collection.sort();
            this.renderCollection();
          }
        });
      });
      this.listenTo(Backbone, 'products:delete', this.resort);

      let timeout = null;
      this.listenTo(Backbone, 'products:sort', (model, options) => {
        if (options.swap) {
          if (model.get('sort') <= options.current) {
            this.$('.seoratings-backend__item--products').eq(model.get('sort') - 1).addClass('-up');
          } else {
            this.$('.seoratings-backend__item--products').eq(model.get('sort') - 1).addClass('-down');
          }
        } else {
        }
        // if (timeout) {
        //   clearTimeout(timeout);
        // }
        // timeout = setTimeout(() => {
        //   this.$('.seoratings-backend__item--products').removeClass('-up -down');
        // }, 1000);
      });
    },
    renderOne: function(model) {
      let view = new module.RatingProductsItemView({ model });
      view.render();
      return view;
    },
    renderCollection: function() {
      this.$el.find('#products-list').empty();
      if (this.collection.length) {
        this.collection.forEach((model) => {
          this.$el.find('#products-list').append(this.renderOne(model).el);
        });
      }
    },
    render: function() {
      this.$el.html(this.template({ collection: this.collection }));
      this.renderCollection();
      this.$('#seoratings-nav-top').html(module.navTemplate({ ratingId: this.ratingId }));
      return this;
    },
    resort: function(callback) {
      let index = 1;
      this.collection.sort();
      this.collection.forEach((model) => model.set('sort', index++));
      $.post(
        '?plugin=seoratings&module=backendRatingProducts&action=sort',
        { data: this.collection.map(model => ({ id: model.get('id'), sort: model.get('sort') })) }
      ).then(() => {
        this.renderCollection();
        if (callback) {
          callback();
        }
      });
    }
  });

  module.RatingProductsSetView = Backbone.View.extend({
    className: 'seoratings-backend__list--set',
    template: module.getTemplate('#products-set'),
    initialize: function(attributes, options) {
      this.ratingId = options.ratingId;
    },
    render: function() {
      this.$el.html(this.template({}));
      this.$('#seoratings-nav-top').html(module.navTemplate({ ratingId: this.ratingId }));
      return this;
    },
  });

  module.RatingTemplateModel = Backbone.Model.extend({
    url: function() {
      let url = '?plugin=seoratings&module=backendTemplates&action=save';
      if (this.id) {
        url += '&id=' + this.id;
      }
      return url;
    },
    defaults: {
      name: 'Новый шаблон',
      html: Seoratings.standard.html,
      css: Seoratings.standard.css
    }
  });

  module.RatingTemplatesCollection = Backbone.Collection.extend({
    model: module.RatingTemplateModel,
    url: function() {
      let url = '?plugin=seoratings&module=backendTemplates&action=default';
      return url;
    },
    initialize: function(models, options) {
    },
  });

  module.RatingTemplatesView = Backbone.View.extend({
    events: {
      'click [data-js="handle__template__create"]': function() {
        this.cleanUp();
        const model = new module.RatingTemplateModel();
        this.listenTo(model, 'template:load', template => {
          this.htmlEditor.setValue(template.html, -1);
          this.cssEditor.setValue(template.css, -1);
        });
        this.currentView = new module.RatingFormTemplateView({ model, collection: this.collection });
        this.$('#template-item-form').html(this.currentView.render().el);
        this.$('#template-item-view').remove();
        this.initAce(model);
      },
      'click [data-js="handle__template__edit"]': function() {
        Backbone.trigger('loader:show');
        const id = parseInt(this.$('[name="seoratings_templates"]').val());
        let model = this.collection.find(function(model) {
          return parseInt(model.get('id')) === id;
        });
        this.$('#template-item-view').remove();
        this.currentView = new module.RatingFormTemplateView({ model, collection: this.collection });
        this.$('#template-item-form').html(this.currentView.render().el);
        this.initAce(model);
        Backbone.trigger('loader:hide');
      }
    },
    template: module.getTemplate('#templates'),
    initialize: function() {
      this.currentView = null;
      this.htmlEditor = null;
      this.cssEditor = null;
    },
    initAce: function(model) {
      this.htmlEditor = ace.edit('html');
      this.htmlEditor.setOption('minLines', 30);
      this.htmlEditor.setOption('maxLines', 30);
      this.htmlEditor.setValue(model.get('html'), -1);
      this.htmlEditor.setOption('showPrintMargin', false);
      this.htmlEditor.getSession().setMode('ace/mode/smarty');
      this.htmlEditor.getSession().on('change', () => {
        model.set('html', this.htmlEditor.getSession().getValue());
      });

      this.cssEditor = ace.edit('css', {
        mode: 'ace/mode/css'
      });
      this.cssEditor.setOption('minLines', 30);
      this.cssEditor.setOption('maxLines', 30);
      this.cssEditor.setValue(model.get('css'), 1);
      this.cssEditor.setOption('showPrintMargin', false);
      this.cssEditor.getSession().setMode('ace/mode/css');
      this.cssEditor.getSession().on('change', () => {
        model.set('css', this.cssEditor.getSession().getValue());
      });
    },
    cleanUp: function() {
      if (this.currentView) {
        this.currentView.remove();
      }
      if (this.htmlEditor) {
        this.htmlEditor.destroy();
      }
      if (this.cssEditor) {
        this.cssEditor.destroy();
      }
      this.$('#html').html('').removeClass('ace ace_editor ace-tm');
      this.$('#css').html('').removeClass('ace ace_editor ace-tm');
    },
    render: function() {
      this.$el.html(this.template({
        seoratings_templates: this.collection.toJSON(),
        seoratings_standard_templates: module.seoratings_standard_templates.toJSON(),
        hasTemplates: this.collection.length
      }));
      return this;
    }
  });

  module.RatingFormTemplateView = Backbone.View.extend({
    events: {
      'click [data-js="save"]': function(event) {
        // TODO: button save animation!
        Backbone.trigger('loader:show');
        this.model.save().then(function() {
          Backbone.trigger('loader:hide');
        });
        this.collection.add(this.model);
      },
      'keyup [name="name"]': function(event) {
        this.model.set('name', $(event.target).val());
      },
      'change': function(event) {
        let $target = $(event.target);
        if ($target.attr('type') === 'checkbox') {
          this.model.set($target.attr('name'), $target.prop('checked'));
        }
      },
      'click [data-js="handle__template__delete"]': function() {
        this.model.destroy();
        Backbone.trigger('template:destroy');
      },
      'click [data-js="handle__template__load"]': function() {
        Backbone.trigger('loader:show');
        $.getJSON(
          '?plugin=seoratings&module=backendTemplates&action=getOne',
          { id: this.$('[name="seoratings_templates"]').val() }
        ).then(response => {
          this.model.trigger('template:load', response);
          Backbone.trigger('loader:hide');
        });
      }
    },
    tagName: 'form',
    template: module.getTemplate('#template-form'),
    initialize: function() {
      this.listenTo(this.model, 'destroy', () => {
        this.remove();
      });
    },
    render: function() {
      const model = _.extend(this.model.toJSON(), {
        isNew: this.model.isNew(),
        seoratings_templates: this.collection.toJSON(),
        seoratings_standard_templates: module.seoratings_standard_templates.toJSON(),
        hasTemplates: this.collection.length > 0
      });
      this.$el.html(this.template(model));
      return this;
    }
  });

  module.LoaderView = Backbone.View.extend({
    className: 'seoratings-backend__loader',
    template: module.getTemplate('#loader'),
    initialize() {
      this.render();
      this.hide();
    },
    show() {
      this.$el.fadeIn(400, () => {
        this.$el.show();
      });
    },
    hide() {
      this.$el.fadeOut(400, () => {
        this.$el.hide();
      });
    },
    render() {
      this.$el.html(this.template());
    }
  });
})(Seoratings, window);
