(function(module, global) {
  'use strict';

  const FilterBaseView = Backbone.View.extend({
    events: {
      'change input': function(e) {
        Backbone.trigger('filter:change', this.model);
      }
    },
    render: function() {
      this.$el.html(this.template(_.extend({
        isEmarket: module.theme_id === 'emarket'
      }, this.model.toJSON())));

      return this;
    }
  });

  module.FilterCheckboxView = FilterBaseView.extend({
    className: 'seoratings__module__filter__item',
    template: module.getTemplate('#filter-checkbox')
  });
  module.FilterColorView = FilterBaseView.extend({
    className: 'seoratings__module__filter__item',
    template: module.getTemplate('#filter-color')
  });
  module.FilterRadioView = FilterBaseView.extend({
    className: 'seoratings__module__filter__item',
    template: module.getTemplate('#filter-radio')
  });
  module.FilterRangeView = FilterBaseView.extend({
    events: {
      'change input': function(event) {
        const input = event.target;
        let [name, position] = input.name.split(':');
        let value = input.value;
        let defaultValue = parseInt(this.model.get(position));

        if (position === 'min' && value < defaultValue) {
          input.value = '';
        }
        if (position === 'max' && value > defaultValue) {
          input.value = '';
        }
        Backbone.trigger('filter:change', this.model);
      },
      'keypress input': function(event) {}
    },
    className: 'seoratings__module__filter__item',
    template: module.getTemplate('#filter-range')
  });
  module.FilterCollection = Backbone.Collection.extend({});
  module.FilterCollectionView = Backbone.View.extend({
    className: 'seoratings__module__filter__container',
    template: module.getTemplate('#filter'),
    initialize: function() {
      this.listenTo(Backbone, 'filter:change', this.filter.bind(this));
    },
    render: function() {
      this.collection.forEach((model) => {
        const ViewClass = module[this.getFilterView(model.get('type'))];
        if (ViewClass) {
          const view = new ViewClass({ model });
          this.$el.append(view.render().el);
        }
      });
      return this;
    },
    getFilterView: function(type) {
      return `Filter${type[0].toUpperCase() + type.slice(1)}View`;
    },
    filter: function(model) {
      const filters = this.$('input[type="checkbox"]:checked')
        .add(this.$('input[type="text"]').filter(function(index, input) {
          return !!input.value;
        }))
        .add(this.$('input[type="radio"]:checked').filter(function(index, input) {
          return input.value !== '';
        }));
      const activeFilters = {};
      filters.each(function(index, input) {
        switch (input.getAttribute('data-type')) {
          case 'range':
            console.log(input);
            let [name, value] = input.name.split(':');
            if (!activeFilters[name]) {
              activeFilters[name] = {
                name, type: 'range', values: {}, title: input.getAttribute('data-title'),
                defaults: { max: parseInt(input.getAttribute('data-max')), min: parseInt(input.getAttribute('data-min')) }
              };
            }
            activeFilters[name].values[value] = parseInt(input.value);
            break;
          case 'checkbox':
          case 'color':
            if (!activeFilters[input.name]) {
              activeFilters[input.name] = {
                name: input.name,
                title: input.getAttribute('data-title'),
                type: input.getAttribute('data-type'),
                values: []
              };
            }
            activeFilters[input.name].values.push(input.value);
            break;
          case 'radio':
            if (!activeFilters[input.name]) {
              activeFilters[input.name] = {
                name: input.name, title: input.getAttribute('data-title'),
                type: input.getAttribute('data-type'), values: []
              };
            }
            activeFilters[input.name].values.push(input.value);
        }
      });
      Backbone.trigger('filter:apply', activeFilters);
    }
  });

  module.FrontendView = Backbone.View.extend({
    el: '#seoratingsFrontend',
    initialize: function() {
      this.aside = this.$('#seoratingsAside');
      if (this.aside.length) {
        const collection = _.map(module.feature_values, (value) => value);
        const filterCollectionView = new module.FilterCollectionView({
          collection: new module.FilterCollection(collection)
        });
        this.aside.append(filterCollectionView.render().el);
        this.listenTo(Backbone, 'filter:apply', this.applyFilters);
        this.listenTo(Backbone, 'filter:apply', (activeFilters) => {
          //TODO: Refactor into own View/CollectionView
          const count = _.keys(activeFilters).length;
          const container = this.$('[data-js="filter--active"]');
          if (!count) {
            container.removeClass('-has-active-filters');
            return;
          }
          container.addClass('-has-active-filters');
          container.find('[data-js="filter__active--count"]').html(count);
          container.find('[data-js="filter__active--list"]').html(_.map(activeFilters, (filter) => {
            let colors;
            if (filter.type === 'color') {
              colors = module.feature_values[filter.name]['values'];
            }
            const filterValues = _.map(filter.values, (value) => {
              return `
                <span class="seoratings__module__filter__title--active-value -${filter.type}" 
                    ${filter.type === 'color' ? `style="background-color: ${colors[value]}"` : ''}
                  >
                  ${value}
                </span>
              `;
            }).join('');

            return `
              <div class="seoratings__module__filter__item--active-filter">
                <span class="seoratings__module__filter__title--active-name">${filter.title}: </span>
                ${filterValues}
              </div>
            `;
          }).join(''));
        });
      }
    },
    applyFilters: function(activeFilters) {
      activeFilters = _.map(_.keys(activeFilters), function(key) {
        return activeFilters[key];
      });
      let filteredCollection = [];
      let products = Seoratings.products;
      _.each(activeFilters, function(filter, index) {
        _.each(products, function(product) {
          if ((!product.features || !product.features[filter.name]) && filter.name !== 'price') {
            return false;
          }
          let found = false;
          switch (filter.type) {
            case 'range':
              let productFeatures;
              if (filter.name === 'price') {
                productFeatures = parseInt(product.price);
              } else {
                productFeatures = parseInt(product.features[filter.name]);
              }
              if (productFeatures) {
                const range = _.extendOwn({}, filter.defaults, filter.values);
                if (productFeatures >= range.min && productFeatures <= range.max) {
                  return filteredCollection.push(product);
                }
              }
              break;
            case 'radio':
              const value = filter.values[0];
              const productFeatureValue = _.keys(product.features[filter.name])[0];
              return value === productFeatureValue ? filteredCollection.push(product) : false;
            case 'checkbox':
            case 'color':
              found = _.any(filter.values, function(value) {
                const productFeatures = product.features[filter.name];
                if (productFeatures) {
                  if (typeof productFeatures === 'string') {
                    return value.toString() === productFeatures.toString();
                  }
                  for (let i in productFeatures) {
                    if (Object.prototype.hasOwnProperty.call(productFeatures, i)) {
                      const valueToTest = filter.type === 'color' ? i.toString() : productFeatures[i].toString();
                      if (value.toString() === valueToTest) {
                        return true;
                      }
                    }
                  }
                }
                return false;
              });
              return found ? filteredCollection.push(product) : false;
          }
        });
        products = filteredCollection;
        if (index !== activeFilters.length - 1) {
          filteredCollection = [];
        }
      });
      if (_.keys(activeFilters).length) {
        this.$('[data-js="product__item"]').hide();
        _.each(filteredCollection, (product) => {
          this.$(`#seoratings-product-cid-${product.id}`).show();
        });
      } else {
        this.$('[data-js="product__item"]').show();
      }
      this.$('[data-js="filter__products--count"]').html(_.keys(products).length);
      Backbone.trigger('filter:end', products, _.keys(products).length);
    }
  });

})(Seoratings, window);

new Seoratings.FrontendView();